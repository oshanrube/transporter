<?php
include_once 'diff.class.php';
$table = 'vis_assets';
$option = array(); //prevent problems
$option['driver']   = 'mysql';            // Database driver name
$option['host']     = 'localhost';    // Database host name
$option['user']     = 'root';       // User for database authentication
$option['password'] = 'oshan1991';   // Password for database authentication
$option['database'] = 'ebase';      // Database name
$option['prefix']   = '';             // Database prefix (may be empty)


//directory 
$Dir = dirname(__FILE__).'/tables/';
//read the files in the key directory
	$handle = opendir($Dir);
	while (($file = readdir($handle)) !== false)
	{
		
		//if file
		if(!is_dir($Dir.$file) && preg_match("/\.dat$/",$file) ){//
			//get table name
			$option['table'] = preg_replace('/\.dat$/','',$file);
			$option['filename'] = $Dir.$file;
			$diff = new Diff($option);
			$diff->exec();
			$diffs[$option['table']] = $diff->getRows();
		}
	}
	var_dump($diffs);
?>