<?php

require_once 'errorcodes.php';

Class Scope {
	public $savedScopes = array();
	public $scope = array();
	private $scopeID;

	public function __construct($id = 0) {
		$this->scopeID = $id;
		$this->initScope();
	}
	
	public function initScope() {
		$newscope = $this->getScopeFormat();
		$this->scope[$this->scopeID] = $newscope;
	}
	
	public function getScope() {
		return $this->scope;
	}
	
	public function getCurrentID() {
		return $this->scopeID;
	}
	
	public function setScope($id) {
		$this->scope = $this->savedScopes[$id];
	}
	
	public function saveScope($id) {
		$this->savedScopes[$id] = $this->scope;
	}
	
	public function increaseScope() {
		$this->scopeID++;
		$this->initScope();
		return $this->scope[$this->scopeID];
	}
	
	public function decreaseScope() {
		if ($this->scopeID > 1) {
			unset($this->scope[$this->scopeID]);
			$this->scopeID--;
			return $this->scope[$this->scopeID];
		}
		else {
			throw new Exception(e_scope_below_zero);
		}
	}
	
	private function setArrayValueByKeys(&$array, $val, $keys) {
		foreach ($keys as $key) {
			if (!isset($array[$key['value']])) {
				$array[$key['value']] = array();
			}
			if (count($keys) == 1) {
				$array[$key['value']] = $val;
			}
			else {
				$this->setArrayValueByKeys($array, $val, $keys);
			}
			break;
		}
	}
	
	public function addVariable($name, $type, $val, $new = true, $akeys = array()) {
		if ($new) {
			if (!empty($akeys)) {
				//throw error
			}
			$this->scope[$this->scopeID]['variables'][$name] = $this->getVariableFormat($type, $val, $this->scopeID);
		}
		else {
			$added = false;
			for ($i = $this->scopeID; $i >= 0; $i--) {
				if (isset($this->scope[$i]['variables'][$name])) {
					$added = true;
					if (!empty($akeys)) {
						if (is_array($this->scope[$i]['variables'][$name]['value'])) {
							$this->setArrayValueByKeys($this->scope[$i]['variables'][$name]['value'], $val, $akeys);
						}
						else {
							//throw variable not array
						}
					}
					else {
						$this->scope[$i]['variables'][$name] = $this->getVariableFormat($type, $val, $i);
					}
					break;
				}
			}
			if (!$added) {
				if (!empty($akeys)) {
					$this->setArrayValueByKeys($this->scope[$this->scopeID]['variables'][$name], $val, $akeys);
				}
				else {
					$this->scope[$this->scopeID]['variables'][$name] = $this->getVariableFormat($type, $val, $this->scopeID);
				}
			}
		}
	}
	
	public function addFunction($name, $args, $code, $new = true) {
		if ($new) {
			$this->scope[$this->scopeID]['functions'][$name] = $this->getFunctionFormat($args, $code, $this->scopeID);
		}
		else {
			$added = false;
			for ($i = $this->scopeID; $i >= 0; $i--) {
				if (isset($this->scope[$i]['functions'][$name])) {
					$added = true;
					$this->scope[$i]['functions'][$name] = $this->getFunctionFormat($args, $code, $i);
					break;
				}
			}
			if (!$added) {
				$this->scope[$this->scopeID]['functions'][$name] = $this->getFunctionFormat($args, $code, $this->scopeID);
			}
		}
	}
	
	public function addClass() {
	
	}
	
	public function addGlobalVariable($name, $type, $val, $scope = false) {
		$this->scope[0]['variables'][$name] = $this->getVariableFormat($type, $val, 0);
	}
	
	public function addGlobalFunction($name, $args, $code, $scope = false) {
		$this->scope[0]['functions'][$name] = $this->getFunctionFormat($args, $code, 0);
	}
	
	public function getVariable($name, $minScope = false) {
		if ($minScope === false) {
			$minScope = $this->scopeID;
		}
		$found = false;
		for ($i = $minScope; $i >= 0; $i--) {
			if (isset($this->scope[$i]['variables'][$name])) {
				$found = true;
				return $this->scope[$i]['variables'][$name];
			}
		}
		if (!$found) {
			throw new Exception(e_variable_not_found.$name);
		}
	}
	
	public function getFunction($name, $minScope = false) {
		if ($minScope === false) {
			$minScope = $this->scopeID;
		}
		$found = false;
		for ($i = $minScope; $i >= 0; $i--) {
			if (isset($this->scope[$i]['functions'][$name])) {
				$found = true;
				return $this->scope[$i]['functions'][$name];
			}
		}
		if (!$found) {
			throw new Exception(e_variable_not_found.$name);
		}
	}
	
	private function getScopeFormat() {
		return array(
			'variables' => array(),
			'functions' => array(),
			'classes' => array(),
		);
	}
	
	private function getVariableFormat($type, $val, $scope) {
		return array(
			'type' => $type,
			'value' => $val,
			'scope' => $scope,
		);
	}
	
	private function getFunctionFormat($args, $code, $scope) {
		return array(
			'arguments' => $args,
			'code' => $code,
			'scope' => $scope,
		);
	}
	
	
}

