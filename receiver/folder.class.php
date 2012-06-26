<?php

class Folder{
	
	public function __construct() {
		$this->folderDir = dirname(__FILE__).'/tables/';
		$this->folderKeyDir = dirname(__FILE__).'/tables/key/';
	}
	public function createTablesFolder() {
		//create tables folder
		if(!is_dir($this->folderDir)){
			mkdir($this->folderDir);
		} else {
			//read the files in the tables directory
			$handle = opendir($this->folderDir);
			while (($file = readdir($handle)) !== false)
			{
				//match files with name sql.dat
				if(preg_match('/dat\.gz/',$file)){
					//delete them
					unlink($this->folderDir.'/'.$file);
				}
			}
		}
		//htaccess
		$this->createHtaccess($this->folderDir);
	}
	public function createKeyFolder(){
		//create key folder
		if(!is_dir($this->folderKeyDir)){
			mkdir($this->folderKeyDir);
		} else {
			//read the files in the key directory
			$handle = opendir($this->folderKeyDir);
			while (($file = readdir($handle)) !== false)
			{
				//if file
				if(!is_dir($this->folderKeyDir.'/'.$file)){
					//delete them
					unlink($this->folderKeyDir.'/'.$file);
				}
			}
		}
		//htaccess
		$this->createHtaccess($this->folderKeyDir);
	}
	private function createHtaccess($folder){ 
		//htaccess
		$htaccess = $folder.'/.htaccess';
		//htaccess rule
		$rule = "RewriteEngine On\nRewriteRule ^$ - [L,R=404]";
		//check if the htaccess is there
		if(!file_exists($htaccess)){
			//create htaccess
			file_put_contents($htaccess,$rule);
		} else {
			//check wether its the right htaccess
			if(file_get_contents($htaccess) == $rule){
				//create htaccess
				file_put_contents($htaccess,$rule);
			}
		}
	}
}
?>