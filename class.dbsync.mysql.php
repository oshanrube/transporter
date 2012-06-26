<?php
	/**
     * Class DBSync_mysql
     * Used by class DBSync to sync a MySQL database
     *
     * @author Diogo Resende <me@diogoresende.net>
     * @licence GPL
     *
     * @method DBSync_mysql::ListTables()
     * @method DBSync_mysql::ListTableFields()
     * @method DBSync_mysql::CreateTable()
     * @method DBSync_mysql::RemoteTable()
     * @method DBSync_mysql::AddTableField()
     * @method DBSync_mysql::ChangeTableField()
     * @method DBSync_mysql::RemoveTableField()
     * @method DBSync_mysql::ClearTablePrimaryKeys()
     * @method DBSync_mysql::SetTablePrimaryKeys()
     * @method DBSync_mysql::LastError()
     **/
	class DBSync_mysql {
    	var $dbp;
        var $database;
        var $host;
        var $user;
        var $pass;
        var $ok = false;

        /**
         * DBSync_mysql::DBSync_mysql()
		 * Class constructor
         *
         * @param	string	$host		Host
         * @param	string	$user		Database Username
         * @param	string	$pass		Database Password
         * @param	string	$database	Database Name
         *
         * @access	public
         * @return 	void
         **/
    	function DBSync_mysql($host, $user, $pass, $database) {
        	$this->database = $database;
            $this->host = $host;
            $this->user = $user;
            $this->pass = $pass;
        	if (($this->dbp = @mysql_pconnect($host, $user, $pass)) !== false) {
            	$this->ok = @mysql_select_db($database, $this->dbp);
                return;
            }
			$this->ok = false;
        }

        /**
         * DBSync_mysql::ListTables()
		 * List tables on current database
         *
         * @access	public
         * @return 	array	Table list
         **/
        function ListTables() {
        	$tables = array();

        	$result = mysql_query("SHOW TABLES FROM {$this->database}", $this->dbp);
            while ($row = mysql_fetch_row($result)) {
				$tables[] = $row[0];
            }

            return $tables;
        }

        /**
         * DBSync_mysql::ListTableFields()
		 * List table fields from a table on current database
         *
         * @param	string	$table	Table Name
         *
         * @access	public
         * @return 	array	Field List
         **/
        function ListTableFields($table) {
            mysql_select_db($this->database, $this->dbp);

        	$fields = array();
        	$result = mysql_query("SHOW COLUMNS FROM {$table}", $this->dbp);
            while ($row = mysql_fetch_row($result)) {
				$fields[] = array(
                	'name'	  => $row[0],
                    'type'    => $row[1],
                    'null'    => $row[2],
                    'key'     => $row[3],
                    'default' => $row[4],
                    'extra'   => $row[5]
                );
            }

            return $fields;
        }

        /**
         * DBSync_mysql::CreateTable()
		 * Create a table on current database
         *
         * @param	string	$name		Table Name
         * @param	array	$fields		Field List
         *
         * @access	public
         * @return 	boolean	Success
         **/
        function CreateTable($name, $fields) {
            mysql_select_db($this->database, $this->dbp);

        	$primary_keys = array();
            $sql_f = array();

            for ($i = 0; $i < count($fields); $i++) {
            	if ($fields[$i]['key'] == 'PRI') {
                	$primary_keys[] = $fields[$i]['name'];
                }
                $sql_f[] = "`{$fields[$i]['name']}` {$fields[$i]['type']} " . ($fields[$i]['null'] ? '' : 'NOT') . ' NULL' . (strlen($fields[$i]['default']) > 0 ? " default '{$fields[$i]['default']}'" : '') . ($fields[$i]['extra'] == 'auto_increment' ? ' auto_increment' : '');
            }

            $sql = "CREATE TABLE `{$name}` (" . implode(', ', $sql_f) . (count($primary_keys) > 0 ? ", PRIMARY KEY (`" . implode('`, `', $primary_keys) . "`)" : '') . ')';
            return mysql_query($sql, $this->dbp);
        }

        /**
         * DBSync_mysql::RemoveTable()
		 * Remove a table from current database
         *
         * @param	string	$name		Table Name
         *
         * @access	public
         * @return 	boolean	Success
         **/
        function RemoveTable($table) {
            mysql_select_db($this->database, $this->dbp);

			$sql = "DROP TABLE `{$table}`";
            return mysql_query($sql, $this->dbp);
        }

        /**
         * DBSync_mysql::AddTableField()
		 * Add a field to a table on current database
         *
         * @param				string	$table			Table Name
         * @param				array	$field			Field Information
         * @param	optional	string	$field_before	Field before the field to be added
         *												(if $field_before = 0 this field will
         *												be added at the begining of the table)
         *
         * @access	public
         * @return 	boolean	Success
         **/
        function AddTableField($table, $field, $field_before = 0) {
			$sql = "ALTER TABLE `{$table}` ADD `{$field['name']}` {$field['type']} " . ($field['null'] ? '' : 'NOT') . ' NULL' . (strlen($field['default']) > 0 ? " default '{$field['default']}'" : '') . ($field['extra'] == 'auto_increment' ? ' auto_increment' : '') . (!is_string($field_before) ? ' FIRST' : " AFTER `{$field_before}`") . ($field['key'] == 'PRI' ? ", ADD PRIMARY KEY (`{$field['name']}`)" : '');
            return mysql_query($sql, $this->dbp);
        }

        /**
         * DBSync_mysql::ChangeTableField()
		 * Change a field on a table on current database
         *
         * @param	string	$table		Table Name
         * @param	string	$field		Field Name
         * @param	array	$new_field	New Field Information
         *
         * @access	public
         * @return 	boolean	Success
         **/
        function ChangeTableField($table, $field, $new_field) {
			$sql = "ALTER TABLE `{$table}` CHANGE `{$field}` `{$new_field['name']}` {$new_field['type']} " . ($new_field['null'] ? '' : 'NOT') . ' NULL' . (strlen($new_field['default']) > 0 ? " default '{$new_field['default']}'" : '') . ($field['extra'] == 'auto_increment' ? ' auto_increment' : '') . ($field['key'] == 'PRI' ? ", ADD PRIMARY KEY (`{$field['name']}`)" : '');
            return mysql_query($sql, $this->dbp);
        }

        /**
         * DBSync_mysql::RemoveTableField()
		 * Remove a field from a table on current database
         *
         * @param	string	$table		Table Name
         * @param	string	$field		Field Name
         *
         * @access	public
         * @return 	boolean	Success
         **/
        function RemoveTableField($table, $field) {
			$sql = "ALTER TABLE `{$table}` DROP `{$field}`";
            return mysql_query($sql, $this->dbp);
        }

        /**
         * DBSync_mysql::ClearTablePrimaryKeys()
		 * Clear primary keys on a table on current database
         *
         * @param	string	$table		Table Name
         *
         * @access	public
         * @return 	boolean	Success
         **/
        function ClearTablePrimaryKeys($table) {
        	$sql = "ALTER TABLE `{$table}` DROP PRIMARY KEY";
            return mysql_query($sql, $this->dbp);
        }

        /**
         * DBSync_mysql::SetTablePrimaryKeys()
		 * Clears primary keys and sets new ones on a table on current database
         *
         * @param	string	$table		Table Name
         * @param	array	$keys		Primary Keys List
         *
         * @access	public
         * @return 	boolean	Success
         **/
        function SetTablePrimaryKeys($table, $keys) {
        	$sql = "ALTER TABLE `{$table}` DROP PRIMARY KEY, ADD PRIMARY KEY (`" . implode('`, `', $keys) . "`)";
            return mysql_query($sql, $this->dbp);
        }

        /**
         * DBSync_mysql::LastError()
		 * Returns last error message from MySQL server
         *
         * @access	public
         * @return 	string	Error Message
         **/
        function LastError() {
        	return mysql_error($this->dbp);
        }
    }
?>