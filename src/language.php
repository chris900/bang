<?php

Class Language {
		
	public function __construct() {
		
	}
	
	public $types = array(
		'int',
		'bool',
		'string',
		'char',
		'float',
		'array',
		'function',
		'class',
		'thread',
	);
	
	public $functionReturns = array(
		'return',
		'returns',
		'ret',
	);
	
	public $structureNames = array(
		'if',
		'for',
		'thread',
		'function',
		'func',
		'class',
	);
	
	public $lineComments = array(
		'//',
	);
	
	public $blockCommentStart = array(
		'/*',
	);
	
	public $blockCommentEnd = array(
		'*/',
	);
	
	public $elseStructure = array(
		'else',
	);
	
	public $elseIfStructure = array(
		'elseif',
		'else if',
		'elif',
	);
	
	public $stringDelimSymbols = array(
		'n' => "\n",
		't' => "\t",
		'r' => "\r",
	);
	
	public $threadWaitOperator = array(
		'@'
	);
	
	public $openArray = array(
		'[',
	);
	
	public $closeArray = array(
		']',
	);
	
	public $arrayKey = array(
		':',
	);
	
	public $openStructure = array(
		'{',
	);
	
	public $closeStructure = array(
		'}',
	);
	
	public $openFunction = array(
		'(',
	);
	
	public $closeFunction = array(
		')',
	);
	
	public $operators = array(
		'+',
		'-',
		'*',
		'/',
		'^',
		'===',
		'==',
		'=',
		'>==',
		'<==',
		'>=',
		'<=',
		'>',
		'<',
		'&&',
		'&',
		'||',
	);
	
	public $singleOperators = array(
		'++',
		'--',
	);
	
	public $argumentSeperators = array(
		',',
		' ',
	);
	
	public $setOperators = array(
		'=',
	);
	
	public $comparisonOperators = array(
		'===',
		'==',
		'>==',
		'<==',
		'>=',
		'<=',
	);
	
	public $mathOperators = array(
		'+',
		'-',
		'*',
		'/',
		'^',
	);
	
	public $includeDeclarations = array(
		'include',
	);
	
	public $preIncludeDeclarations = array(
		'#include',
	);
	
	public $packageIncludeDeclarations = array(
		'#package',
	);
	
	public $concatOperators = array(
		'+',
	);
	
	public $scopeDeclarations = array(
		'$',
		'var ',
	);
	
	public $statementEnd = array(
		';',
	);
	
	public $validSequenceChars = array(
		'_',
	);
	
	public $stringDelimiters = array(
		"'",
		'"',
	);
	
	public $escapeChars = array(
		'\\',
	);
	
}

