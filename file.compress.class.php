<?php

$filename = 'filedata-1.txt';
$filename = 'filedata-2.txt.enc';
//$filename = 'filename.png';
$zipFilename = $filename.'.gz';
$s = "Only a test, test, test, test, test, test, test, test!\n";
$s = file_get_contents($filename);
// open file for writing with maximum compression
$zp = gzopen($zipFilename, "w9");

// write string to file
gzwrite($zp, $s);

// close file
gzclose($zp);
/*************************************/
/*
// open file for reading
$zp = gzopen($zipFilename, "r");

// read 3 char
//echo gzread($zp, 3);

// output until end of the file and close it.
gzpassthru($zp);
gzclose($zp);
*/
/***************************************/
/*
echo "\n";

// open file and print content (the 2nd time).
if (readgzfile($zipFilename) != strlen($s)) {
        echo "Error with zlib functions!";
}
unlink($zipFilename);
*/
?>