<?php
class SQL_Export
{
	var $cnx;
	var $db;
	var $server;
	var $port;
	var $user;
	var $password;
	var $table;
	var $tables;
	var $exported;

	function SQL_Export( $option, $tables)
	{
		$this->tables = $tables;
		$this->db = $option['database'];      // Database name
		$this->cnx = mysql_connect($option['host'], $option['user'], $option['password']) or $this->error(mysql_error());
		mysql_select_db($this->db, $this->cnx) or $this->error(mysql_error());
	}


	function export()
	{
		if(is_string($this->tables) && strtolower($this->tables) == 'all'){
			$this->tables = array();
			$result = mysql_list_tables($this->db);
			while($row = mysql_fetch_array($result)){
				$this->tables[] = $row[0];
			}	
		}
		foreach($this->tables as $t)
		{
			
			$this->table = $t;
			//$header = $this->create_header();
			$data = $this->get_data();
			//$this->exported .= "###################\n# Dumping table $t\n###################\n\n$header" . $data . "\n";
			$this->exported[$t] = $data . "\n\n";
		}
		
		return($this->exported);
	}

	function create_header()
	{
		$fields = mysql_list_fields($this->db, $this->table, $this->cnx);
		$h = "CREATE TABLE `" . $this->table . "` (";
		for($i=0; $i<mysql_num_fields($fields); $i++)
		{
			$name = mysql_field_name($fields, $i);
			$flags = mysql_field_flags($fields, $i);
			$len = mysql_field_len($fields, $i);
			$type = mysql_field_type($fields, $i);

			$h .= "`$name` $type($len) $flags,";

			if(strpos($flags, "primary_key")) {
				$pkey = " PRIMARY KEY (`$name`)";
			}
		}
		
		$h = substr($h, 0, strlen($d) - 1);
		$h .= "$pkey) TYPE=MyISAM;\n\n";
		return($h);
	}

	function get_data()
	{
		$d = null;
		$data = mysql_query("SELECT * FROM `" . $this->table . "` WHERE 1", $this->cnx) or $this->error(mysql_error());
		
		while($cr = mysql_fetch_array($data, MYSQL_NUM))
		{
			//$d .= "INSERT INTO `" . $this->table . "` VALUES (";

			for($i=0; $i<sizeof($cr); $i++)
			{
				if($cr[$i] == '' OR $cr[$i] == null) {
					$d .= 'NULL,';
				} else {
					$val = mysql_real_escape_string($cr[$i]);
					$d .= "'$val',";
					unset($val);
				}
			}

			$d = substr($d, 0, strlen($d) - 1);
			$d .= "\n";
		}

		return($d);
	}

	function error($err)
	{
		die($err);
	}
}
?>