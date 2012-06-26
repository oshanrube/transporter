<?php
//authenticate access
$key = 'oshan1991';
//if($key != $_GET['key'])die('Invalid auth');

//identify the request
$action = $_GET['action'];//$action = 'listFiles';
switch($action) {
	case 'listFiles':
		listFiles();
		break;
	case 'getFile':
		$filename = $_GET['filename'];
		getFile($filename);
		break;
	case 'getKey':
		$filename = $_GET['filename'];
		getKeyFile($filename);
		break;
	default:
		header("HTTP/1.1 404 Not Found"); 
		break;	
}

function listFiles() {
	$folderDir = dirname(__FILE__).'/tables/';
	$folderKeyDir = dirname(__FILE__).'/tables/key/';
	//read the files in the tables directory
	$handle = opendir($folderDir);
	while (($file = readdir($handle)) !== false)
	{
		//match files with name sql.dat
		if(preg_match('/dat\.gz/',$file)){
			$files[] = $file;
		}
	}
	//print the json
	echo json_encode($files);
}

function getFile($filename) {
	$folderDir = dirname(__FILE__).'/tables/';
	$path = $folderDir.$filename;
	header("Content-type: application/zip;\n");
	header("Content-Transfer-Encoding: binary");
	$len = filesize($path);
	header("Content-Length: $len;\n");
	header("Content-Disposition: attachment; filename=\"$filename\";\n\n");
	readfile($path);
}
function getKeyFile($filename) {
	$folderKeyDir = dirname(__FILE__).'/tables/key/';
	$path = $folderKeyDir.$filename;
	header("Content-type: application/zip;\n");
	header("Content-Transfer-Encoding: binary");
	$len = filesize($path);
	header("Content-Length: $len;\n");
	header("Content-Disposition: attachment; filename=\"$filename\";\n\n");
	readfile($path);
}
?>