<?php
include_once 'file.encryption.class.php';
include_once 'sql.class.php';
$key = 'oshan1991';
//if($key != $_GET['key'])die('Invalid auth');
//db spec
$option = array(); //prevent problems
$option['driver']   = 'mysql';            // Database driver name
$option['host']     = 'localhost';    // Database host name
$option['user']     = 'root';       // User for database authentication
$option['password'] = 'oshan1991';   // Password for database authentication
$option['database'] = 'visitlanka';      // Database name
$option['prefix']   = '';             // Database prefix (may be empty)
//track time
$time_start = microtime(true);
//create class
$model = new api($option);
//tables
$tables = array('vis_assets');
$tables = 'all';
//run
$rows = $model->sqlExport($tables);
//!track time
$time_end = microtime(true);
$time = $time_end - $time_start;
echo "Execution time: $time seconds\n";

class api
{
	 private $_hostdb;
	 private $_localdb;
	function __construct($option)
	{
		$this->_option = $option ;
	}
	
	function sqlExport($tables,$filename = 'tables.sql') {
		$e = new SQL_Export($this->_option,$tables);
		//Run the export
		$this->writeFile($filename,$e->export());
	}
	function writeFile($filename,$tables) {
		$folderDir = dirname(__FILE__).'/tables';
		$folderKeyDir = dirname(__FILE__).'/tables/key/';
		$folder = 'tables';
		//check folder
		$this->createTables($folderDir);
		//create key folder
		$this->createKeyFolder($folderKeyDir);
		//!create key folder
		//create files for each table
		foreach($tables as $tablename => $table){
			//encrypt class
			$encrypt = new Encrypt();
			//encrypt data
			$data = $encrypt->encrypt($table,$folderKeyDir.$tablename);
			//create filename
			$filename = $folderDir.'/'.$tablename.'.dat.gz';
			// open file for writing with maximum compression
			$zp = gzopen($filename, "w9");
			// write string to file
			gzwrite($zp, $data);
			// close file
			gzclose($zp);
		}		
	}
	private function createTables($folderDir){
		//create tables folder
		if(!is_dir($folderDir)){
			mkdir($folderDir);
		} else {
			//read the files in the tables directory
			$handle = opendir($folderDir);
			while (($file = readdir($handle)) !== false)
			{
				//match files with name sql.dat
				if(preg_match('/dat\.gz/',$file)){
					//delete them
					unlink($folderDir.'/'.$file);
					//echo 'DELETED:'.$folderDir.'/'.$file."\n";
				}
			}
		}
		//htaccess
		$this->createHtaccess($folderDir);
	}
	private function createKeyFolder($folderKeyDir){
		//create key folder
		if(!is_dir($folderKeyDir)){
			mkdir($folderKeyDir);
		} else {
			//read the files in the key directory
			$handle = opendir($folderKeyDir);
			while (($file = readdir($handle)) !== false)
			{
				//if file
				if(!is_dir($folderKeyDir.'/'.$file)){
					//delete them
					unlink($folderKeyDir.'/'.$file);
				}
			}
		}
		//htaccess
		$this->createHtaccess($folderKeyDir);
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
