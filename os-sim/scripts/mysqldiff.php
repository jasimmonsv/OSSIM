<?php

ini_set('memory_limit','256M');

//Configuration Parameters

$db_tmp = "tmp_".date("d_m_Y_H_i");

//Database to analize
$dbs = array( 
	array('db' => 'datawarehouse', 'sql_file' => '00-create_datawarehouse_tbls_mysql.sql'),
	array('db' => 'ossim', 'sql_file' => '00-create_ossim_tbls_mysql.sql'),
	//array('db' => 'ossim_acl', 'sql_file' => '00-create_ossim_acl_tbls_mysql.sql')
	array('db' => 'snort', 'sql_file' => '00-create_snort_tbls_mysql.sql')
);

$path_sql_file = '/usr/share/ossim/db/';


//Exceptions (Field to drop and/or Field to rename)

$exceptions = array();
//$exceptions['test_1'][] = array('type'=> 'rename_field', 'table_name' => '', 'new_field' => '', 'old_field' => '');
//$exceptions['test_1'][] = array('type'=> 'drop_field', 'table_name' => '', 'field' => '');


function display_errors($errors)
{
	$keys = array_keys($errors);
	
	foreach ($keys as $k => $key)
	{
		$txt_to_print .= "\n\nERROR in function: ". $key."\n\n";
		$txt_to_print .= "\t".implode("\n\t", $errors[$key])."\n";
		$txt_to_print .="\n\n----------------------------------- Execution aborted ---------------------------------\n\n";
	}
	
	return $txt_to_print;
}

function display_info ($type, $info, $debug=0)
{
	switch ($type){
		case "1":
			if ( !empty($info) )
				$txt_to_print .= display_errors($info);
		break;
		
		case "2":
			$txt_to_print = ( $debug == 1 ) ? sprintf("-- %'-85s\n", $info) : "";
		break;
		
		case "3":
			$txt_to_print = "\n".$info;
		break;
		
		case "4":
			$txt_to_print = ( $debug == 1 ) ? "\n".$info : "";
		break;
										
	}
	return $txt_to_print;
}

$version=trim(`mysqld -V|awk '{print $3}'`);

if ( preg_match ('/^5.1/', $version ) == false )
{
	echo "\n\nYour version ( $version ) is too old, please upgrade it to last version\n\n\n";
	exit();
}


if ($argc == 2 && $argv[1] == "--h"){
	echo "\n\t\tHelp : php <options> $argv[0] [--h] \n\n";
	echo "\n\t\tUsage: php <options> $argv[0] [--debug]\n\n\n";
	exit();
}


if ($argc < 1 || $argc > 2){
	echo "\n\t\tERROR: Usage: php <options> $argv[0] [--debug]\n\n\n";
	exit(-1);
}

//Show debug messages

$debug = ($argv[1] == '--debug' ) ? 1 : 0;


$header  ="\n-- ************************************************************************************\n";
$header .="-- ****************************** MySQLDiff Version 1.0 *******************************\n";
$header .="-- ************************************************************************************\n\n";

echo display_info ('4', $header, $debug);

echo display_info ('4', "\n-- ".date("H:i:s")." Start MysqlDiff Version 1.0\n", $debug);

foreach ($dbs as $k => $db)
{
		
	$statements = array();
		
	echo display_info ('4', "\n-- Getting information from DATABASE ".$db['db']." ...\n", $debug);
	
	$info_db = new InfoDb($db['db'], $exceptions[$db['db']]);
	
	$errors = $info_db->getErrors();
	
	if ( !empty ($errors) )
	{
		echo display_info ('1', $errors);
		exit();
	}
	
	echo display_info ('2', 'OK', $debug);
	
	
	echo display_info ('4', "\n-- Creating TEMPORARY DATABASE $db_tmp ...\n", $debug);	
	
	$sql_file = $path_sql_file.$db['sql_file'];
	
	$info_db->createTmpDatabase($db_tmp, $sql_file);
	
	$errors = $info_db->getErrors();
	
	if ( !empty ($errors) )
	{
		echo display_info ('1', $errors);
		exit();
	}
		
	$info_db_tmp = new InfoDb($db_tmp, $exceptions[$db['db']]);
	
	$errors = $info_db_tmp->getErrors();
	
	
	if ( !empty ($errors) )
	{
		echo display_info ('1', $errors);
		exit();
	}
	
	echo display_info ('2', 'OK', $debug);
		
	echo display_info ('4', "\n-- STARTING SEARCH PROCESS (This process might take several minutes) ...\n", $debug);		
	
	$all_tables = $info_db_tmp->getTablesNames();

	$new_tables = $info_db->table_diff($all_tables);

	$old_tables = array_diff($all_tables, $new_tables);


	$num_new_tables = count($new_tables);
	
	$sql_new_tables = array();
	
	if ($num_new_tables > 0)
	{	
		//Exists any new table
		
		$st_fk = array();
				
		foreach ($new_tables as $k => $table){
			
			$sql = $info_db->createTable($info_db_tmp->getTable($table));
			
			$errors = $info_db->getErrors();
			if ( !empty ($errors) )
			{
				echo display_info ('1', $errors);
				exit();
			}
			
			if ( is_array($sql) )
			{
				if (count($sql) > 1)
				{
					$sql_new_tables[$table] = array_shift($sql);
					$st_fk = array_merge($st_fk, $sql);
				}
				else
					$sql_new_tables[$table] = $sql[0];
			}
									
		}
	}
	
	foreach ($old_tables as $k => $name)
	{
		$tablename = $name;
		$table = $info_db->getTable($name);
		$table_tmp = $info_db_tmp->getTable($name);
		
		// Compare Engine, charset, collation, autoincrement
		
		$parameters = $table->getParameters();
		$table_tmp->compareTableParameters($name, $parameters, $table);
		
				
		// Compare Fields
		
		$fields_tmp = $table_tmp->getFields();
		$fields = $table->getFields();
		
		foreach ($fields_tmp as $fieldname => $fieldname_tmp)
			$table_tmp->compareField($fieldname, $table);
		
		
		// Drop Fields
				
		$fields_diff = array_diff_key($fields, $fields_tmp);
		$drop_fieldname = array();
		
		foreach ($fields_diff as $fieldname => $field)
			$table->dropField($fieldname, $table_tmp);
		
					
		//Compare keys	
		
		$key_types = InfoTable::getKeysTypes();
		
		foreach ($key_types as $kt => $value)
		{
			$table_tmp->compareKeys($name, $table, $kt);
			
			$errors = $info_db_tmp->getErrors();

			if ( !empty($errors) )
			{
				echo display_info('2', 'Failure', 1);
				echo display_errors($errors);
				exit();
			} 
		}
						
		
		//Print ALTER STATEMENTS 
		
		$txt_to_print .= display_info ('4', "-- TESTING TABLE $tablename\n", $debug);
				
		$modification = false;
		
		$diff_n = $table_tmp->getDiffs()->getFieldsDiff('N');
		$diff_m = $table_tmp->getDiffs()->getFieldsDiff('M');
		$diff_d = $table_tmp->getDiffs()->getFieldsDiff('D');
			
		if ( !empty ($diff_d) )
		{
		   $txt_to_print .= display_info ('3', implode("\n",$diff_d));
		   $modification  = true;
		}
		
		if ( !empty ($diff_n) )
		{
		   $txt_to_print .= display_info ('3', implode("\n",$diff_n));
		   $modification  = true;
		}
		
		if ( !empty ($diff_m) )
		{
			$txt_to_print .= display_info ('3', implode("\n",$diff_m));
			$modification  = true;
		}
			
		
		if ( $modification == false )
		{
			$txt_to_print .= display_info ('4', "-- Fields\n", $debug);
			$txt_to_print .= display_info ('2', 'Unchanged', $debug);
		}
		else
			$txt_to_print .= "\n";
				
		
		$diff_table =$table_tmp->getDiffs()->getTableDiff();
		
		if (!empty ($diff_table ) )
		{
			$keys = InfoTable::getKeysTypes();
			$modification = false;
			
			foreach ($keys as $i => $key)
			{
				$diff_d = ($i != 'PRIMARY_KEY') ? $table_tmp->getDiffs()->getkeysDiff($i,'D') : "";
								
				if ( !empty ($diff_d) )
					$diff_intsec .= implode("\n",$diff_d);
				
			}
			
			if ( !empty ($diff_intsec) )
			{
				$txt_to_print .= display_info ('4', "\n-- Dropping necessary Keys\n", $debug);
				$txt_to_print .= display_info ('3', $diff_intsec);
				$modification = true;
			}
			
			$txt_to_print .= display_info ('4', "\n-- Changing Table parameters:\n", $debug);
			$txt_to_print .= display_info ('3', $diff_table."\n");
			
			
			$diff_intsec = '';
			
			foreach ($keys as $i => $key)
			{
				$diff_n = $table_tmp->getDiffs()->getkeysDiff($i,'N');
				$diff_m = $table_tmp->getDiffs()->getkeysDiff($i,'M');
				
				$diff_intsec .= ( !empty ($diff_n) ) ? implode("\n",$diff_n) : "";
				$diff_intsec .= ( !empty ($diff_m) ) ? implode("\n",$diff_m) : "";
			}
			
									
			if ( !empty ($diff_intsec) )
			{
				$txt_to_print .= display_info('3', "\n".$diff_intsec);
				$modification = true;	
			}
			
			if ($modification == false)
			{
				$txt_to_print .= display_info('4', "-- Keys\n", $debug);
				$txt_to_print .= display_info('2', 'Unchanged', $debug);
			}
			else
				$txt_to_print .= "\n";
			
			
		}
		
								
		if ( empty ($diff_table ) )
		{   
			
			$keys = InfoTable::getKeysTypes();
			$modification = false;
			
			foreach ($keys as $i => $key)
			{
				$diff_d = ($i != 'PRIMARY_KEY') ? $table_tmp->getDiffs()->getkeysDiff($i,'D') : "";
				$diff_n = $table_tmp->getDiffs()->getkeysDiff($i,'N');
				$diff_m = $table_tmp->getDiffs()->getkeysDiff($i,'M');
															
				if (!empty ($diff_d) || !empty ($diff_n) || !empty ($diff_m) )
				{
					if ( !empty ($diff_d) )
					{
						$txt_to_print .= display_info ('4', "\n-- Dropping $key\n", $debug);
						$txt_to_print .= display_info ('3', implode("\n",$diff_d))."\n";
						$modification = true;
					}
					
					if ( !empty ($diff_n) )
					{
						$txt_to_print .= display_info ('4', "\n-- Adding $key\n", $debug);
						$txt_to_print .= display_info('3', implode("\n",$diff_n))."\n";
						$modification = true;
					}
								   
					if ( !empty ($diff_m) )
					{
						$txt_to_print .= display_info ('4', "\n-- Modifying $key\n", $debug);
						$txt_to_print .= display_info('3', implode("\n",$diff_m));
						$modification = true;
					}
										
				}	
				
			}
			
			if ($modification == false)
			{
				$txt_to_print .= display_info('4', "-- Keys\n", $debug);
				$txt_to_print .= display_info('2', 'Unchanged', $debug);
			}
			else
				$txt_to_print .= "\n";
		
		}
		
		$diff_e = $table_tmp->getDiffs()->getFieldsDiff('E');
		
		if ( !empty ($diff_e) )
			$txt_to_print .=  display_info('3', implode("\n",$diff_e))."\n";
		
						
	}
	
	/***************************** PRE STATEMENTS *****************************/
	
	
	echo display_info('3', "\n\nUSE ".$db['db'].";\n");
	
	$diff_pre = Diffs::getSpecialDiff("PRE");
	
	if ( !empty ($diff_pre) )
	{
		echo display_info ('4', "\n-- ********************************** PRE Statements ***********************************\n\n", $debug);
		echo implode("\n", $diff_pre);
		echo display_info ('4', "\n\n", $debug);
	}
	
	unset($diff_pre);
	Diffs::removeSpecialDiff("PRE");
	
	
	if ( $num_new_tables > 0 )
	{
		echo display_info ('4', "\n-- Searching New tables ... ( $num_new_tables found ) \n", $debug);
		echo display_info ('3', implode("\n\n", $sql_new_tables))."\n\n";
	}
	
	unset($sql_new_tables);
	unset($num_new_tables);
	
	echo trim($txt_to_print);
	unset($txt_to_print);
	
	
	if ( !empty ($st_fk) )
	{
		echo display_info ('4', "Creating Foreign Key new table(s): \n\n", $debug);
		echo display_info ('3', implode("\n\n",$st_fk) );
	}
	
	/***************************** POST STATEMENTS *****************************/
	
	
	$diff_post = Diffs::getSpecialDiff("POST");
	
	if ( !empty ($diff_post) )
	{
		echo display_info ('4', "\n-- ********************************** POST Statements ***********************************\n\n", $debug);
		echo implode("\n", $diff_post);
		echo display_info ('4', "\n\n", $debug);
	}
	
	Diffs::removeSpecialDiff("PRE");
	unset($diff_post);
	

	if ( $info_db_tmp->dropDb() == false )
	{	
		echo "\n\n-- ERROR: Failure to drop temporary database name\n\n";
		exit();
	}
	

	
}

echo display_info ('4',  "\n-- ".date("H:i:s")." MysqlDiff Version 1.0 Completed\n", $debug);




/********************************************************
********************* Class Used ************************
*********************************************************/

/**
* Class: Conf
* Description: Class which contains the connection parameters of a database
*/


Class Conf{ 
	private $_userdb; 
	private $_passdb; 
	private $_hostdb; 
	static $_instance; 
 
	private function __construct(){ 
		
		$this->_userdb= trim(`grep ^user= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`); 
		$this->_passdb= trim(`grep ^pass= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`); 
		$this->_hostdb= trim(`grep ^db_ip= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
				
	} 

	private function __clone(){ } 
 
	public static function getInstance(){ 
		if (!(self::$_instance instanceof self)) 
			self::$_instance=new self(); 
		
		return self::$_instance; 
	} 

	public function getUserDB(){ 
		return $this->_userdb; 
	} 
 
	public function getHostDB(){ 
		return $this->_hostdb; 
	} 

	public function getPassDB(){ 
		return $this->_passdb; 
	} 
}

/**
* Class: Database
* Description: Class for connecting to databases.
*/

class DataBase {
  	
	private $hostname; 
	private $user; 
	private $password;
	private $dbname;	
	private $conexion;
	private $resource;
	private $sql;
	public static $queries;
	private static $_singleton;
			
	private function setConexion($db_name){ 
		$conf = Conf::getInstance(); 
		$this->hostname=$conf->getHostDB(); 
		$this->user=$conf->getUserDB(); 
		$this->password=$conf->getPassDB(); 
		$this->dbname=$db_name; 
	} 
	
		
	public static function getInstance($db_name){
				
		if (is_null (self::$_singleton) ) 
			self::$_singleton = new DataBase($db_name);
		
		return self::$_singleton;
	}

	public function __construct($db_name){
		$this->setConexion($db_name); 
		$this->conexion = mysql_connect($this->hostname, $this->user, $this->password, new_link);
		mysql_select_db($this->dbname, $this->conexion);
		$this->queries = 0;
		$this->resource = null;
	}

	public function execute(){
		
		if(!($this->resource = mysql_query($this->sql, $this->conexion)))
			return null;
				
		$this->queries++;
		return $this->resource;
	}

	public function alter(){
		if(!($this->resource = mysql_query($this->sql, $this->conexion)))
			return false;
		
		return true;
	}

	public function loadObjectList(){
		if (!($cur = $this->execute()))
			return null;
		
		$array = array();
		while ($row = @mysql_fetch_object($cur))
			$array[] = $row;
		
		return $array;
	}
	
	public function loadArray(){
		if (!($cur = $this->execute()))
			return null;
		
		$array = array();
		while ($row = @mysql_fetch_assoc($cur))
			$array[] = $row;
		
		return $array;
	}

	public function setQuery($sql){
		if(empty($sql))
			return false;
		
		$this->sql = $sql;
		return true;
	}

	public function freeResults(){
		@mysql_free_result($this->resource);
		return true;
	}

	public function loadObject(){
		if ($cur = $this->execute()){
			if ($object = mysql_fetch_object($cur)){
				@mysql_free_result($cur);
				return $object;
			}
			else 
				return null;
		}
		else 
			return false;
		
	}
	
	public function getDbName(){ return $this->dbname; } 
	
	public function getHostName(){ return $this->hostname; }
	
	public function getPassword(){ return $this->password; }
	
	public function getUser(){ return $this->user; }
	
	function __destruct(){
		@mysql_free_result($this->resource);
		@mysql_close($this->conexion);
	}
	
}


/**
* Class: InfoTable
* Description: Class which contains all information from a table in a database
*/


Class InfoTable{ 
	private $_db;
	private $_tablename;
	private $_dbname;
	private $_fields;
	private $_keys;
	private $_errors;
	private $_engine;
	private $_charset;
	private $_collation;
	//private $_autoincrement;
	private $_exceptions;
	private $_diffs;
	private static $_key_types = array('PRIMARY_KEY'=>'PRIMARY KEY', 'UNIQUE_KEY'=>'UNIQUE', 'INDEX'=>'INDEX', 'FULLTEXT_KEY' => 'FULLTEXT', 'FOREIGN_KEY'=>'FOREIGN KEY');
	
	 
	public function __construct($db_name, $table_name, $exceptions){
		$this->_tablename = $table_name;
		$this->_dbname = $db_name;
		$this->_db = Database::getInstance($this->_dbname);
		$this->extractFields();
		$this->extractParameters();
		foreach (self::$_key_types as $k)
			$this->_keys[$k] = array();
		$this->setkeys(); 
		$this->_exceptions = ( empty ($exceptions) ) ? array() : $exceptions;
		$this->_diffs = new Diffs();
		$this->_db->freeResults();
	}
	
	
	public function getTableName(){ return $this->_tablename;}
	
	public function getDbName(){ return $this->_dbname;}

	public function getFields(){ return $this->_fields;}
	
	public function getField($name){ 
		
		$field = array();
	   
		if ( !empty($name) && array_key_exists($name, $this->_fields) )
			$field = $this->_fields[$name];

		return $field;
	}
	
	public function getEngine(){ return $this->_engine;}
	
	public function getCharSet(){ return $this->_charset;}
	
	public function getDiffs(){ return $this->_diffs;}
	
	public function getAutoIncrement(){ return $this->_autoincrement;}
	
	public function getExceptions($exception_type=''){
		
		$exceptions = array();
		
		if ( empty($exception_type) )	
		   $exceptions = $this->_exceptions;
		else
		{
			$exceptions = $this->_exceptions;
			
			foreach ($exceptions as $k => $exception)
			{
				if ( $exception['type'] == $exception_type)
					$exceptions[] = $exception;	
			}
		}
		
		return $exceptions;
	}
	
	public static function getKeysTypes(){
		return self::$_key_types;
	}
		
	public function getKeys($type){
		
		$keys = array();
		
		if ( array_key_exists($type, self::$_key_types) )
			$keys = $this->_keys[$type];					
				
		return $keys;
	}
	
	
	public function setkeys(){
		
		//FOREIGN KEYS
		
		if ( $this->getEngine() != 'MyISAM' )
		{
			$sql = "SELECT A.CONSTRAINT_NAME, A.UNIQUE_CONSTRAINT_NAME, A.MATCH_OPTION, A.UPDATE_RULE, A.DELETE_RULE, 
				A.REFERENCED_TABLE_NAME, B.COLUMN_NAME, B.REFERENCED_COLUMN_NAME, B.REFERENCED_TABLE_SCHEMA 
				FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS A, INFORMATION_SCHEMA.KEY_COLUMN_USAGE B
				WHERE A.TABLE_NAME = B.TABLE_NAME
				AND A.CONSTRAINT_SCHEMA = B.TABLE_SCHEMA 
				AND A.CONSTRAINT_NAME = B.CONSTRAINT_NAME
				AND A.TABLE_NAME = '".$this->_tablename."' AND A.CONSTRAINT_SCHEMA = '".$this->_dbname."';";
			
												
			if ( $this->_db->setQuery($sql) )
			{
				$fks = $this->_db->loadObjectList();
				
				if ($fks != null){
					foreach ($fks as $fk){
						$this->_keys['FOREIGN_KEY'][$fk->COLUMN_NAME]['CONSTRAINT_NAME']			= $fk->CONSTRAINT_NAME;
						$this->_keys['FOREIGN_KEY'][$fk->COLUMN_NAME]['UNIQUE_CONSTRAINT_NAME']		= $fk->UNIQUE_CONSTRAINT_NAME;
						$this->_keys['FOREIGN_KEY'][$fk->COLUMN_NAME]['MATCH_OPTION']				= $fk->MATCH_OPTION;
						$this->_keys['FOREIGN_KEY'][$fk->COLUMN_NAME]['UPDATE_RULE']				= $fk->UPDATE_RULE;
						$this->_keys['FOREIGN_KEY'][$fk->COLUMN_NAME]['DELETE_RULE']				= $fk->DELETE_RULE;
						$this->_keys['FOREIGN_KEY'][$fk->COLUMN_NAME]['COLUMN_NAME']				= $fk->COLUMN_NAME;
						$this->_keys['FOREIGN_KEY'][$fk->COLUMN_NAME]['REFERENCED_TABLE_SCHEMA']	= $fk->REFERENCED_TABLE_SCHEMA;
						$this->_keys['FOREIGN_KEY'][$fk->COLUMN_NAME]['REFERENCED_TABLE_NAME']		= $fk->REFERENCED_TABLE_NAME;
						$this->_keys['FOREIGN_KEY'][$fk->COLUMN_NAME]['REFERENCED_COLUMN_NAME']		= $fk->REFERENCED_COLUMN_NAME;
					}
					$this->_db->freeResults();
				}
				else
				{
					if (mysql_errno() != 0)
						$this->setErrors("setkeys", "Failed to set Foreign keys for table ".$this->_tablename." (Code. 2)");
				}
			}
			else
				$this->setErrors("setkeys", "Failed to set Foreign keys for table ".$this->_tablename." (Code. 1)");
		}
		
			
		//PRIMARY KEYS
			
		foreach ($this->_fields as $k => $value)
		{
			if ($this->_fields[$k]['COLUMN_KEY'] == 'PRI')
				$this->_keys['PRIMARY_KEY'][$k] = $k;
		}
		
		
		//FULLTEXT KEYS
		
		if ( $this->getEngine() == 'MyISAM' )
		{
			$sql = "SHOW INDEX FROM `".$this->_dbname."`.`".$this->_tablename."` WHERE INDEX_TYPE = 'FULLTEXT';";
							
			if ( $this->_db->setQuery($sql) )
			{
				$ftks= $this->_db->loadObjectList();
				
				if ($ftks != null){
					foreach ($ftks as $ftk)
						$this->_keys['FULLTEXT_KEY'][$ftk->Key_name][]= $ftk->Column_name;
					
					$this->_db->freeResults();
				}
				else
				{	
					if (mysql_errno() != 0)
						$this->setErrors("setkeys", "Failed to set FullText keys for table ".$this->_tablename." (Code. 2)");
				}
				
			}
			else
				$this->setErrors("setkeys", "Failed to set FullText keys for table ".$this->_tablename." (Code. 1)");
		}	
			
		//UNIQUE KEYS
		$sql = "SELECT A.index_name, A.column_name, A.sub_part
					FROM information_schema.STATISTICS A, information_schema.TABLE_CONSTRAINTS B
					WHERE A.table_name = B.table_name
					AND A.table_schema = B.table_schema AND A.index_name = B.constraint_name
					AND B.table_schema = '".$this->_dbname."' AND B.table_name = '".$this->_tablename."'
					AND B.CONSTRAINT_TYPE = 'UNIQUE' ORDER BY A.index_name;";
		
		
		if ( $this->_db->setQuery($sql) )
		{
			
			$uks= $this->_db->loadObjectList();
						
			if ($uks != null){
				foreach ($uks as $uk){
					
					if ($uk->sub_part != null)
						$size = "(".$uk->sub_part.")";
					
					$this->_keys['UNIQUE_KEY'][$uk->index_name][]= $uk->column_name.$size;
				}
								
				$this->_db->freeResults();
			}
			else
			{	
				if (mysql_errno() != 0)
					$this->setErrors("setkeys", "Failed to set Unique keys for table ".$this->_tablename." (Code. 2)");
			}
			
		}
		else
			$this->setErrors("setkeys", "Failed to set Unique keys for table ".$this->_tablename." (Code. 1)");	
			
			
		//KEY (INDEX)
		
				
		$sql = "SHOW index FROM `".$this->_dbname."`.`".$this->_tablename."` WHERE Non_unique = 1 and Index_type = 'BTREE'";		
							
		if ( $this->_db->setQuery($sql) )
		{
			$keys= $this->_db->loadObjectList();
			
			if ($keys != null){
				foreach ($keys as $key){
					if ($key->sub_part != null)
						$size = "(".$key->Sub_part.")";
					
					$this->_keys['INDEX'][$key->Key_name][]= $key->Column_name.$size;
				}
				
				$this->_db->freeResults();
			}
			else
			{	
				if (mysql_errno() != 0)
					$this->setErrors("setkeys", "Failed to set Index for table ".$this->_tablename." (Code. 2)");
			}
		}
		else
			$this->setErrors("setkeys", "Failed to set Index for table ".$this->_tablename." (Code. 1)");	
		
	}
		
		
	public function getErrors($type=''){
		
		return ( empty($type) ) ? $this->_errors : $this->_errors[$type];
	}
	
	public function setErrors($type, $error){
		
		if (!empty($type) && !empty($error))
			$this->_errors[$type][] = $error;
	}
	
	
	private function extractFields(){
		
       	$fields = array();
		$sql = "SELECT COLUMN_NAME,COLUMN_DEFAULT,IS_NULLABLE,DATA_TYPE, COLLATION_NAME,CHARACTER_SET_NAME,COLUMN_TYPE,COLUMN_KEY,EXTRA
				FROM INFORMATION_SCHEMA.COLUMNS
				WHERE TABLE_NAME = '".$this->_tablename."' AND TABLE_SCHEMA = '".$this->_dbname."';";
		
		if ( $this->_db->setQuery($sql) )
		{
			$fields = $this->_db->loadObjectList();
			$previous_field = '';	
			
			if ($fields != null){
				foreach ($fields as $field){
					
					$previous_field = ( empty($previous_field) ) ? "NONE" : $this->_fields[$pf]['COLUMN_NAME'];
					$this->_fields[$field->COLUMN_NAME]['COLUMN_NAME']= $field->COLUMN_NAME;
					$this->_fields[$field->COLUMN_NAME]['DATA_TYPE']= $field->DATA_TYPE;
					$this->_fields[$field->COLUMN_NAME]['COLUMN_TYPE']= $field->COLUMN_TYPE;
					$this->_fields[$field->COLUMN_NAME]['COLUMN_KEY']= $field->COLUMN_KEY;
					$this->_fields[$field->COLUMN_NAME]['COLUMN_DEFAULT']= ( ($field->COLUMN_DEFAULT !== null) ? $field->COLUMN_DEFAULT : "NONE");
					$this->_fields[$field->COLUMN_NAME]['IS_NULLABLE']= ( $field->IS_NULLABLE == 'NO' ) ? "NOT NULL" : "NULL";
					$this->_fields[$field->COLUMN_NAME]['COLLATION_NAME']= ( !empty($field->COLLATION_NAME) ? $field->COLLATION_NAME : "NONE");
					$this->_fields[$field->COLUMN_NAME]['CHARACTER_SET_NAME']= ( !empty($field->CHARACTER_SET_NAME) ? $field->CHARACTER_SET_NAME : "NONE");
					$this->_fields[$field->COLUMN_NAME]['EXTRA']= ( !empty($field->EXTRA) ? $field->EXTRA : "NONE");
					$this->_fields[$field->COLUMN_NAME]['PREVIOUS_FIELD']= $previous_field;
					$pf = $field->COLUMN_NAME;
				}
				
				$this->_db->freeResults();
				return true;
			}
			else{
				$this->setErrors("setkeys", "Failed to create class InfoTable for table ".$this->_tablename." (Code. 2)");
				return false;
			}
					
		}
		else{
			$this->setErrors("setkeys", "Failed to create class InfoTable for table ".$this->_tablename." (Code. 1)");
			return false;
		}
		
	}
	
	
	private function extractReferences ($field_name)
	{
		$fields = array();
		
		$sql_2 = "SELECT A.CONSTRAINT_SCHEMA, A.TABLE_NAME, A.CONSTRAINT_NAME, A.UNIQUE_CONSTRAINT_NAME, A.MATCH_OPTION, 
				A.UPDATE_RULE, A.DELETE_RULE, A.REFERENCED_TABLE_NAME,
				B.COLUMN_NAME, B.REFERENCED_COLUMN_NAME, B.REFERENCED_TABLE_SCHEMA
				FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS A, INFORMATION_SCHEMA.KEY_COLUMN_USAGE B
				WHERE A.TABLE_NAME = B.TABLE_NAME
				AND A.CONSTRAINT_SCHEMA = B.TABLE_SCHEMA
				AND A.CONSTRAINT_NAME = B.CONSTRAINT_NAME
				AND B.REFERENCED_TABLE_NAME = '".$this->_tablename."'
				AND B.REFERENCED_TABLE_SCHEMA = '".$this->_dbname."'
				AND B.REFERENCED_COLUMN_NAME = '".$field_name."';";
					
		if ( $this->_db->setQuery($sql_2) )
		{
			$info_rf = $this->_db->loadObjectList();
			
			if ($info_rf != null)	
			{
				foreach ($info_rf as $info)
				{	
					$this->_fields[$field_name]['REFERENCES'][$info->TABLE_NAME."-".$info->COLUMN_NAME]['CONSTRAINT_SCHEMA'] = $info->CONSTRAINT_SCHEMA;
					$this->_fields[$field_name]['REFERENCES'][$info->TABLE_NAME."-".$info->COLUMN_NAME]['TABLE_NAME'] = $info->TABLE_NAME;
					$this->_fields[$field_name]['REFERENCES'][$info->TABLE_NAME."-".$info->COLUMN_NAME]['COLUMN_NAME'] = $info->COLUMN_NAME;
					$this->_fields[$field_name]['REFERENCES'][$info->TABLE_NAME."-".$info->COLUMN_NAME]['CONSTRAINT_NAME'] = $info->CONSTRAINT_NAME;
					$this->_fields[$field_name]['REFERENCES'][$info->TABLE_NAME."-".$info->COLUMN_NAME]['MATCH_OPTION'] = $info->MATCH_OPTION;
					$this->_fields[$field_name]['REFERENCES'][$info->TABLE_NAME."-".$info->COLUMN_NAME]['UPDATE_RULE'] = $info->UPDATE_RULE;
					$this->_fields[$field_name]['REFERENCES'][$info->TABLE_NAME."-".$info->COLUMN_NAME]['DELETE_RULE'] = $info->DELETE_RULE;
					$this->_fields[$field_name]['REFERENCES'][$info->TABLE_NAME."-".$info->COLUMN_NAME]['REFERENCED_TABLE_SCHEMA'] = $info->REFERENCED_TABLE_SCHEMA;
					$this->_fields[$field_name]['REFERENCES'][$info->TABLE_NAME."-".$info->COLUMN_NAME]['REFERENCED_TABLE_NAME'] = $info->REFERENCED_TABLE_NAME;
					$this->_fields[$field_name]['REFERENCES'][$info->TABLE_NAME."-".$info->COLUMN_NAME]['REFERENCED_COLUMN_NAME'] = $info->REFERENCED_COLUMN_NAME;
				
				}
								
			}
			else
				$this->_fields[$field_name]['REFERENCES'] = 'NONE';
									
		}
		else{
			$this->setErrors("extractReferences", "Failed to create class InfoTable for table ".$this->_tablename." (Code. 3)");
			return false;
		}
	}
	
	
	private function extractParameters(){
		
		$parameters='';
		
       	$sql = "SELECT TABLE_SCHEMA, TABLE_NAME,ENGINE,TABLE_TYPE,AUTO_INCREMENT, CHARACTER_SET_NAME, TABLE_COLLATION
				FROM INFORMATION_SCHEMA.TABLES A, INFORMATION_SCHEMA.COLLATIONS B
				WHERE TABLE_NAME = '".$this->_tablename."' AND TABLE_SCHEMA = '".$this->_dbname."' AND A.TABLE_COLLATION = B.COLLATION_NAME;";
		
		if ( $this->_db->setQuery($sql) )
		{
			$parameters = $this->_db->loadObjectList();
									
			if ( $parameters  != null){
				
				$this->_engine    = $parameters[0]->ENGINE;
				$this->_collation = $parameters[0]->TABLE_COLLATION;
				$this->_charset   = $parameters[0]->CHARACTER_SET_NAME;
				//$this->_autoincrement = $parameters[0]->AUTO_INCREMENT;
				$this->_db->freeResults();
				return true;
			}
			else{
				$this->setErrors("extractParameters", "Failed to create class InfoTable for table ".$this->_tablename." (Code. 5)");
				return false;
			}
					
		}
		else{
			$this->setErrors("extractParameters", "Failed to create class InfoTable for table ".$this->_tablename." (Code. 4)");
			return false;
		}
		
	}
	
	
	public function getParameters(){
	
		$parameters['ENGINE']= $this->_engine;
		$parameters['TABLE_COLLATION']=$this->_collation;
		$parameters['CHARACTER_SET_NAME']=$this->_charset;
		//$parameters['AUTO_INCREMENT']= ( !empty($this->_autoincrement) ) ? $this->_autoincrement : "NONE";
		return $parameters;
	}
	

	public function compareField($name, $table)
	{
	   	$diffs = array();
		$differences = array();
		
		$tmp_field = $this->getField($name);
		$field     = $table->getField($name);
        $diff      = "";	
		$sentence  = "";

		$pos = ($tmp_field['PREVIOUS_FIELD'] == "NONE") ? " FIRST" : " AFTER `".$tmp_field['PREVIOUS_FIELD']."`";		
		
		
		if ( empty($field) )
		{
	        // Field no exists in table
			$exceptions = $this->checkException('rename_field', $tmp_field['COLUMN_NAME']);
			
						
			if ( $exceptions !== false )
			{
			   	if (  $table->getEngine() != 'MyISAM' && $table->isKey($exceptions['old_field'], 'FOREIGN_KEY') )
				{
				   $fk = $table->getKeys('FOREIGN_KEY');
				   $sentence = "ALTER TABLE `". $table->_tablename."` DROP FOREIGN KEY `".$fk[$exceptions['old_field']]['CONSTRAINT_NAME']."`;";
				   Diffs::addSpecialDiff ("PRE", $sentence);
				}
							
				$sentence = "ALTER TABLE `". $table->_tablename."` CHANGE `". $exceptions['old_field']."` `".$exceptions['new_field']."` ".$tmp_field['COLUMN_TYPE'];
				$diff = "M";	
			}
			else
			{
				$sentence = "ALTER TABLE `". $table->_tablename."` ADD `".$tmp_field['COLUMN_NAME']."` ".$tmp_field['COLUMN_TYPE'];
				$diff = "N";	
			}
		}
		else
		{
			$differences = array_diff ($tmp_field, $field);
			
			if ( !empty($differences) )
			{
				if ( $table->getEngine() != 'MyISAM' )
				{
					if ( $table->getEngine() != 'MyISAM' && $table->isKey($field['COLUMN_NAME'], 'FOREIGN_KEY') )
					{
						$fk = $this->getKeys('FOREIGN_KEY');
						$sentence = "ALTER TABLE `". $table->_tablename."` DROP FOREIGN KEY `".$fk[$field['COLUMN_NAME']]['CONSTRAINT_NAME']."`;";
						Diffs::addSpecialDiff ("PRE", $sentence);
						
						$sentence  = "ALTER TABLE `".$table->_tablename."` ADD FOREIGN KEY (`".$field['COLUMN_NAME']."`) REFERENCES `";
						$sentence .= $table->_dbname."`.`".$fk[$field['COLUMN_NAME']]['REFERENCED_TABLE_NAME']."` (`".$fk[$field['COLUMN_NAME']]['REFERENCED_COLUMN_NAME']."`)";
						
						$sentence .= ( !empty( $fk[$field['COLUMN_NAME']]['UPDATE_RULE']) ) ? " ON UPDATE ".$fk[$field['COLUMN_NAME']]['UPDATE_RULE'] : "";
						$sentence .= ( !empty( $fk[$field['COLUMN_NAME']]['DELETE_RULE']) ) ? " ON DELETE ".$fk[$field['COLUMN_NAME']]['DELETE_RULE'] : "";
						
						Diffs::addSpecialDiff ("POST", $sentence.";");
					}
					else
					{
						$table->extractReferences($name);
						$field = $table->getField($name);
						
						if ( $field['REFERENCES'] != 'NONE' )
						{
							foreach ($field['REFERENCES'] as $k => $v)
							{
								$sentence = "ALTER TABLE `". $v['TABLE_NAME']."` DROP FOREIGN KEY `".$v['CONSTRAINT_NAME']."`;";
								Diffs::addSpecialDiff ("PRE", $sentence);
								
								$sentence  = "ALTER TABLE `".$v['TABLE_NAME']."` ADD FOREIGN KEY (`".$v['COLUMN_NAME']."`) REFERENCES `";
								$sentence .= $table->_dbname."`.`".$v['REFERENCED_TABLE_NAME']."` (`".$v['REFERENCED_COLUMN_NAME']."`)";
								
								$sentence .= ( !empty( $v['UPDATE_RULE']) ) ? " ON UPDATE ".$v['UPDATE_RULE'] : "";
								$sentence .= ( !empty( $v['DELETE_RULE']) ) ? " ON DELETE ".$v['DELETE_RULE'] : "";
								
								Diffs::addSpecialDiff ("POST", $sentence.";");
							}
						}
					}				
				}
				
				// Field exists in table although is different
				$sentence  = "ALTER TABLE `". $this->_tablename."` CHANGE `". $field['COLUMN_NAME'] ."` `".$tmp_field['COLUMN_NAME']."` ".$tmp_field['COLUMN_TYPE'];
				$diff = "M";	
			}
		}	

		if ($diff == "M" || $diff == "N")
		{
			
			$charset   = ( $tmp_field['CHARACTER_SET_NAME'] != 'NONE' ) ? " CHARACTER SET ".$tmp_field['CHARACTER_SET_NAME'] : "";

			$collate   = ( $tmp_field['COLLATION_NAME'] != 'NONE' ) ? " COLLATE ".$tmp_field['COLLATION_NAME'] : "";
			
			$nullable  = " ".$tmp_field['IS_NULLABLE'];
			
			if( $tmp_field['COLUMN_DEFAULT'] == "NONE" ) 
				$default = "";
			else if ( $tmp_field['COLUMN_DEFAULT'] == "CURRENT_TIMESTAMP" )
				$default = " DEFAULT CURRENT_TIMESTAMP";
			else
				$default = " DEFAULT '".$tmp_field['COLUMN_DEFAULT']."'";
							
			$extra     =  ( $tmp_field['EXTRA'] == "NONE" ) ? "" : " ".$tmp_field['EXTRA'];
			
			if (trim($extra) == 'auto_increment')
			{
				$sentence_aux = "ALTER TABLE `". $this->_tablename."` CHANGE `". $tmp_field['COLUMN_NAME'] ."` `".$tmp_field['COLUMN_NAME']."` ".$tmp_field['COLUMN_TYPE'].$extra;
				$sentence_aux  = trim($sentence_aux).";";
				$this->_diffs->addFieldDiff('E', $sentence_aux );
				
				$sentence .= $charset.$collate.$nullable.$default.$pos;
			}
			else
				$sentence .= $charset.$collate.$nullable.$default.$extra.$pos;
			
			$sentence  = trim($sentence).";";
			$this->_diffs->addFieldDiff($diff, $sentence);
			
			return false;
		
		}
		
		return true;
	
	}
	
	public function dropField($name_field, $table_tmp)
	{
	    $sql = '';	

		$exceptions = $this->checkException('drop_field', $name_field);
		
		if ( $exceptions !== false )
		{		
		   	if ( $this->getEngine() != 'MyISAM' && $this->isKey($name_field, 'FOREIGN_KEY') )
			{
			   $fk  = $this->getKeys('FOREIGN_KEY');
			   $sentence = "ALTER TABLE `". $this->_tablename."` DROP FOREIGN KEY `".$fk[$name_field]['CONSTRAINT_NAME']."`;";
			   Diffs::addSpecialDiff ("PRE", $sentence);
			}
			
			$sql = "ALTER TABLE `". $this->_tablename."` DROP `".$name_field."`;";
			$table_tmp->_diffs->addFieldDiff("D", $sql);
			
			return true;
		}
		
		return false;
	}
	
	
	public function compareTableParameters($name, $parameters, $table)
	{
	    $differences = array();
		
		$tmp_parameters = $this->getParameters();
				
		$differences = array_diff ($tmp_parameters, $parameters);
		
		if ( !empty($differences) && !empty($parameters) )
		{
			$sql = "ALTER TABLE `".$table->_dbname."`.`". $table->_tablename."`";
			
			$engine = " ENGINE=".$tmp_parameters['ENGINE'];
			
			$charset = "  DEFAULT CHARACTER SET ".$tmp_parameters['CHARACTER_SET_NAME']." COLLATE ".$tmp_parameters['TABLE_COLLATION'];
			
			//$autoincrement = ($tmp_parameters['AUTO_INCREMENT'] != 'NONE') ? " AUTO_INCREMENT =".$tmp_parameters['AUTO_INCREMENT'] : "";

			//$sentence = trim($sql.$engine.$charset.$autoincrement).";";
			
			$sentence = trim($sql.$engine.$charset).";";
			
			$this->_diffs->addTableDiff($sentence);
			
			return false;
		}
		
		return true;
	}


	public function compareKeys($name, $table, $key_type)
	{
	   	if ( !array_key_exists($key_type, InfoTable::$_key_types) )
		{
			$this->setErrors('keys', 'Invalid key type');
			return false;
        }
		
				
		$tmp_keys = $this->getKeys($key_type);
		$tmp_keys = ( empty ($tmp_keys) ) ? array() : $tmp_keys;
		
				
		$keys = $table->getKeys($key_type);
		$keys = ( empty ($keys) ) ? array() : $keys;
		
		switch ($key_type){
		
			case "PRIMARY_KEY":
							
				if( empty($tmp_keys) )
				{
					$this->setErrors('compareKeys', 'Primary key does not exist');
					return -1;
				}
		
				$diff_pk_1 = array_diff($tmp_keys, $keys);
				$diff_pk_2 = array_diff($keys, $tmp_keys);

		
				if ( !empty($diff_pk_1) || !empty($diff_pk_2))
				{
				   
				   $ex_keys = array_intersect($keys, $tmp_keys);
				   
					if (!empty($ex_keys))
					{
						foreach ($ex_keys as $k => $value)
						{
							if ($table->getEngine() != 'MyISAM' && $table->isKey($value,'FOREIGN_KEY'))
							{
								$fk = $table->getKeys('FOREIGN_KEY');
								$sentence = "ALTER TABLE `". $table->_tablename."` DROP FOREIGN KEY `".$fk[$value]['CONSTRAINT_NAME']."`;";
								Diffs::addSpecialDiff ("PRE", $sentence);
							}
						}
					}
					
					$sentence  = "ALTER TABLE `". $table->_tablename."` ";
				   
				    $pt_et = false;
					foreach ($tmp_keys as $k => $name_field)
					{
						$exceptions = $this->checkException('drop_field', $name_field);

							if ( $exceptions !== false )							{
								$pt_et = true;
								break;
							}
					}
				   
				    $sentence .= ( $pt_et == true ) ? "DROP PRIMARY KEY," : "";
				    $sentence .= "ADD PRIMARY KEY(`".implode("`,`", $tmp_keys)."`);";	
				   
					$this->_diffs->addKeyDiff("PRIMARY_KEY", "N", $sentence);
				   
				   
					if (!empty($ex_keys))
					{
						foreach ($ex_keys as $k => $value)
						{
							if ( $table->getEngine() != 'MyISAM' && $table->isKey($value, 'FOREIGN_KEY') )
							{
								$fk = $table->getKeys('FOREIGN_KEY');
								$sentence  = "ALTER TABLE `".$table->_tablename."` ADD FOREIGN KEY (`".$fk[$value]['COLUMN_NAME']."`) REFERENCES `";
								$sentence  .= $table->_dbname."`.`".$fk[$value]['REFERENCED_TABLE_NAME']."` (`".$fk[$value]['REFERENCED_COLUMN_NAME']."`)";
								
								$sentence .= ( !empty( $fk[$value]['UPDATE_RULE']) ) ? " ON UPDATE ".$fk[$value]['UPDATE_RULE'] : "";
								$sentence .= ( !empty( $fk[$value]['DELETE_RULE']) ) ? " ON DELETE ".$fk[$value]['DELETE_RULE'] : "";
								
								Diffs::addSpecialDiff ("POST", trim($sentence).";");
							}
						}
					}
					
					return false;		   
				}
				else
					return true;
				
				break;
								
				
				case "FOREIGN_KEY":
									
					//Drop foreign keys not used
					$differences_2 = array_diff_key($keys, $tmp_keys);
					
					if ( !empty($differences_2) )
					{
						foreach ($differences_2 as $k => $fk)
							$sql = "ALTER TABLE `". $this->_tablename."` DROP FOREIGN KEY `".$fk['CONSTRAINT_NAME']."`;";
							$this->_diffs->addKeyDiff("FOREIGN_KEY", "D", $sql);
					}
					

					//Add new foreign keys 		
					$differences = array_diff_key($tmp_keys, $keys);
					
										
					if ( !empty($differences) )
					{
						foreach ($differences as $k => $fk)
						{
							$sentence  = "ALTER TABLE `".$table->_tablename."` ADD FOREIGN KEY (`".$fk['COLUMN_NAME']."`) REFERENCES `";
							$sentence .= $table->_dbname."`.`".$fk['REFERENCED_TABLE_NAME']."` (`".$fk['REFERENCED_COLUMN_NAME']."`)";
							
							$sentence .= ( !empty( $fk['UPDATE_RULE']) ) ? " ON UPDATE ".$fk['UPDATE_RULE'] : "";
							$sentence .= ( !empty( $fk['DELETE_RULE']) ) ? " ON DELETE ".$fk['DELETE_RULE'] : "";
							
							$sql = trim($sentence).";";
							$this->_diffs->addKeyDiff("FOREIGN_KEY", "N", $sql);
						}
					}
					
					if ( !empty($sql) )
						return false;
					else
						return true;
				
				break;
										
				default:
					
					//Drop unique keys, index and fulltext index not used
					
					$differences_2 = array_diff_key($keys, $tmp_keys);
					
					if ( !empty($differences_2) )
					{
						foreach ($differences_2 as $k => $ok)
						{
							$ok_et = false;
							
							foreach ($ok as $j => $name_field)
							{
								$exceptions = $this->checkException('drop_field', $name_field);

								if ( $exceptions == false ){
									$ok_et = true;
									break;
								}
							}
							
							
							if ($ok_et == true){
								$sql  = "ALTER TABLE `". $this->_tablename."` DROP INDEX `".$k."`;";
								$this->_diffs->addKeyDiff("INDEX", "D", $sql);
							}
						}
					}
					
					//Add new unique keys, index and fulltext index 
					
					$differences = array_diff_key($tmp_keys, $keys);
					
					if ( !empty($differences) )
					{
					   	foreach ($differences as $k => $uk)
						{
							$ikey  = $table->getKeyName($k, 'INDEX');
							$ukey  = $table->getKeyName($k, 'UNIQUE_KEY');
							$ftkey = $table->getKeyName($k, 'FULLTEXT_KEY');
							
							$index = array_merge($ikey, $ukey, $ftkey);
							
							if ( !empty($index) )
								$sql_drop = "ALTER TABLE `". $this->_tablename."` DROP INDEX `".$k."`;\n";
														
							$sql = $sql_drop."ALTER TABLE `".$table->_dbname."`.`".$table->_tablename."` ADD ".InfoTable::$_key_types[$key_type]." `".$k."` (`".implode("`,`", $uk)."`);";
							
							$this->_diffs->addKeyDiff($key_type, "N", $sql);
						}
					}
					
					//Modify unique keys, index and fulltext index existing
					
					$ex_uk = array_intersect_key($tmp_keys, $keys);	
																			
					foreach ($ex_uk as $k => $uk)
					{
						$diff_ex_uk = array_diff($tmp_keys[$k], $keys[$k]);
						
						$diff_ex_uk_2 = array_diff($keys[$k], $tmp_keys[$k]);
						
						if ( !empty($diff_ex_uk) ||  !empty($diff_ex_uk_2))
						{
							$sql  = "ALTER TABLE `".$table->_dbname."`.`".$table->_tablename."` DROP INDEX `".$k."`;\n";
							$sql .= "ALTER TABLE `".$table->_dbname."`.`".$table->_tablename."` ADD ".InfoTable::$_key_types[$key_type]." `".$k."` (`".implode("`,`", $uk)."`);";
							$this->_diffs->addKeyDiff($key_type, "M", $sql);
						}
					}
					
					if ( !empty($sql) )
						return false;
					else
						return true;
				
				break;
				
		}
	}
		
	public function isKey($field, $type)
	{
		if ( empty($type) )
			return false;
			
		if ( !is_array($type)  )
			$tk[$type] = self::$_key_types[$type];
		else
			$tk = $type;
		
		foreach ($tk as $k => $v)
		{
			$keys = $this->getKey($field, $k);
			
			if ( !empty ($keys) )
				return true;
		}
		
		return false;
	}
	
	public function getKey($field, $type)
	{
		$keys = $this->getKeys($type);
		$index = array();
		
		if (!is_array ($keys) )
			return $index;
		
		
		switch ($type){
			
			case 'PRIMARY_KEY':
				foreach ($keys as $k => $value)
				{
					if ($value == $field)
					{
						$index = $keys;
						break;
					}
				}
			break;
			
			case 'FOREIGN_KEY':
				foreach ($keys as $k => $v)
				{
					if ( $v['COLUMN_NAME'] == $field )
					{
						$index[$k] = $v;
						break;
					}
				}
			break;
			
			default:
				foreach ($keys as $k => $v)
				{
					$indexes = implode('#', $v);
					if ( stripos($indexes, $field) !== false )
					{
						$index[$k] = $v;
						break;
					}
				}
			break;
		}
		
		return $index;
			
	}
	
	public function getKeyName($keyname, $type)
	{
		$keys = $this->getKeys($type);
		$index = array();
		
		if (!is_array ($keys) )
			return $index;
		
		switch ($type){
			
			case 'PRIMARY_KEY':
			if ( !empty ($keys) && strtoupper($keyname) == 'PRIMARY' )
				return $keys;
			break;
			
			case 'FOREIGN_KEY':
			foreach ($keys as $k => $fk)
			{
				if ( $fk['CONSTRAINT_NAME'] == $keyname )
					$index = $fk;
			}
			break;
			
			default:
				foreach ($keys as $i => $k)
				{
					if ($i == $keyname)
						$index = $keys;
				}
			break;
		}
		
		return $index;
			
	}
	
	
	public function checkException($exception_type, $field)
	{
		$exceptions = $this->getExceptions($exception_type);
		
		if ( !empty ($exceptions)  )
		{
			switch ($exception_type){
			
				case "rename_field":
					
					foreach ($exceptions as $k => $exception)
					{
						if ( $exception['new_field'] == $field )
							return $exception;
					}
				break;
				
				case "drop_field":
				
					foreach ($exceptions as $k => $exception)
					{
						if ( $exception['field'] == $field )
							return $exception;
					}
				break;
		
			}
		}
		return false;
	}

}

/**
* Class: InfoDb
* Description:Class which contains all information from a database
*/


Class InfoDb{ 
	private static $_db;
	private $_dbname;
	private $_tables;
	private $_errors;
	private $_tables_names;
	private static $_exceptions;
	 
	public function __construct($db_name, $exceptions=''){
		$this->_dbname = $db_name;
		$this->_db = new DataBase($this->_dbname);
		self::$_exceptions = ( !empty ($exceptions) ) ? $exceptions : array();
		$this->setTables();
	}
	
	public function getTables(){ return $this->_tables;}
	
	public function getDbName(){ return $this->_dbname;}
	
	public function getTablesNames(){ return $this->_tables_names;}	
	
	public function getErrors($type=''){
		
		if (empty($type))
			return $this->_errors;
		else
			return $this->_errors[$type];
	}
	
	public function setErrors($type, $error){
		
		if (!empty($type) && !empty($error))
			$this->_errors[$type][] = $error;
	}
	
		
	public function getTable($name){
		$table = array();
				
		if ( is_array ($this->_tables) && !empty($name) )
		    $table = $this->_tables[$name];

		return $table;
	}
	
	public static function existsDb($db_name)
	{
		$db = new DataBase('information_schema');
		$sql = "SHOW DATABASES LIKE '$db_name';";
		
		if ($db->setQuery($sql) ){
			
			$output= $db->loadArray();
			
			if ($output == null)
				return false;
			else
				return true;
		}
		else{
			echo "Failed to verify database name - 1";
			exit();
		}
	}
	
	public function dropDb()
	{
		$db = new DataBase($this->_dbname);
		$sql = "DROP DATABASE IF EXISTS `".$this->_dbname."`;";
				
		if ($db->setQuery($sql) )
		{
			$output= $db->execute();
			$db->freeResults();
			
			if ($output == null)
				return false;
			else
				return true;
		}
		else
			return false;
	}
	
	
	public function createTable($tmp_table)
	{
		$sentence = "SHOW CREATE TABLE `".$tmp_table->getDbName()."`.`".$tmp_table->getTableName()."`;";
								
		if ( $this->_db->setQuery($sentence) )
			$statement = $this->_db->loadArray();
		
		if ($statement == null)
		{
			$this->setErrors("createTable", "Failed to execute SHOW CREATE TABLE statement. (Code. ".mysql_errno()." - 1)");
			return false;
		}
		else
		{
			$sql = array();
			
			$pattern = "/,?\r?\n  CONSTRAINT .*/";	
			
			preg_match_all($pattern, $statement[0]['Create Table'], $matches);
			
			$sql[] = preg_replace($pattern, "", $statement[0]['Create Table']).";";
			
			if (preg_match_all($pattern, $statement[0]['Create Table'], $matches))				 
			{
				foreach ($matches[0] as $match)
				{
					$match = trim($match);
										
					if ( $match[0] == ',' )
						$match = substr ($match, 1);
					
					if ( $match[strlen($match)-1] == ',' )
						$match = substr ($match, 0, -1);
					
					$aux .= trim($match)."\n";
				}
				
				$pattern2 = "/FOREIGN KEY .*/";
				
				if (preg_match_all($pattern2, $aux, $matches))	
				{				
					foreach ($matches[0] as $match)
						$sql[] = "ALTER TABLE `".$tmp_table->getTableName()."` ADD ".$match.";";
				}
			}
			
			return $sql;
		}
	}
		
	public function setTables(){
				
		$errors = $this->getErrors();
		$this->setTablesNames();
						
		if ( empty ($errors) && count($this->_tables_names ) > 0 )
		{
			foreach ($this->_tables_names  as $key => $value){
				
				$table = new InfoTable($this->_dbname, $value, self::$_exceptions);
				
				$errors = $table->getErrors();
								
				if ( empty($errors) )
					$this->_tables[$value] = $table;
				else
				{
					$this->setErrors("setTables", "Failed to create class InfoDb for database ".$this->_dbname." (Code. 4)");
					return false;
				}
			}
			return true;
			
		}
		else{
			$this->setErrors("setTables", "Failed to create class InfoDb for database ".$this->_dbname." (Code. 3)");
			return false;
		}
	}
		
	
	private function setTablesNames(){
		
		$sql = "SHOW TABLES FROM `".$this->_dbname."`;";
		
		$tables = array();
		if ( $this->_db->setQuery($sql) )
		{
			$aux = $this->_db->loadObjectList();
			
			if ($aux != null){
				foreach ($aux as $table){
					$index = "Tables_in_".$this->_dbname;
					$tables[$table->$index]= $table->$index;
				}
				$this->_db->freeResults();
				$this->_tables_names = $tables;
			}
			else
			{
				$this->setErrors("setTables", "Failed to create class InfoDb for database ".$this->_dbname." (Code. 2)");
				echo $sql;
				return false;
			}
					
		}
		else{
			$this->setErrors("setTables", "Failed to create class InfoDb for database ".$this->_dbname." (Code. 1)");
			return false;
		}
	}
	
	public function createTmpDatabase($dbname, $sql_file)
	{
	    
		if ( filesize ($sql_file) == 0)
	    {
		    $this->setErrors("createTmpDatabase", "File $sql_file is empty");
			return false;
		}
		
		$sql = "DROP DATABASE IF EXISTS `$dbname`;";
				
		if ( $this->_db->setQuery($sql) )
			$aux = $this->_db->execute();
			        
		
		if ($aux == null)
		{
			$this->setErrors("createTmpDatabase", "Failed to execute DROP DATABASE statement. (Code. ".mysql_errno().")");
			return false;
		}
				
		// CREATE DATABASE
		
		$sql = "CREATE DATABASE IF NOT EXISTS `$dbname`;";
				
		if ( $this->_db->setQuery($sql) )
			$aux = $this->_db->execute();
			        
		
		if ($aux == null)
		{
			$this->setErrors("createTmpDatabase", "Failed to execute CREATE DATABASE statement. (Code. ".mysql_errno().")");
			return false;
		}
		else
			$this->_db = new DataBase($dbname);
				
		$cmd = "mysql -h ".$this->_db->getHostName()." -u ".$this->_db->getUser()." -p".$this->_db->getPassword()." $dbname < $sql_file";
		
		system ($cmd, $ret);
		
		if ( $ret == 0 )
			return true;
		else
			return false;
	}
	
	public function table_diff($tables)	{
		
		$new_tables = array ();
		$tables_to_compare = $this->getTablesNames();
		
		if ( is_array ($tables) && is_array ($tables_to_compare) )
			$new_tables = array_diff($tables, $tables_to_compare);	
			
		return $new_tables;
	}
	
}


/**
* Class: Diffs
* Description:Class which contains all diff in the tables from a database
*/


Class Diffs{ 
	private $_diffs;
	private static $_op = array("N" =>"new", "M"=>"modify", "D"=>"drop", "E"=>"special");
	private static $_keys_types = array('PRIMARY_KEY' =>'PRIMARY KEY', 'UNIQUE_KEY'=>'UNIQUE', 'INDEX'=>'INDEX', 'FULLTEXT_KEY' => 'FULLTEXT', 'FOREIGN_KEY'=>'FOREIGN KEY');
	private static $_diff_special = array("PRE" => array(), "POST" => array());	
	
	public function __construct(){
			
		$this->_diffs = array("fields" => array("N" =>array(), "M"=>array(), "D"=>array(), "E"=>array()), 
								"table_parameters" => "", 
								"keys" => array(
									"PRIMARY_KEY" => array("N" =>array(), "M"=>array()),
									"UNIQUE_KEY" => array("N" =>array(), "M"=>array(), "D"=>array()),
									"INDEX"  => array("N" =>array(), "M"=>array(), "D"=>array()),
									"FULLTEXT_KEY"  => array("N" =>array(), "M"=>array(), "D"=>array()),
									"FOREIGN_KEY" => array("N" =>array(), "M"=>array(), "D"=>array())	
								)
						);
		
	}
	
	public function getFieldsDiff($op=''){ 
		
		$diff = array();
		
		if ( empty($op) )
			$diff = $this->_diffs["fields"];
		else	
	    {
			if (array_key_exists($op, Diffs::$_op) )
				$diff = $this->_diffs["fields"][$op];
		}
		
		return $diff;
	}
	
	
	public static function getSpecialDiff ($pos) { 
	
	    if ($pos == 'PRE' || $pos == 'POST')
			return self::$_diff_special[$pos];
		else
		    return array();
	
	}
	
	
	public function getTableDiff(){ 
		return $this->_diffs["table_parameters"];
	}
		
	public function getKeysDiff($key_type, $op=''){
	
		$diff = array();
	
		if (array_key_exists($key_type, Diffs::$_keys_types) )
		{
			if ( empty($op) )
				$diff = $this->_diffs["keys"][$key_type];
			else	
			{
				if (array_key_exists($op, Diffs::$_op) )
					$diff = $this->_diffs["keys"][$key_type][$op];
			}
		}
		
		return $diff;
	
	}
	
	public function addFieldDiff($op, $diff){ 
		
		$res = false;
	
		if (array_key_exists($op, Diffs::$_op) )
		{
			if ( !in_array($diff, $this->_diffs["fields"][$op]) )
				$this->_diffs["fields"][$op][] = $diff;
			$res = true;
		}
		
		return $res;
	}
	
	public function addTableDiff($diff){ 
		
		$this->_diffs["table_parameters"] = $diff;
		return $res;
	}
		
	public function addKeyDiff($key_type, $op, $diff){
	
		$res = false;
	
		if (array_key_exists($op, Diffs::$_op) )
		{
			if (array_key_exists($key_type, Diffs::$_keys_types) )
			{
				$new_statement = "/".$diff."/";
				
				$alter_n  = ( is_array($this->_diffs["fields"]['N']) )  ? $this->_diffs["fields"]['N'] : array();
				$alter_m  = ( is_array($this->_diffs["fields"]['M']) )  ? $this->_diffs["fields"]['M'] : array();
				$alter_d  = ( is_array($this->_diffs["fields"]['D']) )  ? $this->_diffs["fields"]['D'] : array();
				$alter_e  = ( is_array($this->_diffs["fields"]['E']) )  ? $this->_diffs["fields"]['E'] : array();
				$alter_ef = ( !empty(self::$_diff_special['PRE']) )     ? self::$_diff_special['PRE'] : array();
				$alter_el = ( !empty(self::$_diff_special['POST']) )    ? self::$_diff_special['POST'] : array();
								
				$statements_field = array_merge ($alter_n, $alter_m, $alter_d, $alter_ef, $alter_el);
				$txt_sf = implode("\n", $statements_field);
				
				if ( !in_array($diff, $this->_diffs["keys"][$key_type][$op]) && preg_match ($new_statement, $txt_sf) == 0 )
					$this->_diffs["keys"][$key_type][$op][] = $diff;
				
				$res = true;
			}
		}
		
		return $res;
	
	}
	
	public static function addSpecialDiff ($pos, $diff)
	{
		if ($pos == 'PRE' || $pos == 'POST')
		{
			if ( !in_array($diff, self::$_diff_special[$pos]) )
			{
				self::$_diff_special[$pos][] = $diff;
				return true;
			}
		}
		return false;
	}
	
	public static function removeSpecialDiff ($pos)
	{
		if ($pos == 'PRE' || $pos == 'POST')
		{
			self::$_diff_special[$pos] = array();
			return true;
			
		}
		return false;
	}
	
}
?>
