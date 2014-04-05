<?php

require_once 'errorcodes.php';
if (function_exists("clr_create_thread")) {
	require_once 'phalangthread.php';
}
else {
	//causes segmentation fault
	require_once 'pthread.php';
}

Class CoreFunctions {
	private $threadid = 1;
	private $threads = array();

	private $executor;
	private $functionNames = array(
		'echo',
		'getFile',
		'saveFile',
		'isset',
		'dump',
		'exec',
		'lineIn',
		'makeThread',
		'runThread',
		'joinThread',
	);
	
	public function __construct($executor) {
		$this->executor = $executor;
	}

	public function functionExists($funcname) {
		if (in_array($funcname, $this->functionNames)) {
			return true;
		}
		return false;
	}
	
	public function execute($fname, $args) {
		switch ($fname) {
			case 'echo':
				return $this->c_echo($args);
			case 'getFile':
				return $this->c_getFile($args);
			case 'saveFile':
				return $this->c_saveFile($args);
			case 'isset':
				return $this->c_isset($args);
			case 'dump':
				return $this->c_dump($args);
			case 'exec':
				return $this->c_exec($args);
			case 'lineIn':
				return $this->c_lineIn($args);
			case 'makeThread':
				return $this->c_makeThread($args);
			case 'runThread':
				return $this->c_runThread($args);
			case 'joinThread':
				return $this->c_joinThread($args);
		}
	}
	
	private function c_makeThread($args) {
		$fname = array_shift($args);
		$rfname = $this->executor->evaluate($fname);
		$code = array( 0 => array('functionCall' => array('name' => $rfname['value'], 'args' => $args)));
		//$code = array('code' => $code, 'functions' => array(), 'classes' => array(), 'threads' => array()); // code array for $this->executor->runCode()
		
		$R = new LangExecutor($code);
		$this->executor->scope->saveScope($this->executor->scopeID);
		$R->scopeID = $this->executor->scopeID;
		
		//$R->scope = $this->executor->scope; -- crashes
		//clone thread -- cant set vars within thread then read from outside
		$R->scope = new Scope($R->scopeID);
		$R->scope->savedScopes = $this->executor->scope->savedScopes;
		$R->scope->scope = $this->executor->scope->scope;
		$R->scope->setScope($R->scopeID);
		
		$tid = $this->threadid;
		$this->threadid++;
		
		$this->threads[$tid] = array('runner' => $R, 'thread' => null, 'code' => $code);
		
		$T = new BangThread($this->threads[$tid]['runner'], 'evaluate');
		
		$this->threads[$tid]['thread'] = $T;
		
		$ret = array('type' => 'int', 'value' => $tid);		
		
		return $ret;
	}
	
	private function c_runThread($args) {
		if (isset($args[0])) {
			$tid = $this->executor->evaluate(array_shift($args));
			$tid = $tid['value'];
		}
		if (isset($this->threads[$tid])) {
			$T = $this->threads[$tid]['thread'];
			
			$T->start($this->threads[$tid]['code']);
			return array('type' => 'bool', 'value' => true);
		}
		return array('type' => 'bool', 'value' => false);
	}
	
	private function c_joinThread($args) {
		if (isset($args[0])) {
			$tid = $this->executor->evaluate($args[0]);
			$tid = $tid['value'];
		}
		if (isset($this->threads[$tid])) {
			$T = $this->threads[$tid]['thread'];
			$T->join();
			if ($T->returned) {
				$returned = $T->returned['value'];
			}
			unset($this->threads[$tid]);
			if ($returned) {
				return array('type' => 'string', 'value' => $returned);
			}
			return array('type' => 'bool', 'value' => true);
		}
		return array('type' => 'bool', 'value' => false);
	}
	
	
	private function c_lineIn($args) {
		if (isset($args[0])) {
			$prompt = $this->executor->evaluate($args[0]);
			echo $prompt['value'];
		}
		
		$line = trim(fgets(STDIN));
		
		return array('type' => 'string', 'value' => $line);
	}
	
	private function c_exec($args) {
		$cmd = $this->executor->evaluate($args[0]);
		$cmd = $cmd['value'];
		$out = "";
		exec($cmd, $out);
		return array('type' => 'string', 'value' => implode("\n", $out));
	}
	
	private function c_echo($args) {
		foreach ($args as $arg) {
			$a = $this->executor->evaluate($arg);
			$this->executor->shoutput($a['value']);
		}
		return array('type' => 'bool', 'value' => true);
	}
	
	private function c_getFile($args) {
		$value = array('type' => 'bool', 'value' => false);
			
		$a = $this->executor->evaluate($args[0]);
		if (is_string($a['value'])) {
			$r = file_get_contents($a['value']);
			if (is_string($r)) {
				$value = array('type' => 'string', 'value' => $r);
			}
		}
		
		return $value;
	}
	
	private function c_saveFile($args) {
		$value = array('type' => 'bool', 'value' => false);
			
		$f = $this->executor->evaluate($args[0]);
		$c = $this->executor->evaluate($args[1]);

		if ($f['value'] && is_string($f['value']) && is_string($c['value'])) {
			$r = file_put_contents($f['value'], $c['value']);
			if (is_bool($r)) {
				$value = array('type' => 'bool', 'value' => $r);
			}
		}
	}
	
	private function c_isset($args) {
		$value = array('type' => 'bool', 'value' => false);
		$a = false;
		try {
			$a = $this->executor->evaluate($args[0]);
		}
		catch (Exception $e) {
			return $value;
		}
		
		if ($a) {
			$value['value'] = true;
			return $value;
		}
	}
	
	private function c_dump($args) {
		foreach ($args as $arg) {
			$a = $this->executor->evaluate($arg);
			var_dump($a['value']);
		}
	}
	
	public function __destruct() {
		foreach ($this->threads as $id => $t) {
			$t['thread']->join();
		}
	}
}

