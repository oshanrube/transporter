<?php
include_once 'folder.class.php';
include_once 'file.encryption.class.php';
$action = $argv[1];
switch($action) {
	case 'exec':
		//run export
		runexport();
		break;
	case 'dwnld':
		//get filelist
		$fileList = getFileList();
		//create folder
		createFolders();
		//download files
		getFiles($fileList);
		//download keys
		getkeys($fileList);
		break;
	case 'extrct':
		//get filelist
		$fileList = getFileList();
		//extact files
		gzextractFiles($fileList);
		//decrypt
		decryptFiles($fileList);
		break;
	default:
		echo "error";
		break;
}

function runexport() {
	$url = 'http://ebase.whatsupbuddy.com/module/export.php?key=oshan1991';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
 	curl_setopt($ch, CURLOPT_HEADER,1);  // DO NOT RETURN HTTP HEADERS
	curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);  // RETURN THE CONTENTS OF THE CALL 
	$Rec_Data = curl_exec($ch);	
	curl_close($ch);
	echo $Rec_Data;
}

function getFileList() {
	$url = 'http://ebase.whatsupbuddy.com/module/transport.php?action=listFiles&key=oshan1991';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);  // RETURN THE CONTENTS OF THE CALL 
	$data = curl_exec($ch);
	curl_close($ch);
	return json_decode($data);
}
function createFolders() {
	$folder = new Folder();
	//create folders
	$folder->createTablesFolder();
	$folder->createKeyFolder();	
}
function getFiles($fileList) {
	foreach($fileList as $filename){
		downloadFile($filename);
	}
}
function getkeys($fileList) {
	foreach($fileList as $filename){
		downloadFile($filename,'Key');
	}
}
function downloadFile($filename,$type = 'File') {
	$folderDir = dirname(__FILE__).'/tables/';
	$folderKeyDir = dirname(__FILE__).'/tables/key/';
	//get filename
	if($type == 'File') {
		$path = $folderDir.$filename;
	} else {
		$filename = preg_replace('/.dat.gz$/','',$filename).'.enc.key';
		$path = $folderKeyDir.$filename;
	}
	//url
	$url = 'http://ebase.whatsupbuddy.com/module/transport.php?action=get'.$type.'&key=oshan1991&filename='.$filename;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);  // RETURN THE CONTENTS OF THE CALL 
	$data = curl_exec($ch);
	file_put_contents($path,$data);
	curl_close($ch);
}
function gzextractFiles($fileList){
	foreach($fileList as $file){
		gzextract($file);
	}
}
function gzextract($filename){
	$folderDir = dirname(__FILE__).'/tables/';
	$folderKeyDir = dirname(__FILE__).'/tables/key/';

	//filename
	$path = $folderDir.$filename;
	//get extracted filename
	$path2 = $folderDir.preg_replace('/.gz$/','',$filename).'.enc';
	//get file size
	$gzip=file_get_contents($path);
	$rest = substr($gzip, -4); 
	$GZFileSize = end(unpack("V", $rest));
	// getting content of the compressed file
   $HandleRead = gzopen($path, "rb");
	$ContentRead = gzread($HandleRead, $GZFileSize);
	gzclose($HandleRead);
	file_put_contents($path2,$ContentRead);
	unlink($path);
}
function decryptFiles($fileList) {
	$folderDir = dirname(__FILE__).'/tables/';
	$folderKeyDir = dirname(__FILE__).'/tables/key/';
	
	foreach($fileList as $file){
		$filename 	= $folderDir.preg_replace('/.gz$/','',$file);
		$keyfile = $folderKeyDir.preg_replace('/.dat.gz$/','',$file).'.enc';
		$encrypt = new Encrypt($filename,$keyfile);
		//decrypt
		$encrypt->decryptFile();
		//delete files
		unlink($filename.'.enc');
		unlink($keyfile.'.key');
	}
	//delete htaccess in key folder
	unlink($folderKeyDir.'.htaccess');
	//delete keys folder
	rmdir($folderKeyDir);
}

?>