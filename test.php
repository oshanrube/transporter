<?php
include 'class.dbsync.php';
$database = 'temp';
$type = 'mysql';
$host = 'localhost';
$user = 'root';
$pass = 'oshan1991';

//$dbsync = new DBSync();
//$dbsync->SetHomeDatabase($db, 'mysql', $host, $user, $pass);
		    //$dbsync->AddSyncDatabase($_POST['sdb'], $_POST['stype'], $_POST['shost'], $_POST['suser'], $_POST['spass']);
//if (!$dbsync->Sync()) {
//	$error = 'Something went wrong with synchronising...';
//}     
if (!class_exists("DBSync_{$type}")) {
	include dirname(__FILE__) . "/class.dbsync.{$type}.php";
}
$class = "DBSync_{$type}";

$home = new $class($host, $user, $pass, $database);
if (!$home->ok) {
	die('Home Database Error: ' . $home->LastError());
}
$tables = $home->ListTables();
var_dump($tables);


?>