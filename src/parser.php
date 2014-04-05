<?php

require_once 'errorcodes.php';
require_once 'language.php';

Class Parser {
	private $lang;
	
	public function __construct() {
		$this->lang = new Language();
	}
	
	public function findNext($string, $find, $ignoreStrings = true) {
		$chars = str_split($string);
		$findlen = strlen($find);
		$prev = '';
		for ($i = 0; $i <= strlen($string); $i++) {
			$c = $chars[$i];
			if (in_array($c, $this->lang->stringDelimiters)) {
				$slen = $this->getStringLen(substr($string, $pos));
				$i += $slen;
			}
			
			$prev .= $c;
			if (strlen($prev) > $findlen) {
				$prev = substr($prev, 1);
			}
			
			if ($prev == $find) {
				return $i - $findlen;
			}
		}
		return false;
	}
	
	public function GetTo($string, $find, $ignoreStrings = true) {
		$pos = $this->findNext($string, $find, $ignoreStrings);
		if ($pos !== false) {
			return substr($string, 0, $pos);
		}
		return false;
	}
	
	public function removeTo($string, $find, $ignoreStrings = true) {
		$pos = $this->findNext($string, $find, $ignoreStrings);
		if ($pos !== false) {
			return substr($string, $pos + strlen($find));
		}
		return false;
	}
	
	public function getNextChars($string) {
		$word = '';
		$wordstart = false;
		foreach (str_split($string) as $c) {
			if (!ctype_space($c)) {
				$wordstart = true;
				$word .= $c;
			}
			else if ($wordstart) {
				return $word;
			}
		}
		return false;
	}
	
	public function isValidSequence($string) {
		if ($string) {
			foreach (str_split($string) as $c) {
				if (!(!ctype_space($c) && (ctype_alnum($c) || in_array($c, $this->lang->validSequenceChars)))) {
					return false;
				}
			}
		}
		return true;
	}
	
	public function rGetNextSequence(&$string) {
		$seq = $this->getNextSequence($string);
		if ($seq !== false) {
			$string = trim(substr($string, strlen($seq)));
		}
		return $seq;
	}
	
	public function getNextSequence($string, $allowed = array()) {
		$word = '';
		$wordstart = false;
		foreach (str_split($string) as $c) {
			if (ctype_space($c) || ctype_alnum($c) || in_array($c, $this->lang->validSequenceChars) || in_array($c, $allowed)) {
				if (!ctype_space($c)) {
					$wordstart = true;
					$word .= $c;
				}
				else if ($wordstart) {
					return $word;
				}
			}
			else if ($wordstart) {
				return $word;
			}
			else {
				return false;
			}
		}
		return false;
	}
	
	public function rGetNextValue(&$string) {
		$string = trim($string);
		$delim = $this->nextIs($string, $this->lang->stringDelimiters, false);

		if ($delim) {
			$string = substr($string, 1);
			return array('type' => 'string', 'value' => $this->getString($string, $delim));
		}
	
		$seq = $this->getNextSequence($string);
		
		if ($seq !== false) {
			if ($this->_is_numeric($seq)) {
				$string = trim(substr(trim($string), strlen($seq)));
				if ($this->rNextIs($string,'.')) {
					$seq2 = $this->getNextSequence($string);
					$v = $seq;
					if ($this->_is_numeric($seq2)) {
						$v = $seq.'.'.$seq2;
						$string = trim(substr(trim($string), strlen($seq2)));
					}
					
					return array('type' => 'float', 'value' => (float) $v);
				}
				else {
					return array('type' => 'int', 'value' => (int) $seq);
				}
			}
			else if (strtolower($seq) === 'false' || strtolower($seq) === 'true') {
				$string = trim(substr(trim($string), strlen($seq)));
				return array('type' => 'bool', 'value' => ($seq==='true')?true:false);
			}
		}
		
		return false;
	}
	
	private function _is_numeric($seq) {
		if (((int) $seq != 0) || ((float) $seq != 0) || $seq === "0" || $seq === "0.0") {
			return true;
		}
		return false;
	}
	
	public function rNextIs(&$string, $find, $ignoreSpace = true) {
		$res = $this->nextIs($string, $find, $ignoreSpace);
		if ($res !== false) {
			if ($ignoreSpace) {
				$string = trim($string);
			}
			$string = trim(substr($string, strlen($res)));
		}
		return $res;
	}
	
	public function nextIs($string, $find, $ignoreSpace = true) {
		if (is_array($find)) {
			foreach ($find as $f) {
				if ($this->_nextIs($string, $f, $ignoreSpace)) {
					return $f;
				}
			}
			return false;
		}
		else {
			return $this->_nextIs($string, $find, $ignoreSpace);
		}
	}
	
	private function _nextIs($string, $find, $ignoreSpace = true) {
		if ($ignoreSpace) {
			$string = trim($string);
		}
		if (substr($string, 0, strlen($find)) == $find) {
			return $find;
		}
		return false;
	}
	
	public function getString(&$string, $delim) {
		$word = '';
		$prev = '';
		$remove = 1;

		foreach (str_split($string) as $c) {
			if ($c == $delim) {
				if (in_array($prev, $this->lang->escapeChars)) {
					$word = substr($word, 0, -1);
				}
				else {
					$string = substr($string, $remove);
					return $word;
				}
			}
			
			
			if ($prev == "\\") {
				if (isset($this->lang->stringDelimSymbols[$c])) {
					$c = $this->lang->stringDelimSymbols[$c];
					$word = substr($word, 0, -1);
				}
			}

			$remove++;
			
			$word .= $c;
			$prev = $c;
		}
		throw new Exception(e_unterminated_string);
	}
	
	public function getStringLen($string, $delim) {
		$len = 0;
		$prev = '';
		foreach (str_split($string) as $c) {
			if ($c == $delim && !in_array($prev, $this->lang->escapeChars)) {
				return $len;
			}
			
			$len++;
			$prev = $c;
		}
		return $len;
	}
	
	
}
