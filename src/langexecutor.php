<?php

require_once 'errorcodes.php';
require_once 'scope.php';
require_once 'langparser.php';
require_once 'corefunctions.php';

Class LangExecutor {
	public $data;
	public $scope;
	public $scopeID;
	public $coreFunctions;
	
	public $output;
	
	public function __construct($data) {
		$this->data = $data;
		$this->scopeID = 1;
		$this->coreFunctions = new CoreFunctions($this);
	}
	
	public function getOutput() {
		return $this->output;
	}
	
	public function shoutput($str, $add = true) {
		if ($add) {
			$this->output .= $str;
		}
		echo $str;
	}
	
	public function run() {
		if (!$this->scope) {
			$this->scope = new Scope($this->scopeID);
		}
		
		$this->runCode($this->data);
		
		return $this->getOutput();
	}
	
	public function runCode($code) {
		foreach ($code['functions'] as $func) {
			$this->scope->addFunction($func['name'], $func['args'], $func['code'], $func['newScope']);
		}
		
		foreach ($code['code'] as $line) {
			if (isset($line['set'])) {
				$this->runSet($line['set']);
			}
			else if (isset($line['evaluate'])) {
				$this->evaluate($line['evaluate']);
			}
			else if (isset($line['structure'])) {
				$ran = false;
				switch ($line['structure']['type']) {
					case 'if':
						$ran = $this->runIf($line['structure']['details']);
						break;
					case 'for':
						$ran = $this->runFor($line['structure']['details']);
						break;
				}
				
				if ($ran) {
					if (isset($ran['return'])) {
						return $ran;
					}
				}
			}
			else if (isset($line['return'])) {
				return array('return' => $this->evaluate($line['return']));
			}
			else if (isset($line['include'])) {
				$i = $this->evaluate($line['include']);
				$i = $i['value'];
				if (is_string($i)) {
					chdir(dirname(__FILE__));
					if (file_exists($i)) {
						$f = file_get_contents($i);
						try {
							$P = new LangParser($f);
							$parsed = $P->run();
						}
						catch (Exception $e) {
							throw new Exception('Parse Error: '. $e->getMessage());
						}
						$this->runCode($parsed);
					}
					else {
						throw new Exception(e_file_not_exists);
					}
				}
				else {
					throw new Exception(e_expecting_string);
				}
			}
		}
		
	}
	
	/*
	func strpos str find {
  flen = strlen(find)
  prev = ""

  for (str : k : v) {
    prev = prev + v
    if strlen(prev) > flen {
      tprev = ""
      for (prev : k2 : v2) {
        if (k2 < flen) {
          tprev = tprev + v2
        }
      }
      prev = tprev
echo(tprev+ " ")
    }
    if prev == find {
      return k
    }
  }
  return false
}

func substr str s e {
  ret = ""
  for (str : k : v) {
    if (k > s) {
      ret = ret + v
    }
  }
  return ret;
}
	*/
	
	public function runFor($details) {
		$ran = false;
		if ($details['type'] == 'array') {
			$for = $this->evaluate($details['conditions']['array']);
			if (is_string($for['value'])) {
				$for['type'] = 'union';
				$for['value'] = str_split($for['value']);
			}			
			if (!is_array($for['value'])) {
				//throw expecting array 
			}
			
			if (!empty($details['conditions']['key'])) {
				foreach ($for['value'] as $k => $v) {
					$this->scope->addVariable($details['conditions']['key'][0]['variable']['name'], 'union', $k, true);
					$this->scope->addVariable($details['conditions']['value'][0]['variable']['name'], 'union', $v, true);
					
					$ran = $this->runCode($details['code']);
					if (isset($ran['return'])) {
						return $ran;
					}
				}
			}
			else {
				foreach ($for['value'] as $v) {
					$this->scope->addVariable($details['conditions']['value'][0]['variable']['name'], 'union', $v, true);
					$ran = $this->runCode($details['code']);
					if (isset($ran['return'])) {
						return $ran;
					}
				}
			}
		}
		else if ($details['type'] == 'default') {
			foreach ($details['conditions']['declares'] as $declare) {
				if (isset($declare['set'])) {
					$this->runSet($declare['set']);
				}
				else if (isset($declare['evaluate'])) {
					$this->evaluate($declare['evaluate']);
				}
				else if (isset($declare['declare'])) {
					$this->scope->addVariable($declare['declare']['name'], $declare['declare']['type'], null, $declare['newScope']);
				}
			}
			
			do {
				$condok = true;
				foreach ($details['conditions']['conditions'] as $cond) {
					$eval = $this->evaluate($cond);
					if (!$eval['value']) {
						$condok = false;
						break;
					}
				}
				
				if ($condok) {
					$ran = $this->runCode($details['code']);
					if (isset($ran['return'])) {
						return $ran;
					}
					foreach ($details['conditions']['ends'] as $declare) {
						if (isset($declare['set'])) {
							$this->runSet($declare['set']);
						}
						else if (isset($declare['evaluate'])) {
							$this->evaluate($declare['evaluate']);
						}
						else if (isset($declare['declare'])) {
							$this->scope->addVariable($declare['declare']['name'], $declare['declare']['type'], null, $declare['newScope']);
						}
					}
				}
			}
			while ($condok);
		}
		return $ran;
	}
	
	public function runIf($details) {
		$matched = false;
		$ran = false;
		foreach ($details['ifs'] as $if) {
			$cond = $this->evaluate($if['condition']);
			if ($cond['value']) {
				$matched = true;
				$ran = $this->runCode($if['code']);
			}
		}
		if (!$matched && !empty($details['else'])) {
			$ran = $this->runCode($details['else']);
		}
		return $ran;
	}
	
	public function runSet($code) {
		if (isset($code['value']['operator']) && ($code['value']['operator'] == '++' || $code['value']['operator'] == '--')) {
			$var = $this->scope->getVariable($code['declare']['name']);
			$value = $var;

			if (!is_int($value['value']) || !is_float($value['value'])) {
				//throw invalid operator for type
			}
			
			switch ($code['value']['operator']) {
				case '++':
					$value['value']++;
					break;
				case '--':
					$value['value']--;
					break;
			}
		}
		else {
			$value = $this->evaluate($code['value']);
		}
		if (is_array($value['value'])) {
			$ret = array();
			foreach ($value['value'] as $arow) {
				$key = $this->evaluate($arow['key']);
				if (!is_string($key['value']) && !is_int($key['value'])) {
					throw new Exception(e_invalid_index);
				}
				$val = $this->evaluate($arow['value']);
				$ret[$key['value']] = $val['value'];
			}
			$value = array('value' => $ret);
		}
		$akeys = array();
		if (!empty($code['declare']['arrayKeys'])) {
			foreach($code['declare']['arrayKeys'] as $akey) {
				$key = $this->evaluate($akey);
				if (!is_string($key['value']) && !is_int($key['value'])) {
					throw new Exception(e_invalid_index);
				}
				$akeys[] = $key;
			}
		}
		$this->scope->addVariable($code['declare']['name'], $code['declare']['type'], $value['value'], $code['declare']['newScope'], $akeys);
	}
	
	public function evaluate($code) {
		$value = array('type' => null, 'value' => null);
		foreach ($code as $pos => $bit) {
			if (isset($bit['functionCall'])) {
				if ($this->coreFunctions->functionExists($bit['functionCall']['name'])) {
					$value = $this->coreFunctions->execute($bit['functionCall']['name'], $bit['functionCall']['args']);
				}				
				else {
					//var_dump($bit);
					$func = $this->scope->getFunction($bit['functionCall']['name']);
					$this->scope->increaseScope();
					
					if (count($bit['functionCall']['args']) > count($func['arguments'])) {
						throw new Exception(e_too_many_function_args);
					}
					
					foreach ($func['arguments'] as $pos => $arg) {
						if (isset($bit['functionCall']['args'][$pos])) {
							$ad = $this->evaluate($bit['functionCall']['args'][$pos]);
							
							if ($arg['type'] == 'union') {
								$arg['type'] = $ad['type'];
							}
							if ($arg['type'] !== $ad['type']) {
								throw new Exception(e_invalid_argument_type);
							}
							$this->scope->addVariable($arg['name'], $arg['type'], $ad['value'], $arg['newScope']);
						}
						else {
							if ($arg['type'] != 'union') {
								throw new Exception(e_missing_required_argument);
							}
							$this->scope->addVariable($arg['name'], $arg['type'], null, $arg['newScope']);
						}
					}
					$result = $this->runCode($func['code']);
					
					$this->scope->decreaseScope();
					if (isset($result['return'])) {
						$value = $result['return'];
					}
					else {
						$value = array('type' => 'bool', 'value' => true);
					}
				}
			}
			else if (isset($bit['variable'])) {
				$var = $this->scope->getVariable($bit['variable']['name']);
				if (!empty($bit['variable']['arrayKeys'])) {
					if (!is_array($var['value'])) {
						//throw not an array
					}
					foreach ($bit['variable']['arrayKeys'] as $ak) {
						$ak = $this->evaluate($ak);
						if (!is_string($ak['value']) && !is_int($ak['value'])) {
							throw new Exception(e_invalid_index);
						}
						
						if (isset($var['value'][$ak['value']])) {
							$var['value'] = $var['value'][$ak['value']];
						}
						else {
							throw new Exception(e_undefined_index);
						}
					}
					
				}
				$value = $var;
			}
			else if (isset($bit['value'])) {
				$value = $bit['value'];
			}
			else if (isset($bit['operator'])) {
				if ($bit['operator'] == '++' || $bit['operator'] == '--') {
					$value = $this->evaluateSingleOperation($value, $bit['operator']);
				}
				else {
					if (isset($code[$pos+1]) && $bit['operator'] == '-' && $value['type'] === null) {
						
					}
					if (!isset($code[$pos+1]) || ($value['type'] === null && $bit['operator'] != '-')) {
						throw new Exception(e_operator_needs_2_args);
					}
					$nextValue = $this->evaluate($code[$pos+1]);
					$value = $this->evaluateOperation($value, $nextValue, $bit['operator']);
				}
			}
		}
		return $value;
	}
	
	public function evaluateSingleOperation($value, $operation) {
		switch($operation) {
			case '++':
				if (is_int($value['value'])) {
					$value['value']++;
					return $value;
				}
				else {
					throw new Exception(e_incompatible_operator_types);
				}
				break;
			case '--':
				if (is_int($value['value'])) {
					$value['value']--;
					return $value;
				}
				else {
					throw new Exception(e_incompatible_operator_types);
				}
				break;
		}
	}
	
	public function evaluateOperation($value, $nextValue, $operation) {
		switch($operation) {
			case '+':
				if (is_bool($value['value']) && is_bool($nextValue['value'])) {
					if ($value['value'] && $nextValue['value']) {
						return array('type' => 'bool', 'value' => true);
					}
					else {
						return array('type' => 'bool', 'value' => false);
					}
				}
				if ($value['type'] == 'bool' || $value['type'] == 'int' || $value['type'] == 'float' || ($value['type'] == 'union' && (is_float($value['value']) || is_int($value['value']) || is_bool($value['value'])))) {
					if ($nextValue['type'] == 'int' || $nextValue['type'] == 'float' || $nextValue['type'] == 'bool' || $nextValue['type'] == 'string' || ($nextValue['type'] == 'union' && (is_bool($nextValue['value']) || is_string($nextValue['value']) || is_float($nextValue['value']) || is_int($nextValue['value'])))) {
						if ($nextValue['type'] == 'string' || ($nextValue['type'] == 'union' && is_string($value['value']))) {
							if (is_bool($value['value'])) {
								$value['value'] = $value['value'] ? '1' : '0';
							}
							if (is_bool($nextValue['value'])) {
								$value['value'] = $value['value'] ? '1' : '0';
							}
							return array('type' => 'string', 'value' => $value['value'] . $nextValue['value']);
						}
						else {
							$t = 'int';
							$v = $value['value'] + $nextValue['value'];
							if ($value['type'] == 'float' || $nextValue['type'] == 'float' || ($value['type'] == 'union' && is_float($value['value'])) || ($nextValue['type'] == 'union' && is_float($nextValue['value']))) {
								$t = 'float';
								$v = (float) $v;
							}
							else {
								$v = (int) $v;
							}
							return array('type' => $t, 'value' => $v);
						}
					}
					else {
						throw new Exception(e_incompatible_operator_types);
					}
				}
				else if (($value['type'] == 'string' || ($value['type'] == 'union' && is_string($value['value']))) && ($nextValue['type'] == 'int' || $nextValue['type'] == 'float' || $nextValue['type'] == 'string' || $nextValue['type'] == 'bool' || ($nextValue['type'] == 'union' && (is_bool($nextValue['value']) || is_string($nextValue['value']) || is_float($nextValue['value']) || is_int($nextValue['value']))))) {
					if (is_bool($value['value'])) {
						$value['value'] = $value['value'] ? '1' : '0';
					}
					if (is_bool($nextValue['value'])) {
						$value['value'] = $value['value'] ? '1' : '0';
					}
					return array('type' => 'string', 'value' => $value['value'] . $nextValue['value']);
				}
				else {
					throw new Exception(e_incompatible_operator_types);
				}
				break;
			case '-':
				if ($value['type'] === null && ($nextValue['type'] == 'int' || $nextValue['type'] == 'float' || ($nextValue['type'] == 'union' && (is_float($nextValue['value']) || is_int($nextValue['value']))))) {
					$t = 'int';
					$v = 0 - $nextValue['value'];
					if ($nextValue['type'] == 'float' || ($nextValue['type'] == 'union' && is_float($nextValue['value']))) {
						$t = 'float';
						$v = (float) $v;
					}
					else {
						$v = (int) $v;
					}
					return array('type' => $t, 'value' => $v);
				}
				else if (($value['type'] == 'int' || $value['type'] == 'float' || ($value['type'] == 'union' && (is_float($value['value']) || is_int($value['value'])))) && ($nextValue['type'] == 'int' || $nextValue['type'] == 'float' || ($nextValue['type'] == 'union' && (is_float($nextValue['value']) || is_int($nextValue['value']))))) {
					$t = 'int';
					$v = $value['value'] - $nextValue['value'];
					if ($value['type'] == 'float' || $nextValue['type'] == 'float' || ($value['type'] == 'union' && is_float($value['value'])) || ($nextValue['type'] == 'union' && is_float($nextValue['value']))) {
						$t = 'float';
						$v = (float) $v;
					}
					else {
						$v = (int) $v;
					}
					return array('type' => $t, 'value' => $v);
				}
				else {
					throw new Exception(e_incompatible_operator_types);
				}
				break;
			case '*':
				if (($value['type'] == 'int' || $value['type'] == 'float' || ($value['type'] == 'union' && (is_float($value['value']) || is_int($value['value'])))) && ($nextValue['type'] == 'int' || $nextValue['type'] == 'float' || ($nextValue['type'] == 'union' && (is_float($nextValue['value']) || is_int($nextValue['value']))))) {
					$t = 'int';
					$v = $value['value'] * $nextValue['value'];
					if ($value['type'] == 'float' || $nextValue['type'] == 'float' || ($value['type'] == 'union' && is_float($value['value'])) || ($nextValue['type'] == 'union' && is_float($nextValue['value']))) {
						$t = 'float';
						$v = (float) $v;
					}
					else {
						$v = (int) $v;
					}
					return array('type' => $t, 'value' => $v);
				}
				else {
					throw new Exception(e_incompatible_operator_types);
				}
				break;
			case '/':
				if (($value['type'] == 'int' || $value['type'] == 'float' || ($value['type'] == 'union' && (is_float($value['value']) || is_int($value['value'])))) && ($nextValue['type'] == 'int' || $nextValue['type'] == 'float' || ($nextValue['type'] == 'union' && (is_float($nextValue['value']) || is_int($nextValue['value']))))) {
					$t = 'int';
					$v = $value['value'] / $nextValue['value'];
					if ($value['type'] == 'float' || $nextValue['type'] == 'float' || ($value['type'] == 'union' && is_float($value['value'])) || ($nextValue['type'] == 'union' && is_float($nextValue['value']))) {
						$t = 'float';
						$v = (float) $v;
					}
					else {
						$v = (int) $v;
					}
					return array('type' => $t, 'value' => $v);
				}
				else {
					throw new Exception(e_incompatible_operator_types);
				}
				break;
			case '^':
				if (($value['type'] == 'int' || $value['type'] == 'float' || ($value['type'] == 'union' && (is_float($value['value']) || is_int($value['value'])))) && ($nextValue['type'] == 'int' || $nextValue['type'] == 'float' || ($nextValue['type'] == 'union' && (is_float($nextValue['value']) || is_int($nextValue['value']))))) {
					$t = 'int';
					$v = $value['value'] * $nextValue['value'];
					if ($value['type'] == 'float' || $nextValue['type'] == 'float' || ($value['type'] == 'union' && is_float($value['value'])) || ($nextValue['type'] == 'union' && is_float($nextValue['value']))) {
						$t = 'float';
						$v = (float) $v;
					}
					else {
						$v = (int) $v;
					}
					return array('type' => $t, 'value' => $v);
				}
				else {
					throw new Exception(e_incompatible_operator_types);
				}
				break;
			case '===':
				if ($value['value'] === $nextValue['value']) {
					return array('type' => 'bool', 'value' => true);
				}
				else {
					return array('type' => 'bool', 'value' => false);
				}
				break;
			case '==':
				if ($value['value'] == $nextValue['value']) {
					return array('type' => 'bool', 'value' => true);
				}
				else {
					return array('type' => 'bool', 'value' => false);
				}
				break;
			case '>==':
				if (gettype($value['value']) == gettype($nextValue['value']) && $value['value'] >= $nextValue['value']) {
					return array('type' => 'bool', 'value' => true);
				}
				else {
					return array('type' => 'bool', 'value' => false);
				}
				break;
			case '<==':
				if (gettype($value['value']) == gettype($nextValue['value']) && $value['value'] <= $nextValue['value']) {
					return array('type' => 'bool', 'value' => true);
				}
				else {
					return array('type' => 'bool', 'value' => false);
				}
				break;
			case '>=':
				if ($value['value'] >= $nextValue['value']) {
					return array('type' => 'bool', 'value' => true);
				}
				else {
					return array('type' => 'bool', 'value' => false);
				}
				break;
			case '<=':
				if ($value['value'] <= $nextValue['value']) {
					return array('type' => 'bool', 'value' => true);
				}
				else {
					return array('type' => 'bool', 'value' => false);
				}
				break;
			case '>':
				if ($value['value'] > $nextValue['value']) {
					return array('type' => 'bool', 'value' => true);
				}
				else {
					return array('type' => 'bool', 'value' => false);
				}
				break;
			case '<':
				if ($value['value'] < $nextValue['value']) {
					return array('type' => 'bool', 'value' => true);
				}
				else {
					return array('type' => 'bool', 'value' => false);
				}
				break;
			case '&&':
				if ($value['value'] && $nextValue['value']) {
					return array('type' => 'bool', 'value' => true);
				}
				else {
					return array('type' => 'bool', 'value' => false);
				}
				break;
			case '&':
				if ($value['value'] & $nextValue['value']) {
					return array('type' => 'bool', 'value' => true);
				}
				else {
					return array('type' => 'bool', 'value' => false);
				}
				break;
			case '||':
				if ($value['value'] || $nextValue['value']) {
					return array('type' => 'bool', 'value' => true);
				}
				else {
					return array('type' => 'bool', 'value' => false);
				}
				break;
		}

		return array('type' => 'bool', 'value' => false);
	}
	
}

