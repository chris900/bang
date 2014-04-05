<?php

require_once 'errorcodes.php';
require_once 'langbase.php';

Class Lang extends LangBase {
	
	private $running_file = false;
	private $contents;

	public function __construct($bangdir, $options = array()) {
		$this->options = $options;
		
		//set up folders
		$bangdir = $bangdir."/banglang/";
		if (!is_dir($bangdir)) {
			mkdir($bangdir);
		}
		$compileinfo = $bangdir."compiled.ini"; // contains lines of "filedir # hash @ compiled file name \n"
		if (!file_exists($compileinfo)) {
			file_put_contents($compileinfo, "\n");
		}
		$compiledir = $bangdir."compiled/"; //contains compiled file
		if (!is_dir($compiledir)) {
			mkdir($compiledir);
		}
		$packagedir = $bangdir."packages/"; //contains packages
		if (!is_dir($packagedir)) {
			mkdir($packagedir);
		}
		
		$this->bangdir = $bangdir;
		$this->compileinfo = $compileinfo;
		$this->compiledir = $compiledir;
		$this->packagedir = $packagedir;
	}

	public function setContents($c) {
		$this->contents = trim($c);
	}
	
	public function setFile($file) {
		$this->contents = trim(file_get_contents($file));
		$this->running_file = realpath($file);
	}
	
	public function run() {
		if (($this->contents && !$this->running_file) || ($this->running_file && file_exists($this->running_file))) {
			$contents = $this->contents;
			
			$ptime = false;
			
			if ($this->hasOption('x')) {
				$parsed = unserialize($contents);
			}
			else {
				$chash = md5($contents);
				
				$this->check($contents);
				
				if (!$this->running_file) {
					$ptime = microtime(true);
					$parsed = $this->parse($contents);
					$ptime = microtime(true) - $ptime;
				} else {
					$curhash = $this->getCompiledHash($this->running_file);
					if (!$this->hasOption('p') && $curhash == $chash) {
						$parsed = $this->getCompiled($this->running_file);
					}
					else {
						$ptime = microtime(true);
						$parsed = $this->parse($contents);
						$ptime = microtime(true) - $ptime;
						$this->addCompiled($this->running_file, $chash, $parsed);
					}
				}
			}
			
			$etime = microtime(true);
			$exe = $this->execute($parsed);
			$etime = microtime(true) - $etime;
			
			if ($this->hasOption('i')) {
				echo "\n\n";
				if ($ptime) {
					echo "Parse time: \t\t".$ptime."secs\n";
				}
				echo "Execution time: \t".$etime."secs\n";
			}
			
			return $exe;
		}
		else {
			throw new Exception(e_script_not_found);
		}
	}
}

