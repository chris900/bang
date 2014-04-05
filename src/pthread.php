<?php 

class pThread extends Thread {
	private $_class = false;
	private $runfunc;
	private $vars = null;
	
	public $returned = null;

	public function __construct($run, $clfunc = false) {
		if ($clfunc) {
			$this->_class = $run;
			$this->runfunc = $clfunc;
		}
		else {
			$this->runfunc = $run;
		}
	}
	
	public function setVars($vars) {
		$this->vars = $vars;
	}

	public function run() {
		if ($this->_class) {
			$this->returned = call_user_func(array($this->_class, $this->runfunc), $this->vars);
		}
		else {
			$this->returned = call_user_func($this->runfunc, $this->vars);
		}
	}
}

class BangThread {
	private $stage = 0;
	private $thread = false;
	
	public $returned = null;
	
    public function __construct($run, $clfunc = false) {
		$this->thread = new pThread($run, $clfunc);
    }
	
	public function start($vars = null) {
		if ($this->stage == 0) {
			$this->thread->setVars($vars);
			$this->thread->start();
			$this->stage = 1;
		}
	}
	
	public function join() {
		if ($this->stage > 0) {
			$this->thread->join();
			$this->returned = $this->thread->returned;
			$this->stage = 0;
		}
	}
}


