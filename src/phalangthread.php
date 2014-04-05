<?php 
class BangThread
{
	private $stage = 0;
	private $_class = false;
	private $runfunc;
	
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
	
	public function start($vars = null) {
		$this->stage = 1;
		clr_create_thread([$this, 'inthread'], $vars);
	}
	
	public function join() {
		while ($this->stage == 1) {
		}
	}

    private function inthread($vars)
    {
		if ($this->_class) {
			$this->returned = call_user_func(array($this->_class, $this->runfunc), $vars);
		}
		else {
			$this->returned = call_user_func($this->runfunc, $vars);
		}
		$this->stage = 0;
    }
}
