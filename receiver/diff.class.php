<?php
class Diff{
	function __construct($options) {
		$this->table = $options['table'];
		$this->filename = $options['filename'];
		$this->db = mysql_connect($options['host'], $options['user'], $options['password']) or die(mysql_error());
		mysql_select_db($options['database'], $this->db) or die(mysql_error());
		
	}
	function __destruct() {
	//	mysql_close($this->db);
	}
	function exec() {
		//get index
		$this->index = $this->getIndex();
		//get data in the file
		$ids = $this->getFileRows();
		//check if file is empty
		if(count($ids)>0){
			$ids = implode('","',$ids);
			$this->getDifference($ids);
		}
	}
	function getRows() {
		return $this->rows;
	}
	private function getTables() {
		
	}
	private function getIndex() {
		$data = mysql_query("SHOW INDEX FROM`" . $this->table . "`", $this->db) or die(mysql_error());
		$data = mysql_fetch_assoc($data);
		return $data['Column_name'];		
	}
	private function getFileRows() {
		$handle = @fopen($this->filename, "r") or die('Error opening file');
			if ($handle) {
			    while (($buffer = fgets($handle)) !== false) {
			    	if($buffer != "\n"){
			    		$valiables = explode("','",$buffer);	//get index value
				    	$valiables = substr($valiables[0],1);
				    	$sql = 'SELECT * FROM `'. $this->table . "` WHERE `".$this->index.'`="'.$valiables.'"';
				    	$data2 = mysql_query($sql, $this->db);
				    	if(mysql_num_rows($data2) == 0){
				    		$rows[] = 'ADD:'.$buffer;//add to errors
				    	} else {
				    		$objs = array();
				    		$clmns = array();				    		
				    		while($row = mysql_fetch_assoc($data2)){
				    			foreach($row as $obj=>$val){
				    				$objs[] = mysql_real_escape_string($val);
				    				$clmns[]=$obj;
				    			}
				    		}
				    		$row = "'".implode("','",$objs)."'\n";
				    		$row = str_replace("''",'NULL',$row);
				    		if($row != $buffer){
				    			$err = '';//array();
				    			$buffer = substr($buffer,0,-2);
				    			$buffer = substr($buffer,1);
				    			//get variables from line
				    			$vars = explode('\',\'',$buffer);
				    			//rotate thro all the columns in files
				    			$count = 0;
				    			foreach($vars as $var){
				    				//if the values does not match
				    				if($var != $objs[$count]) {
				    					$err .= "'$clmns[$count]' => '$objs[$count]','$var'";
				    				}
				    				$count++;			    				
				    			}
				    			$this->rows->local->rows[] = 'CHANGED:'.$err;//add to errors
				    		}
			    		}
			    		$ids[] = $valiables;
			    	}
			    }
			    if (!feof($handle)) {
			        echo "Error: unexpected fgets() fail\n";
			    }
			    fclose($handle);
			}
		return $ids;
	}
	private function getDifference($ids){
		$sql = 'SELECT * FROM `'. $this->table . "` WHERE `".$this->index.'` NOT IN("'.$ids.'")';
		$data3 = mysql_query($sql, $this->db);	
		while ($row = mysql_fetch_assoc($data3)) {
			$row = "'".implode("','",$row)."'";
			$row = str_replace("''",'NULL',$row);
			$this->rows->server->rows[] = 'MISSING:'.$row;//add to errors
		}
	}
}
?>