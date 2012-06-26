<?php
	/**
     * Class DBSync
     * Sync 2 or more databases. For now it only supports structure wich
     * is the most essencial part.
     *
     * @author Diogo Resende <me@diogoresende.net>
     * @licence GPL
     *
     * @method DBSync::SetHomeDatabase()
     * @method DBSync::AddSyncDatabase()
     * @method DBSync::Sync()
     **/
	class DBSync {
    	var $home = array();
        var $sync = array();

        /**
         * DBSync::DBSync()
		 * Class constructor
         *
         * @param	optional	string	$database	Home Database Name
         * @param	optional	string	$type		Home Database Type
         * @param	optional	string	$host		Home Host (can be diferent from localhost :D)
         * @param	optional	string	$user		Home Database Username
         * @param	optional	string	$pass		Home Database Password
         *
         * @access	public
         * @return 	void
         **/
    	function DBSync($database = '', $type = 'mysql', $host = 'localhost', $user = 'root', $pass = '') {
        	if (strlen($database) > 0) {
            	$this->SetHomeDatabase($database, $type, $host, $user, $pass);
            }
        }

        /**
         * DBSync::SetHomeDatabase()
         * Set definitions for home database. This is the database that should be
         * correct and all others will be synched with this one.
         *
         * @param				string	$database	Home Database Name
         * @param	optional	string	$type		Home Database Type
         * @param	optional	string	$host		Home Host (can be diferent from localhost :D)
         * @param	optional	string	$user		Home Database Username
         * @param	optional	string	$pass		Home Database Password
         *
         * @access	public
         * @return 	void
         **/
        function SetHomeDatabase($database, $type = 'mysql', $host = 'localhost', $user = 'root', $pass = '') {
        	if (!class_exists("DBSync_{$type}")) {
            	include dirname(__FILE__) . "/class.dbsync.{$type}.php";
            }

            $class = "DBSync_{$type}";

            $this->home = new $class($host, $user, $pass, $database);
            if (!$this->home->ok) {
            	$this->RaiseError('Home Database Error: ' . $this->home->LastError());
            }
        }

        /**
         * DBSync::AddSyncDatabase()
         * Add a database to sync with the home database. You can add as many as
         * you want.
         *
         * @param				string	$database	Database Name
         * @param	optional	string	$type		Database Type
         * @param	optional	string	$host		Host
         * @param	optional	string	$user		Database Username
         * @param	optional	string	$pass		Database Password
         *
         * @access	public
         * @return 	void
         **/
        function AddSyncDatabase($database, $type = 'mysql', $host = 'localhost', $user = 'root', $pass = '') {
        	if (!class_exists("DBSync_{$type}")) {
            	include dirname(__FILE__) . "/class.dbsync.{$type}.php";
            }

            $class = "DBSync_{$type}";

            $sync = new $class($host, $user, $pass, $database);
            if (!$sync->ok) {
            	$this->RaiseError('Sync Database Error: ' . $this->home->LastError());
            }
            $this->sync[] = $sync;
        }

        /**
         * DBSync::Sync()
         * Sync defined databases with home database
         *
         * @access	public
         * @return 	boolean		Success
         **/
        function Sync() {
        	if (count($this->sync) == 0) {
            	$this->RaiseError('No Sync Databases defined. Use AddSyncDatabase() to add Sync Databases.');
            }

            for ($i = 0; $i < count($this->sync); $i++) {
            	$this->SyncDatabases($this->home, $this->sync[$i]);
            }

            return true;
        }

        /**
         * DBSync::SyncDatabases()
         * Sync one database with home database
         *
         * @access	private
         * @return 	boolean		Success
         **/
        function SyncDatabases(&$db_home, &$db_sync) {
        	$tables_home = $db_home->ListTables();
            $tables_sync = $db_sync->ListTables();

            for ($i = 0; $i < count($tables_home); $i++) {
            	if (!in_array($tables_home[$i], $tables_sync)) {
                	$fields = $db_home->ListTableFields($tables_home[$i]);
					if (!$db_sync->CreateTable($tables_home[$i], $fields)) {
                    	$this->RaiseError("Could not create table <strong>{$tables_home[$i]}</strong> on database <strong>{$db_sync->database}</strong> at {$db_sync->user}@{$db_sync->host}: " . $db_sync->LastError());
                    }
                } else {
					$fields_home = $db_home->ListTableFields($tables_home[$i]);
                    $fields_sync = $db_sync->ListTableFields($tables_home[$i]);
                    $fieldnames_sync = $this->GetFieldNames($fields_sync);

                    $diferent_fields = 0;

                    for ($j = 0; $j < count($fields_home); $j++) {
                    	if (!in_array($fields_home[$j]['name'], $fieldnames_sync)) {
                            if (!isset($fields_home[$j - 1])) {
                            	$success = $db_sync->AddTableField($tables_home[$i], $fields_home[$j], 0);
                            } else {
	                        	$success = $db_sync->AddTableField($tables_home[$i], $fields_home[$j], $fields_home[$j - 1]['name']);
                            }
                            if (!$success) {
								$this->RaiseError("Could not add field <strong>{$fields_home[$j]['name']}</strong> to table <strong>{$tables_home[$i]}</strong> on database <strong>{$db_sync->database}</strong> at {$db_sync->user}@{$db_sync->host}: " . $db_sync->LastError());
                            }
                            $diferent_fields++;
                        } else {
                        	$k = $this->GetFieldIndex($fields_sync, $fields_home[$j]['name']);
                            if ($fields_sync[$k]['type'] != $fields_home[$j]['type'] ||
                                $fields_sync[$k]['null'] != $fields_home[$j]['null'] ||
                                $fields_sync[$k]['key'] != $fields_home[$j]['key'] ||
                                $fields_sync[$k]['default'] != $fields_home[$j]['default'] ||
                                $fields_sync[$k]['extra'] != $fields_home[$j]['extra']) {
                                if (!$db_sync->ChangeTableField($tables_home[$i], $fields_home[$j]['name'], $fields_home[$j])) {
	                                $this->RaiseError("Could not change field <strong>{$fields_home[$j]['name']}</strong> on table <strong>{$tables_home[$i]}</strong> on database <strong>{$db_sync->database}</strong> at {$db_sync->user}@{$db_sync->host}: " . $db_sync->LastError());
                                }
                                $diferent_fields++;
                            }
                        }

		                unset($fieldnames_sync[array_shift(array_keys($fieldnames_sync, $fields_home[$j]['name']))]);
                    }

                    if ($diferent_fields > 0) {
                    	/**
                         * Arrange Primary Keys
                         **/
                        $keys_home = $this->GetPrimaryKeys($fields_home);
                        $keys_sync = $this->GetPrimaryKeys($fields_sync);
                        if ($this->DiferentKeys($keys_home, $keys_sync)) {
	                        if (count($keys_home) > 0) {
    	                    	$db_sync->SetTablePrimaryKeys($tables_home[$i], $keys_home);
        	                } else {
            	            	$db_sync->ClearTablePrimaryKeys($tables_home[$i]);
                	        }
                        }
                    }

        		    foreach ($fieldnames_sync as $field) {
                    	if (!$db_sync->RemoveTableField($tables_home[$i], $field)) {
	                        $this->RaiseError("Could not change field <strong>{$field}</strong> on table <strong>{$tables_home[$i]}</strong> on database <strong>{$db_sync->database}</strong> at {$db_sync->user}@{$db_sync->host}: " . $db_sync->LastError());
                        }
		            }
                }

                unset($tables_sync[array_shift(array_keys($tables_sync, $tables_home[$i]))]);
            }

            foreach ($tables_sync as $table) {
            	if (!$db_sync->RemoveTable($table)) {
	                $this->RaiseError("Could not remove table <strong>{$table}</strong> on database <strong>{$db_sync->database}</strong> at {$db_sync->user}@{$db_sync->host}: " . $db_sync->LastError());
                }
            }

            return true;
        }

        /**
         * DBSync::GetFieldNames()
         * Return the names of the fields on the field list array
         *
         * @param	array	$fields		Field List
         *
         * @access	private
         * @return 	array		Field Names
         **/
        function GetFieldNames($fields) {
        	$names = array();
            for ($i = 0; $i < count($fields); $i++) {
            	$names[] = $fields[$i]['name'];
            }

            return $names;
        }

        /**
         * DBSync::GetFieldIndex()
         * Return the index (array key) of the field with the given name
         *
         * @param	array	$fields		Field List
         * @param	string	$name		Field Name
         *
         * @access	private
         * @return 	array		Field Index
         **/
        function GetFieldIndex($fields, $name) {
            for ($i = 0; $i < count($fields); $i++) {
            	if ($fields[$i]['name'] == $name) {
                	return $i;
                }
            }
            return false;
        }

        /**
         * DBSync::GetPrimaryKeys()
         * Returns a list of field names wich are primary keys
         *
         * @param	array	$fields		Field List
         *
         * @access	private
         * @return 	array		Primary Keys Field List
         **/
        function GetPrimaryKeys($fields) {
        	$keys = array();
            for ($i = 0; $i < count($fields); $i++) {
            	if ($fields[$i]['key'] == 'PRI') {
	            	$keys[] = $fields[$i]['name'];
                }
            }

            return $keys;
        }

        /**
         * DBSync::DiferentKeys()
         * Compares two primary keys field lists and checks if
         * they are diferent from each other.
         *
         * @param	array	$fields_home	Primary Keys Field List 1
         * @param	array	$fields_sync	Primary Keys Field List 2
         *
         * @access	private
         * @return 	boolean		"Are diferent?"
         **/
        function DiferentKeys($keys_home, $keys_sync) {
        	if (count($keys_home) != count($keys_sync)) {
            	return true;
            }

            for ($i = 0; $i < count($keys_home); $i++) {
            	if ($keys_home[$i] != $keys_sync[$i]) {
                	return true;
                }
            }

            return false;
        }

        /**
         * DBSync::RaiseError()
         * Displays error message and aborts execution of the program
         *
         * @param	string	$description	Error description
         *
         * @access	private
         * @return 	void
         **/
        function RaiseError($description) {
        	echo "<h3>Error</h3><hr />\n" .
                 $description;
			exit(1);
        }
    }
?>