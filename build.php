<?php

function buildExe($tinput, $output) {
	$compPath = "C:\Program Files (x86)\Phalanger 3.0\Bin\phpc.exe";

	$in = "";
	if (is_array($tinput)) {
		$input = array();
		$first = false;
		
		foreach ($tinput as $i) {
			$fn = basename($i, '.php');
			if (strtolower($fn) == "main") {
				$first = $i;
			}
			else {
				$input[] = $i;
			}
		}
		
		if ($first) {
			array_unshift($input, $first);
		}
		
		//echo "\n INPUT FILES\n -----------\n";
		foreach ($input as $i) {
			//echo $i."\n";
			$in .= $i . " ";
		}
	}
	else {
		$in = $tinput;
	}
	
	$exec = "\"".$compPath."\" /target:exe ".$in." /out:".$output;
	exec($exec, $out);
	return implode("\n", $out);
}

$input = glob(dirname(__FILE__)."\\src\\*.php");
$output = dirname(__FILE__)."\\bang.exe";
echo buildExe($input, $output)."\n";
