<?php

require_once 'errorcodes.php';
require_once 'langparserbase.php';

Class LangParser extends LangParserBase {
	private $data;
	private $scopeType;
	private $packagedir;
	
	public function __construct($data, $scopeType = 'main', $packagedir = false) {
		parent::__construct();
		$this->data = $data;
		$this->scopeType = $scopeType;
		if ($packagedir) {
			$this->packagedir = $packagedir;
		}
	}
	
	public function run() {
		return $this->rParseCode($this->data);
	}
	
	private function rParseCode(&$code, $ret = false) {
		if (!$ret) {
			$ret = array('code' => array(), 'functions' => array(), 'classes' => array(), 'threads' => array());
		}
		
		if ($code) {
			$code = trim($code);
			
			if ($this->parser->rNextIs($code, $this->lang->closeStructure)) {
				return $ret;
			}
			
			$lineType = $this->getLineType($code);
			$code = $lineType['code'];
			$markers = $lineType['markers'];
			$type = $lineType['type'];
			
			if ($type) {
				$st = $this->scopeType;
			
				switch ($type) {
					case 'include':
						$inc = $this->parseInclude($code, $markers);
						$ret = array_merge($ret, $inc);
						break;
					case 'package':
						$inc = $this->parsePackageInclude($code, $markers);
						$ret = array_merge($ret, $inc);
						break;
					case 'statement':
						$ret['code'][] = $this->parseStatement($code, $markers);
						break;
					case 'structure':
						$pst = $this->parseStructure($code, $markers);
						if ($pst['type'] == 'function') {
							$ret['functions'][] = $pst['details'];
						}
						else if ($pst['type'] == 'class') {
							$ret['classes'][] = $pst['details'];
						}
						else if ($pst['type'] == 'thread') {
							$ret['threads'][] = $pst['details'];
						}
						else {
							$ret['code'][] = array('structure' => $pst);
						}
						break;
					case 'function':
						$pst = $this->parseStructure($code, $markers, 'function');
						$ret['functions'][] = $pst['details'];
						break;
				}
				$code = trim($code);
				
				$this->scopeType = $st;
				
				if ($code) {
					return $this->rParseCode($code, $ret);
				}
				else {
					return $ret;
				}
			}
			else {
				$newlinepos = strpos($code, "\n");
				if ($newlinepos) {
					$emess = e_unknown_code_at.substr($code,0,$newlinepos);
				}
				else {
					$emess = e_unknown_code;
				}
				throw new Exception($emess);
			}
		}
		return $ret;
	}
	
	private function parseInclude(&$code, $markers) {
		$this->parser->rNextIs($code, $this->lang->preIncludeDeclarations);
		$delim = $this->parser->rNextIs($code, $this->lang->stringDelimiters);
		if ($delim) {
			$string = $this->parser->getString($code, $delim);
			
			if (file_exists($string)) {
				$f = file_get_contents($string);
				return $this->rParseCode($f);
			}
			else {
				throw new Exception(e_file_not_exists);
			}
		}
		else {
			throw new Exception(e_expecting_string);
		}
	}
	
	private function parsePackageInclude(&$code, $markers) {
		$this->parser->rNextIs($code, $this->lang->packageIncludeDeclarations);
		$delim = $this->parser->rNextIs($code, $this->lang->stringDelimiters);
		if ($delim) {		
			$string = $this->parser->getString($code, $delim);
			if ($this->packagedir) {
				$string = $this->packagedir . $string;
			}
			
			if (file_exists($string)) {
				$f = file_get_contents($string);
				return $this->rParseCode($f);
			}
			else {
				throw new Exception(e_file_not_exists);
			}
		}
		else {
			throw new Exception(e_expecting_string);
		}
	}
	
	private function rParseDeclaration(&$code) {
		$declare = array();
		$seq = $this->parser->rGetNextSequence($code);
		if ($seq) {
			$seq2 = $this->parser->getNextSequence($code);
			$declare['name'] = $seq;
			$declare['type'] = 'union';
			if ($seq2 && in_array($seq, $this->lang->types)) {
				$declare['name'] = $seq2;
				$declare['type'] = $seq;
				$code = trim(substr(trim($code), strlen($seq2)));
			}

			$akeys = array();
			while ($this->parser->rNextIs($code, $this->lang->openArray)) {
				$akeys[] = $this->rParseEvaluation($code);
				if (!$this->parser->rNextIs($code, $this->lang->closeArray)) {
					throw new Exception(e_expecting_array_close);
				}
			}
			
			$declare['arrayKeys'] = $akeys;
			$declare['newScope'] = true;
			return $declare;
		}
		return false;
	}
	
	private function parseStatement(&$code, $markers, $removesc = true) {
		if ($this->scopeType == 'function' && $this->parser->rNextIs($code, $this->lang->functionReturns)) {
			$evaluated = $this->rParseEvaluation($code);
			if ($removesc) {
				$this->parser->rNextIs($code, $this->lang->statementEnd); // remove the ";"
			}
			if ($evaluated) {
				return array('return' => $evaluated);
			}
			else {
				throw new Exception(e_expecting_expression);
			}
		}
		if ($this->parser->rNextIs($code, $this->lang->includeDeclarations)) {
			$evaluated = $this->rParseEvaluation($code);
			if ($removesc) {
				$this->parser->rNextIs($code, $this->lang->statementEnd); // remove the ";"
			}
			if ($evaluated) {
				return array('include' => $evaluated);
			}
			else {
				throw new Exception(e_expecting_expression);
			}
		}
	
		$setStatement = false;
		$onlyDeclare = false;
		
		$akeys = array();
		
		$seq = $this->parser->rGetNextSequence($code);
		$seq2 = false;
		$so = false;
		$so2 = false;
		if ($seq !== false) {
			$so = $this->parser->rNextIs($code, $this->lang->setOperators);
			if ($so) {
				$setStatement = true;
			}
			else {
				$seq2 = $this->parser->rGetNextSequence($code);
				if ($seq2 !== false) {
					$so2 = $this->parser->rNextIs($code, $this->lang->setOperators);
					if ($so2) {
						if (!in_array($seq, $this->lang->types)) {
							throw new Exception(e_invalid_type.$seq);
						}
						$setStatement = true;
					}
					else {
						$tcode = $code;
						while ($this->parser->rNextIs($tcode, $this->lang->openArray)) {
							$akeys[] = $this->rParseEvaluation($tcode);
							if (!$this->parser->rNextIs($tcode, $this->lang->closeArray)) {
								throw new Exception(e_expecting_array_close);
							}
						}
						
						if ($this->parser->rNextIs($tcode, $this->lang->setOperators)) {
							$code = $tcode;
							$setStatement = true;
							if (!in_array($seq, $this->lang->types)) {
								throw new Exception(e_invalid_type.$seq);
							}
						}
						else if (empty($akeys)) {
							$code = $seq2.' '.$code;
						}
					}
				}
				else {
					$tcode = $code;
					while ($this->parser->rNextIs($tcode, $this->lang->openArray)) {
						$akeys[] = $this->rParseEvaluation($tcode);
						if (!$this->parser->rNextIs($tcode, $this->lang->closeArray)) {
							throw new Exception(e_expecting_array_close);
						}
					}
					
					if ($this->parser->rNextIs($tcode, $this->lang->setOperators)) {
						$code = $tcode;
						$setStatement = true;
					}
					else if (empty($akeys)) {
						$code = $seq.' '.$code;
					}
				}
			}
		}
		
		if ($seq !== false && $this->parser->nextIs($code, $this->lang->statementEnd)) {
			if ($removesc) {
				$this->parser->rNextIs($code, $this->lang->statementEnd); // remove the ";"
			}
			$onlyDeclare = true;
		}

		$declare = array();
		
		if ($setStatement || $onlyDeclare) {
			$declare['name'] = $seq;
			$declare['type'] = 'union';
			if ($so2) {
				$declare['name'] = $seq2;
				$declare['type'] = $seq;
			}
			$declare['newScope'] = false;
			if ($markers['newScope']) {
				$declare['newScope'] = true;
			}
			$declare['arrayKeys'] = $akeys;
		}
		else {
			$seq = $this->parser->getNextSequence($code);
			if ($seq) {
				$opp = $this->parser->nextIs(substr($code, strlen($seq)), $this->lang->singleOperators);
				if ($opp) {
					$declare['name'] = $seq;
					$declare['type'] = 'union';
					$declare['newScope'] = false;
					if ($markers['newScope']) {
						$declare['newScope'] = true;
					}
					$declare['arrayKeys'] = $akeys;
					$setStatement = true;
				}
			}
			
		}
		
		if ($onlyDeclare) {
			return array('declare' => $declare);
		}

		$evaluated = $this->rParseEvaluation($code);
		
		if ($removesc) {
			$this->parser->rNextIs($code, $this->lang->statementEnd); // remove the ";"
		}
		if ($setStatement) {
			if ($evaluated) {
				return array('set' => array('declare' => $declare, 'value' => $evaluated));
			}
			else {
				throw new Exception(e_expecting_expression);
			}
		}
		else {
			if ($evaluated) {
				return array('evaluate' => $evaluated);
			}
		}
	}
	
	private function parseStructure(&$code, $markers, $type = false) {
		if ($type === false) {
			$type = $this->parser->rGetNextSequence($code);
		}
		
		if ($type) {
			switch ($type) {
				case 'if':
					return array('type' => 'if', 'details' => $this->parseIf($code, $markers));
					break;
				case 'for':
					return array('type' => 'for', 'details' => $this->parseFor($code, $markers));
					break;
				case 'func':
					return array('type' => 'function', 'details' => $this->parseFunction($code, $markers));
					break;
				case 'function':
					return array('type' => 'function', 'details' => $this->parseFunction($code, $markers));
					break;
				case 'class':
					return array('type' => 'class', 'details' => $this->parseClass($code, $markers));
					break;
				case 'thread':
					return array('type' => 'thread', 'details' => $this->parseThread($code, $markers));
					break;
			}
		}
		else {
			throw new Exception(e_unkown_type);
		}
	}
	
	private function rParseEvaluation(&$code) {
		if ($this->parser->rNextIs($code, $this->lang->openFunction)) {
			$parsed = $this->rParseEvaluation($code);
			if (!$this->parser->rNextIs($code, $this->lang->closeFunction)) {
				throw new Exception(e_expecting_closing_bracket);
			}
			return $parsed;
		}
		
		$two = $this->parser->rNextIs($code, $this->lang->threadWaitOperator);
		$seq = $this->parser->rGetNextSequence($code);
		
		$eval = array();

		if ($seq && $this->parser->rNextIs($code, $this->lang->openFunction)) {
			$neweval = array('functionCall' => array('name' => $seq, 'args' => array(), 'threadWait' => $two?true:false));
			$seq = $this->parser->getNextSequence($code);
			
			while (($this->parser->rNextIs($code, $this->lang->argumentSeperators) && !$this->parser->rNextIs($code, $this->lang->closeFunction)) || !$this->parser->rNextIs($code, $this->lang->closeFunction)) {
				$openFunc = false;
				if ($this->parser->rNextIs($code, $this->lang->openFunction)) {
					$openFunc = true;
				}
				
				$parsed = $this->rParseEvaluation($code);

				if ($openFunc && !$this->parser->rNextIs($code, $this->lang->closeFunction)) {
					throw new Exception(e_expecting_closing_bracket);
				}
				
				if (!empty($parsed)) {
					$neweval['functionCall']['args'][] = $parsed;
				}
				else {
					throw new Exception(e_expecting_function_argument);
				}
			}
			
			$eval[] = $neweval;
			
			$opp = $this->parser->rNextIs($code, $this->lang->operators);
			if ($opp) {
				$eval[] = array('operator' => $opp);
				$eval[] = $this->rParseEvaluation($code);
			}
		}
		else {
			if ($seq !== false) {
				$code = $seq.' '.$code;
				$value = $this->parser->rGetNextValue($code);
			}
			else {
				$value = $this->parser->rGetNextValue($code);
			}
			
			if ($value) {
				$eval[] = array('value' => $value);
			}
			else if ($this->parser->rNextIs($code, $this->lang->openArray)) {
				$arrv = $this->parseArray($code);
				$eval[] = array('value' => array('type' => 'array', 'value' => $arrv));
			}
			else if ($seq) {
				$code = trim(substr(trim($code), strlen($seq)));
				$akeys = array();
				while ($this->parser->rNextIs($code, $this->lang->openArray)) {
					$akeys[] = $this->rParseEvaluation($code);
					if (!$this->parser->rNextIs($code, $this->lang->closeArray)) {
						throw new Exception(e_expecting_array_close);
					}
				}
				$eval[] = array('variable' => array('name' => $seq, 'arrayKeys' => $akeys));
			}
			
			$sopp = $this->parser->rNextIs($code, $this->lang->singleOperators);
			if ($sopp) {
				$eval = array('operator' => $sopp);
			}
			
			$opp = $this->parser->rNextIs($code, $this->lang->operators);
			if ($opp) {
				$eval[] = array('operator' => $opp);
				$eval[] = $this->rParseEvaluation($code);
			}
		}
		return $eval;
	}
	
	private function parseArray(&$code) {
		$array = array();
		$key = 0;
		do {
			$item = $this->rParseEvaluation($code);
		
			if (!empty($item)) {
				if ($this->parser->rNextIs($code, $this->lang->arrayKey)) {
					$array[] = array('key' => $item, 'value' => $this->rParseEvaluation($code));
				}
				else if ($this->parser->rNextIs($code, $this->lang->argumentSeperators, false) || $key == 0) {
					$array[] = array('key' => array(array('value' => array('type' => 'int', 'value' => $key))), 'value' => $item);
					$key++;
				}
			}
		}
		while (!empty($item));
		if (!$this->parser->rNextIs($code, $this->lang->closeArray)) {
			throw new Exception(e_expecting_array_close);
		}
		return $array;
	}
	
	private function parseIf(&$code, $markers) {
		$struct = array('ifs' => array(), 'else' => array());
		
		$openbrackets = $this->parser->rNextIs($code, $this->lang->openFunction);
		
		$cond = $this->rParseEvaluation($code);
		
		if ($openbrackets) {
			if (!$this->parser->rNextIs($code, $this->lang->closeFunction)) {
				throw new Exception(e_expecting_closing_bracket);
			}
		}
		
		if (!$cond) {
			throw new Exception (e_expecting_if_condition);
		}
		if(!$this->parser->rNextIs($code, $this->lang->openStructure)) {
			throw new Exception (e_expecting_opening_structure);
		}
		$struct['ifs'][] = array('condition' => $cond, 'code' => $this->rParseCode($code));
		
		while($this->parser->rNextIs($code, $this->lang->elseIfStructure)) {
			$openbrackets = $this->parser->rNextIs($code, $this->lang->openFunction);
			$cond = $this->rParseEvaluation($code);
			if ($openbrackets) {
				if (!$this->parser->rNextIs($code, $this->lang->closeFunction)) {
					throw new Exception(e_expecting_closing_bracket);
				}
			}
			if (!$cond) {
				throw new Exception (e_expecting_if_condition);
			}
			if(!$this->parser->rNextIs($code, $this->lang->openStructure)) {
				throw new Exception (e_expecting_opening_structure);
			}
			$struct['ifs'][] = array('condition' => $cond, 'code' => $this->rParseCode($code));
		}
		
		if ($this->parser->rNextIs($code, $this->lang->elseStructure)) {
			if(!$this->parser->rNextIs($code, $this->lang->openStructure)) {
				throw new Exception (e_expecting_opening_structure);
			}
			$struct['else'] = $this->rParseCode($code);
		}
		
		return $struct;
	}
	
	private function parseFor(&$code, $markers) {
		$struct = array('type' => 'default', 'conditions' => array(), 'code' => array());
		
		$openbrackets = $this->parser->rNextIs($code, $this->lang->openFunction);
		
		$cond = $this->parseStatement($code, array('threadWait' => false, 'newScope' => false), false);
		
		if ($cond && isset($cond['evaluate'])) {
			$struct['type'] = 'array';
			$struct['conditions'] = array('array' => $cond['evaluate'], 'key' => array(), 'value' => array());

			if (!$this->parser->rNextIs($code, $this->lang->arrayKey)) {
				//throw expecting array separator
			}
			
			$cond = $this->parseStatement($code, array('threadWait' => false, 'newScope' => false));
			if (!isset($cond['evaluate']) || empty($cond['evaluate'])) {
				//throw expecting declaration
			}
			
			if ($this->parser->rNextIs($code, $this->lang->arrayKey)) {
				$struct['conditions']['key'] = $cond['evaluate'];
				
				$cond = $this->parseStatement($code, array('threadWait' => false, 'newScope' => false));
				if (!isset($cond['evaluate']) || empty($cond['evaluate'])) {
					//throw expecting declaration
				}
				$struct['conditions']['value'] = $cond['evaluate'];
			}
			else {
				$struct['conditions']['key'] = false;
				$struct['conditions']['value'] = $cond['evaluate'];
			}
		}
		else {
			$struct['conditions'] = array('declares' => array(), 'conditions' => array(), 'ends' => array());
			
			if ($cond) {
				$struct['conditions']['declares'][] = $cond;
			}
			
			do {
				$this->parser->rNextIs($code, $this->lang->argumentSeperators, false);
				$cond = $this->parseStatement($code, array('threadWait' => false, 'newScope' => false), false);
				if ($cond !== null) {
					$struct['conditions']['declares'][] = $cond;
				}
			}
			while ($cond !== null);
			
			if (!$this->parser->rNextIs($code, $this->lang->statementEnd)) {
				//throw expecting for separator
			}

			$cond = $this->rParseEvaluation($code);
			$struct['conditions']['conditions'][] = $cond;
			if (empty($cond)) {
				//throw expecting declaration
			}
			
			do {
				$this->parser->rNextIs($code, $this->lang->argumentSeperators, false); //remove arg separators
				$cond = $this->rParseEvaluation($code);
				if (!empty($cond)) {
					$struct['conditions']['conditions'][] = $cond;
				}
			}
			while (!$this->parser->rNextIs($code, $this->lang->statementEnd) && !$this->parser->nextIs($code, $this->lang->closeFunction) && !$this->parser->nextIs($code, $this->lang->openStructure));
			
			$markers = $this->checkForMarkers($code);
			$code = $markers['code'];
			unset($markers['code']);
			
			$cond = $this->parseStatement($code, $markers, false);
			
			$struct['conditions']['ends'][] = $cond;

			do {
				$this->parser->rNextIs($code, $this->lang->argumentSeperators, false);
				$markers = $this->checkForMarkers($code);
				$code = $markers['code'];
				unset($markers['code']);
				
				$cond = $this->parseStatement($code, $markers, false);
				$struct['conditions']['ends'][] = $cond;
			}
			while (!$this->parser->rNextIs($code, $this->lang->statementEnd) && !$this->parser->nextIs($code, $this->lang->closeFunction) && !$this->parser->nextIs($code, $this->lang->openStructure));
		}
		
		if ($openbrackets) {
			if (!$this->parser->rNextIs($code, $this->lang->closeFunction)) {
				throw new Exception(e_expecting_closing_bracket);
			}
		}
		
		if(!$this->parser->rNextIs($code, $this->lang->openStructure)) {
			throw new Exception (e_expecting_opening_structure);
		}
		$struct['code'] = $this->rParseCode($code);
		
		return $struct;
	}
	
	private function parseFunction(&$code, $markers) {
		$this->scopeType = 'function';
		
		$name = $this->parser->rGetNextSequence($code);
		if (!$name) {
			throw new Exception(e_expecting_function_name);
		}
		$struct = array('name' => $name, 'newScope' => false, 'args' => array(), 'code' => array());
		if ($markers['newScope']) {
			$struct['scope'] = true;
		}
		
		
		$openbrackets = $this->parser->rNextIs($code, $this->lang->openFunction);
		
		do {
			$arg = $this->rParseDeclaration($code);
			if ($arg) {
				$struct['args'][] = $arg;
			}
		}
		while($arg);
		
		if ($openbrackets) {
			if (!$this->parser->rNextIs($code, $this->lang->closeFunction)) {
				throw new Exception(e_expecting_closing_bracket);
			}
		}
		
		if(!$this->parser->rNextIs($code, $this->lang->openStructure)) {
			throw new Exception (e_expecting_opening_structure);
		}
		$struct['code'] = $this->rParseCode($code);
		
		return $struct;
	}
	
	private function parseClass($code, $markers) {
		$this->scopeType = 'class';
		
	}
	
	private function parseThread($code, $markers) {
		$this->scopeType = 'thread';
	}
	
}


