<?php
	require_once 'lang.php';
	
	//$file = '/wamp/www/PHPZAP/bang/script.lang';
	$error = false;
	$out = false;
	
	$file = false;
	if (isset($argv[1])) {
		if (file_exists(trim($argv[1]))) {
			$file = trim($argv[1]);
		}
		else {
			chdir(dirname($argv[0]));
			if (file_exists(trim($argv[1]))) {
				$file = trim($argv[1]);
			}
		}
	}

	$options = array();
	if (isset($argv[2])) {
		$options = str_split(substr($argv[2], 1));
	}
	
	function showError($message, $code = 0) {
		echo "\nERROR";
		if ($code != 0) {
			" " . $code;
		}
		echo ": ";
		echo $message."\n";
	}
	
	if ($file) {
		$L = false;
		try {
			$L = new Lang(dirname($argv[0]), $options);
			$L->setFile($file);

			$out = $L->run();
			//echo $out;
		}	
		catch (Exception $e) {
			if ($L) {
				$out = $L->output;
			}
			//echo $out;
			showError($e->getMessage(), $e->getCode());
		}
	}
	else {
		showError("Input file not found");
	}
