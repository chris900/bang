<?php

require_once 'errorcodes.php';
require_once 'language.php';
require_once 'parser.php';

Class LangParserBase {
	public $lang;
	public $parser;
	
	public function __construct() {
		$this->lang = new Language();
		$this->parser = new Parser();
	}

	public function getLineType($code) {
		$type = false;
		
		$markers = $this->checkForMarkers($code);
		$code = $markers['code'];
		unset($markers['code']);

		if (in_array($this->parser->getNextChars($code), $this->lang->preIncludeDeclarations)) {
			$type = 'include';
		}
		else if (in_array($this->parser->getNextChars($code), $this->lang->packageIncludeDeclarations)) {
			$type = 'package';
		}
		if (in_array($this->parser->getNextSequence($code), $this->lang->includeDeclarations)) {
			$type = 'statement';
		}
		else if (in_array($this->parser->getNextSequence($code), $this->lang->functionReturns)) {
			$type = 'statement';
		}
		else if (in_array($this->parser->getNextSequence($code), $this->lang->structureNames)) {
			$type = 'structure';
		}
		else {
			$tcode = $code;
			$bits = array();
			
			do {
				$seq = $this->parser->getNextSequence($tcode);
				if ($seq) {
					$bits[] = trim(substr($tcode, 0, strlen($seq)));
					$tcode = trim(substr($tcode, strlen($seq)));
				}
			}
			while ($seq);
			
			$bitslen = count($bits);
			
			if ($this->parser->nextIs($tcode, $this->lang->openStructure)) {
				$type = 'function';
			}
			else if ($this->parser->nextIs($tcode, $this->lang->openArray)) {
				$type = 'statement';
			}
			else if ($this->parser->nextIs($tcode, $this->lang->openFunction) && $bitslen == 1) {
				$type = 'statement';
			}
			else if ($this->parser->nextIs($tcode, $this->lang->statementEnd) && ($bitslen == 1 || ($bitslen == 2 && in_array($bits[0], $this->lang->types)))) {
				$type = 'statement';
			}
			else {
				if ($this->parser->nextIs($tcode, $this->lang->operators) && ($bitslen == 1 || ($bitslen == 2 && in_array($bits[0], $this->lang->types)))) {
					$type = 'statement';
				}	
			}
			
		}
		
		return array ('type' => $type, 'markers' => $markers, 'code' => $code);
	}
	
	public function checkForMarkers($code) {
		$newScope = false;
		$threadWait = false;

		if ($code) {				
			foreach ($this->lang->scopeDeclarations as $sd) {
				if (substr($code, 0, strlen($sd)) == $sd) {
					$code = trim(substr($code, strlen($sd)));
					$newScope = true;
					break;
				}
			}
			foreach ($this->lang->threadWaitOperator as $wo) {
				if (substr($code, 0, strlen($wo)) == $wo) {
					$code = trim(substr($code, strlen($wo)));
					$threadWait = true;
					break;
				}
			}
			foreach ($this->lang->scopeDeclarations as $sd) {
				if (substr($code, 0, strlen($sd)) == $sd) {
					$code = trim(substr($code, strlen($sd)));
					$newScope = true;
					break;
				}
			}
		}
		return array('newScope' => $newScope, 'threadWait' => $threadWait, 'code' => $code);
	}
	
}

