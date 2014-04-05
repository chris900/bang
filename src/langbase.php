<?php

require_once 'langchecker.php';
require_once 'langparser.php';
require_once 'langexecutor.php';

Class LangBase {
	protected $bangdir;
	protected $compileinfo;
	protected $compiledir;
	protected $packagedir;
	
	public $output;
	public $options;
	
	public function __construct() {
		
	}
	
	public function check($data) {
		try {
			$C = new LangChecker($data);
			return $C->run();
		}
		catch (Exception $e) {
			throw new Exception('Error: '. $e->getMessage());
		}
	}
	
	public function parse($data) {
		try {
			$P = new LangParser($data, 'main', $this->packagedir);
			return $P->run();
		}
		catch (Exception $e) {
			throw new Exception('Parse Error: '. $e->getMessage());
		}
	}
	
	public function execute($data) {
		$E = false;
		try {
			$E = new LangExecutor($data);
			return $E->run();
		}
		catch (Exception $e) {
			if ($E) {
				$this->output = $E->getOutput();
			}
			throw new Exception('Runtime Error: '. $e->getMessage());
		}
	}
	
	
	public function hasOption($opp) {
		return in_array($opp, $this->options);
	}
	
	public function addCompiled($filename, $hash, $parsed) {
		$compfilename = md5($filename).".compiled";
		
		$infoline = false;
		$lines = explode("\n", file_get_contents($this->compileinfo));
		$prevlines = array();
		$afterlines = array();
		foreach ($lines as $line) {
			$fd = trim(substr($line, 0, strpos($line, "#")-1));
			if ($fd == $filename) {
				$infoline = $line;
			}
			else {
				if ($infoline) {
					$afterlines[] = $line;
				}
				else {
					$prevlines[] = $line;
				}
			}
		}
		
		if ($infoline) {
			//change
			$compiled = serialize($parsed);
			file_put_contents($this->compiledir . $compfilename, $compiled);
			$newline = $filename . " # " . $hash . " @ " . $compfilename;
			$prevlines[] = $newline;
			$merged = array_merge($prevlines, $afterlines);
			$savefile = implode("\n", $merged);
			file_put_contents($this->compileinfo, $savefile);
		}
		else {
			// add new
			$compiled = serialize($parsed);
			file_put_contents($this->compiledir . $compfilename, $compiled);
			$newline = $filename . " # " . $hash . " @ " . $compfilename;
			$lines[] = $newline;
			$savefile = implode("\n", $lines);
			file_put_contents($this->compileinfo, $savefile);
		}
	}
	public function getCompiledHash($filename) {
		$lines = explode("\n", file_get_contents($this->compileinfo));
		foreach ($lines as $line) {
			$fd = trim(substr($line, 0, strpos($line, "#")-1));
			if ($fd == $filename) {
				$hash = trim(substr($line, strpos($line, "#")+1, strpos($line, '@')-1-strpos($line, "#")));
				return $hash;
			}
		}
		return false;
	}
	public function getCompiled($filename) {
		$lines = explode("\n", file_get_contents($this->compileinfo));
		foreach ($lines as $line) {
			$fd = trim(substr($line, 0, strpos($line, "#")-1));
			if ($fd == $filename) {
				$compfile = trim(substr($line, strpos($line, "@")+1));
				$file = file_get_contents($this->compiledir . $compfile);
				return unserialize($file);
			}
		}
		return false;
	}
	
}

