<?php
/**
 * PHP ORM Class by HitraA Technologies
 * @author Harshal Khairnar
 * @version 1.2.1
 * @license MIT
 */

class Database{
	// Database connection properties
	private $server = 'localhost';
	private $user = 'root';
	private $password = '';
	private $database = 'test';
	private $port = 3306;
	private $table = null;
	private $conn;
	private $connect = true;

	// SQL server properties
	private $engine = "InnoDB";
	private $charset = "utf8mb4";
	private $collate = "utf8mb4_unicode_ci";
	private $time_zone = "+05:30";

	// MySQL properties
	private $stmt = null;
	private $selector = null;
	private $joinFormat = '%3$s JOIN `%1$s` ON %2$s';
	private $join = null;
	private $limit = null;
	private $offset = null;
	private $where = null;
	private $group_by = null;
	private $order_by = null;
	private $set = ["columns"=>[], "values"=>[]];
	public $sql_error = false;
	public $num_rows;

	// output properties
	private $result = [];
	private $status = null;
	private $insert_id = null;

	// pagination properties
	private $paginationLink = "";
	public $num_pages;
	public $paged_rows;

	/**
	 * constructor method creates database connection
	 * @method constructor
	 * @param boolean	:	$connect	(true|false)
	 * @default	= true
	 **/
	public function __construct($connect=true){
		$this->connect = $connect;
		if ($this->connect===true) {
			$this->connect();
		}
	}

	/**
	 * @method connect
	 * database server connection
	 * @param boolean	:	$connect	(true|false)
	 * @default = true
	 * if $connect=false it will return connection without database
	 **/
	public function connect(){
		$this->conn(true);
		return $this->conn;
	}

	/**
	 * errorReporting method enables mysqli error reporting
	 * @param $reporting : boolean (true|false)
	 * if true then error reporting is on
	 * else error reporting remains off
	 **/
	public function errorReporting($reporting=null){
		$driver = new mysqli_driver();
		if ($reporting===true) {
			$driver->report_mode = MYSQLI_REPORT_ALL;
			return $this;
		}elseif ($reporting===false){
			$driver->report_mode = MYSQLI_REPORT_OFF;
			return $this;
		}else{
			$driver->report_mode = MYSQLI_REPORT_STRICT;
			return $this;
		}
	}


	/**
	 * @method dbExists
	 * check is database exist or not
	 * @param string	:	$database	(database_name)
	 **/
	protected function dbExists($database){
		$this->conn(false);
		$result = $this->conn->query("SHOW DATABASES LIKE '{$database}'");
		if ($result->num_rows > 0) {
			return true;
		}else{
			return false;
		}
	}


	/**
	 * @method	tableExists
	 * check is table exist in database
	 * @param	string	:	$table	(table_name)
	 **/
	protected function tableExists($table){
		$result = $this->conn->query("SHOW TABLES LIKE '{$table}'");
		if ($result->num_rows > 0) {
			return true;
		}else{
			return false;
		}
	}

	/**
	 * @method	usePort
	 * @param	int	:	$port	(default:3306) [MySQL]
	 * use 3307 for MariaDB on wampserver
	 **/
	public function usePort($port=null){
		if ($port!==null) {
			$this->port = $port;
		}
	}

	/**
	 * database connection method
	 * returns mysqli connection object into $conn property
	 * @param $connect_db : boolean (true|false), default : true
	 * if $connect_db=false it will return connection without database
	 **/
	private function conn($connect_db){
		if ($connect_db===true && $this->dbExists($this->database)) {
			$conn = new \mysqli($this->server, $this->user, $this->password, $this->database, $this->port);
		}elseif($connect_db===false){
			$conn = new \mysqli($this->server, $this->user, $this->password, '', $this->port);
		}
		if (isset($conn)) {
			if ($conn) {
				// check connection is done or not
				if ($conn->connect_error) {
					$this->sql_error = $conn->connect_error;
				}else{
					$this->conn = $conn;
					$this->setCharset();
					$this->setTimeZone();
					return $this;
				}
			}else{
				$this->sql_error = "Database '{$this->database}' does not exists";
				throw new \Exception("Database '{$this->database}' does not exists");
			}
		}else{
			$this->sql_error = "Database '{$this->database}' does not exists";
			throw new \Exception("Database '{$this->database}' does not exists");
		}
	}

	/**
	 * @method useDatabase
	 * changes default database and
	 * use the new database other than default one
	 * @param $server : string (server_address) (ex. "localhost")
	 * @param $user : string (user_name) (ex. "root")
	 * @param $password : string (user_password)
	 * @param $database : string (database_name)
	 * if server and user is same then,
	 * ::useDatabase(database: "database_name")
	 **/
	public function useDatabase($database=null, $server=null, $user=null, $password=null){
		if ($server!==null) {
			$this->server = $server;
		}
		if ($user!==null) {
			$this->user = $user;
		}
		if ($password!==null) {
			$this->password = $password;
		}
		if ($database!==null) {
			$this->database = $database;
		}
		$this->conn($this->connect);
		$this->resetResult();
		return $this;
	}

	/**
	 * Create database method
	 * @param $database : string (database_name)
	 * returns (success : string) if true
	 * else returns (Error : string)
	 **/
	public function createDatabase($database=null, $encrypt=false){
		if ($database!==null) {
			$this->conn(false);
			$sql = "CREATE DATABASE IF NOT EXISTS {$database} CHARACTER SET={$this->charset} COLLATE={$this->collate}";
			if ($encrypt===true) {
				$sql .= " ENCRYPTION='Y'";
			}
			if (!$this->dbExists($database)) {
				if ($this->conn->query($sql) === true) {
					return "success";
				}else{
					return "Error creating database: " . $this->conn->error;
				}
			}else{
				throw new \Exception("Database {$database} already exists");
			}
		}
	}

	public function createTable($table=null){
		if ($table!==null) {
			$this->table = $table;
		}
		$this->stmt = "CREATE TABLE IF NOT EXISTS `{$this->table}`";
		return $this;
	}

	public function renameTable($rename_to=null, $table=null){
		if ($rename_to!==null) {
			if ($table!==null) {
				$this->table = $table;
			}
			$this->stmt = "ALTER TABLE `{$this->table}` RENAME `{$rename_to}`";
			return $this;
		}else{
			throw new \Exception("Empty Table Name");
		}
	}

	/**
	 * database table setter method
	 * table must be set before any method execution
	 * it will throw an exception if null or not set
	 * @param $table : string (table_name)
	 **/
	public function table($table=null){
		if ($table!==null) {
			$tables = array_filter(preg_split("/[\s,]+/", $table), function($e){return strlen($e)>1;});
			if (count($tables)<=1) {
				if ($this->tableExists($table)) {
					$this->table = $table;
					$this->resetResult();
					return $this;
				}else{
					throw new \Exception("Table '{$table}' not exists in '{$this->database}' database");
				}
			}else{
				$this->table = $table;
				$this->resetResult();
				return $this;
			}
		}else{
			throw new \Exception(__CLASS__."->table() can't be null");
		}
	}

	/**
	 * escape method for mysqli::real_escape_string()
	 * @param $rawValue : string (value_to_insert)
	 * @since v1.2
	 **/
	public function escape($rawValue=null){
		if ($rawValue!==null) {
			$json = false;
			if (is_array($rawValue)) {
				foreach ($rawValue as $key => $value) {
					if (is_array($rawValue[$key])) {
						$this->escape($rawValue[$key]);
					}else{
						$rawValue[$key] = $this->conn->real_escape_string($value);
					}
				}
				return $rawValue;
			}else{
				if ($json = json_decode($rawValue)) {
					foreach ($json as $key => $value) {
						if (is_array($json[$key])) {
							$this->escape($json[$key]);
						}else{
							$json[$key] = $this->conn->real_escape_string($value);
						}
					}
					return json_encode($json);
				}else{
					return $this->conn->real_escape_string($rawValue);
				}
			}
		}
	}
	/**
	 * @method query
	 * execute custom Queries
	 * @param $stmt : string (SQL Query)
	 * @since v1.0
	 **/
	public function query($stmt=null){
		if ($stmt!==null && $stmt!=="") {
			$this->stmt = $stmt;
		}
		return $this;
	}
	/**
	 * getCharset method for mysqli::character_set_name()
	 * returns charset of database
	 **/
	public function getCharset(){
		return $this->conn->character_set_name();
	}

	/**
	 * setCharset method for mysqli::set_charset()
	 * @param $charset string (ex. "utf8mb4")
	 * It sets charset for database
	 * default is "utf8mb4"
	 **/
	public function setCharset($charset=null){
		if ($charset!==null) {
			$this->charset = $charset;
		}
		$this->conn->set_charset($this->charset);
	}

	/**
	 * setTimeZone method for set session time_zone
	 * @param $time_zone string (ex. "+05:30")
	 * It sets time_zone for mysqli server session
	 * default is "+05:30"
	 **/
	public function setTimeZone($time_zone=null){
		if ($time_zone!==null) {
			$this->time_zone = $time_zone;
		}
		$this->conn->query("SET time_zone = '{$this->time_zone}'");
		return $this;
	}

	/**
	 * select method for selecting columns
	 * @param $selector = "column1, column2,...."
	 **/
	public function select($column=null, $column_as=null){
		if ($column!==null && $column!=="") {
			$column = $this->escape($column);
			$column_as = $this->escape($column_as);
			if ($this->selector!==null) {
				$this->selector .= ", {$column}";
			}else{
				$this->selector = $column;
			}
			if ($column_as!==null && $column_as!=="") {
				$this->selector .= " AS `{$column_as}`";
			}
		}else{
			$this->selector = "*";
		}
		return $this;
	}

	/**
	 * distinct
	 **/
	public function distinct($column=null){
		if ($column!==null) {
			$this->selector = $this->escape($column);
		}
		$this->selector = "DISTINCT ".$this->selector;
		return $this;
	}

	/**
	 * selectDay
	 * SELECT DAY(date_column)
	 **/
	public function selectDay($date_column=null, $column_as=null){
		if ($date_column!==null && $date_column!=="") {
			if ($this->selector!==null) {
				$this->selector .= ", DAY({$date_column})";
			}else{
				$this->selector = "DAY({$date_column})";
			}
			if ($column_as!==null && $column_as!=="") {
				$this->selector .= " AS ".$column_as;
			}
		}else{
			$this->selector = "*";
		}
		return $this;
	}

	/**
	 * selectWeek
	 * SELECT WEEK(date_column)
	 **/
	public function selectWeek($date_column=null, $column_as=null){
		if ($date_column!==null && $date_column!=="") {
			if ($this->selector!==null) {
				$this->selector .= ", WEEK({$date_column})";
			}else{
				$this->selector = "WEEK({$date_column})";
			}
			if ($column_as!==null && $column_as!=="") {
				$this->selector .= " AS ".$column_as;
			}
		}else{
			$this->selector = "*";
		}
		return $this;
	}

	/**
	 * selectWeekDay
	 * SELECT WEEKDAY(date_column)
	 **/
	public function selectWeekDay($date_column=null, $column_as=null){
		if ($date_column!==null && $date_column!=="") {
			if ($this->selector!==null) {
				$this->selector .= ", WEEKDAY({$date_column})";
			}else{
				$this->selector = "WEEKDAY({$date_column})";
			}
			if ($column_as!==null && $column_as!=="") {
				$this->selector .= " AS ".$column_as;
			}
		}else{
			$this->selector = "*";
		}
		return $this;
	}

	/**
	 * selectMonth
	 * SELECT MONTH(date_column)
	 **/
	public function selectMonth($date_column=null, $column_as=null){
		if ($date_column!==null && $date_column!=="") {
			if ($this->selector!==null) {
				$this->selector .= ", MONTH({$date_column})";
			}else{
				$this->selector = "MONTH({$date_column})";
			}
			if ($column_as!==null && $column_as!=="") {
				$this->selector .= " AS ".$column_as;
			}
		}else{
			$this->selector = "*";
		}
		return $this;
	}

	/**
	 * selectYear
	 * SELECT YEAR(date_column)
	 **/
	public function selectYear($date_column=null, $column_as=null){
		if ($date_column!==null && $date_column!=="") {
			if ($this->selector!==null) {
				$this->selector .= ", YEAR({$date_column})";
			}else{
				$this->selector = "YEAR({$date_column})";
			}
			if ($column_as!==null && $column_as!=="") {
				$this->selector .= " AS ".$column_as;
			}
		}else{
			$this->selector = "*";
		}
		return $this;
	}

	/**
	 * selectStrToDate
	 * SELECT STR_TO_DATE(date_column, '%d-%m-%Y')
	 **/
	public function selectStrToDate($date_column=null, $format=null, $column_as=null){
		if ($date_column!==null && $date_column!=="" && $format!==null) {
			$format = dateFormat($format);
			if ($this->selector!==null) {
				$this->selector .= ", STR_TO_DATE({$date_column}, '{$format}')";
			}else{
				$this->selector = "STR_TO_DATE({$date_column}, '{$format}')";
			}
			if ($column_as!==null && $column_as!=="") {
				$this->selector .= " AS ".$column_as;
			}
		}else{
			$this->selector = "*";
		}
		return $this;
	}

	/**
	 * selectMin selects MIN(column) from table
	 * @param $column : string (column_name)
	 * @param $column_as : string (column_name as)
	 * ex. query = SELECT MIN(age) AS age FROM mytable
	 * will be called selectMin("age")
	 * ex. query = SELECT MIN(age) AS xyz FROM mytable
	 * will be called selectMin("age","xyz")
	 **/
	public function selectMin($column=null, $column_as=null){
		if ($column!==null) {
			$selector = "MIN({$column})";
		}
		if ($column_as!==null){
			$selector .= " AS {$column_as}";
		}else{
			$selector .= " AS {$column}";
		}
		$this->selector = $selector;
		return $this;
	}

	/**
	 * selectMax selects MAX(column) from table
	 * @param $column : string (column_name)
	 * @param $column_as : string (column_name as)
	 * ex. query = SELECT MAX(age) AS age FROM mytable
	 * will be called selectMax("age")
	 * ex. query = SELECT MAX(age) AS xyz FROM mytable
	 * will be called selectMax("age","xyz")
	 **/
	public function selectMax($column=null, $column_as=null){
		if ($column!==null) {
			$selector = "MAX({$column})";
		}
		if ($column_as!==null){
			$selector .= " AS {$column_as}";
		}else{
			$selector .= " AS {$column}";
		}
		$this->selector = $selector;
		return $this;
	}

	/**
	 * selectAvg selects AVG(column) from table
	 * @param $column : string (column_name)
	 * @param $column_as : string (column_name as)
	 * ex. query = SELECT AVG(age) AS age FROM mytable
	 * will be called selectAvg("age")
	 * ex. query = SELECT AVG(age) AS xyz FROM mytable
	 * will be called selectAvg("age","xyz")
	 **/
	public function selectAvg($column=null, $column_as=null){
		if ($column!==null) {
			$selector = "AVG({$column})";
		}
		if ($column_as!==null){
			$selector .= " AS {$column_as}";
		}else{
			$selector .= " AS {$column}";
		}
		$this->selector = $selector;
		return $this;
	}

	/**
	 * selectSum selects SUM(column) from table
	 * @param $column : string (column_name)
	 * @param $column_as : string (column_name as)
	 * ex. query = SELECT SUM(age) AS age FROM mytable
	 * will be called selectSum("age")
	 * ex. query = SELECT SUM(age) AS xyz FROM mytable
	 * will be called selectSum("age","xyz")
	 * to get output use Database()->get()->getRow()
	 **/
	public function selectSum($column=null, $column_as=null){
		if ($column!==null) {
			$selector = "SUM({$column})";
		}
		if ($column_as!==null){
			$selector .= " AS {$column_as}";
		}else{
			$selector .= " AS {$column}";
		}
		$this->selector = $selector;
		return $this;
	}

	/**
	 * selectCount selects COUNT(column) from table
	 * @param $column : string (column_name)
	 * @param $column_as : string (column_name as)
	 * ex. query = SELECT COUNT(age) AS count_age FROM mytable
	 * will be called selectCount("age")
	 * ex. query = SELECT COUNT(age) AS xyz FROM mytable
	 * will be called selectCount("age","xyz")
	 **/
	public function selectCount($column=null, $column_as=null){
		if ($column!==null && $column!=="") {
			$selector = "COUNT({$column})";
		}else{
			$selector = "COUNT(*)";
		}
		if (($column!==null || $column!=="") && ($column_as===null || $column_as==="")){
			$selector .= "";
			// $selector .= " AS count_{$column}";
		}elseif($column_as!==null){
			$selector .= " AS {$column_as}";
		}
		if ($this->selector!==null) {
			$this->selector .= ", {$selector}";
		}else{
			$this->selector = $selector;
		}
		return $this;
	}

	/**
	 * @method join
	 * sql => (LEFT/RIGHT/INNER) JOIN table1 ON table1.column = table2.column
	 * @param $table : str (table2 to join)
	 * @param $column_on_column : str (table1.column = table2.column)
	 * @param $join_type : str (LEFT/RIGHT/INNER)
	 * ======================================================================
	 * ## Supported Types of Joins in MySQL ##
	 * ----------------------------------------------------------------------
	 * INNER JOIN: Returns records that have matching values in both tables
	 * ----------------------------------------------------------------------
	 * LEFT JOIN: Returns all records from the left table,
	 * and the matched records from the right table
	 * ----------------------------------------------------------------------
	 * RIGHT JOIN: Returns all records from the right table,
	 * and the matched records from the left table
	 * ----------------------------------------------------------------------
	 * CROSS JOIN: Returns all records from both tables
	 * ======================================================================
	 **/
	public function join($table=null, $column_on_column=null, $join_type=null){
		if ($table!==null) {
			if ($join_type!==null) {
				$join_type = strtoupper($join_type);
			}else{
				$join_type = "";
			}
			if ($column_on_column!==null && $column_on_column!=="") {
				if ($this->join!==null) {
					$this->join .= " ".sprintf($this->joinFormat, $table, $column_on_column, $join_type);
				}else{
					$this->join = sprintf($this->joinFormat, $table, $column_on_column, $join_type);
				}
			}else{
				$this->joinFormat = '%2$s JOIN `%1$s`';
				if ($this->join!==null) {
					$this->join .= " ".sprintf($this->joinFormat, $table, $join_type);
				}else{
					$this->join = sprintf($this->joinFormat, $table, $join_type);
				}
			}
		}else{
			throw new \Exception("SQL Error table not selected for JOIN");
		}
		return $this;
	}

	/**
	 * get method get all rows from table with/without limit
	 * @param $limit int
	 * @param $offset int
	 * limit & offset can be set seperately or by get method directly
	 **/
	public function get($limit=null, $offset=null){
		$this->limit($limit, $offset);
		if ($this->table!==null) {
			if ($this->selector===null) {
				$this->select();
			}
			// SQL statement
			$this->stmt = "SELECT {$this->selector} FROM {$this->table}";
			// (LEFT/RIGHT/INNER) JOIN table1 ON table1.column = table2.column
			if ($this->join!==null) {
				$this->stmt .= " {$this->join}";
			}
			// WHERE col=val
			if ($this->where!==null) {
				$this->stmt .= " WHERE ".$this->where;
			}
			// GROUP BY col1, col2
			if ($this->group_by!==null) {
				$this->stmt .= " GROUP BY ".$this->group_by;
			}
			// ORDER BY col1, col2
			if ($this->order_by!==null) {
				$this->stmt .= " ORDER BY ".$this->order_by;
			}
			// LIMIT $limit
			if ($this->limit!==null) {
				$this->stmt .= " LIMIT ".$this->limit;
			}
			// OFFSET $offset
			if ($this->offset!==null && $offset!=='') {
				$this->stmt .= " OFFSET ".$this->offset;
			}
			return $this;
		}else{
			throw new \Exception(__CLASS__."->table() not set");
		}
	}

	/**
	 * where method
	 * @param $where : array/string
	 * #array : if array then must be ["column"=>"value"]
	 * it will parse query AS column='value'
	 * ======================================================
	 * #string : is string given it must be
	 * "column <delim> value"
	 * <delim> ("<", ">", "=", "!", "<=", ">=", "!=")
	 * ex. "column = value"
	 * ex. "column < value"
	 * ex. "column > value"
	 * ex. "column <= value"
	 * ex. "column >= value"
	 * ex. "column != value"
	 * ======================================================
	 * if multiple times executed then it will append to SQL query
	 * ex. where(col1=val1)
	 * ex. where(col2=val2)
	 * ex. where(col3=val3)
	 * then query = (...WHERE col1='val1' AND col2='val2' AND col3='val3')
	 * ======================================================
	 * if want to change AND/OR clause
	 * then specify AS second argument
	 * ex. where(col2=val2, "or")
	 * ======================================================
	 * if $where is string and and_or != "AND/OR"
	 * then it will parse both arguments AS seperate column and value
	 * ex. where("column","value")
	 * #=> WHERE column='value'
	 * ======================================================
	 * if executed multiple times
	 * ex. where("col1", "val1")
	 * ex. where("col2", "val2")
	 * ex. where("col3", "val3")
	 * #=> WHERE col1='val1' AND col2='val2' AND col3='val3'
	 **/
	public function where($where=null, $and_or="AND"){
		$val = $and_or;
		$and_or = strtoupper($and_or);
		if ($where!==null && is_array($where) && ($and_or=="AND" || $and_or=="OR")) {

			/**
			 * for => where(["column"=>"value"])
			 * OR for => where(["column"=>"value"], "AND/OR")
			 */

			$columns = array_keys($where);
			$values = array_values($where);
			$str = "";
			for ($i=0; $i < count($columns); $i++) { 
				$str .= $columns[$i].'="'.$values[$i].'" '.$and_or.' ';
			}
			if ($and_or==="AND") {
				$str = substr($str, 0,-4);
			}elseif($and_or==="OR"){
				$str = substr($str, 0,-3);
			}
			if ($this->where!==null) {
				$this->where .= " {$and_or} {$str}";
			}else{
				$this->where = "{$str}";
			}
		}elseif ($where!==null && !is_array($where) && ($and_or===null || $and_or==="AND" || $and_or==="OR")){

			/**
			 * for => where("column=value")
			 * OR for => where("column=value", "AND/OR")
			 * this will split string by
			 * "=", "<", ">", "<=", ">=", "!="
			 * and returns array ["column","<!=>","value"]
			 */

			$where = preg_split("/([\=\<\>\!]+)/", $where,-1,PREG_SPLIT_DELIM_CAPTURE);
			$where2 = $where[2];
			$where = trim($where[0]).$where[1];
			$is_col = explode(".", $where2);
			if (count($is_col)>1) {
				$where .= trim($where2," '\"");
			}else{
				$where .="'".trim($where2," '\"")."'";
			}
			if ($this->where!==null) {
				$this->where .= " {$and_or} ".$where;
			}else{
				$this->where = $where;
			}

		}elseif ($where!==null && !is_array($where) && $and_or!=="AND" && $and_or!=="OR"){

			/**
			 * for => where("column","value")
			 */

			if ($this->where!==null) {
				$this->where .= " AND {$where}='{$val}'";
			}else{
				$this->where = "{$where}='{$val}'";
			}
		}
		return $this;

	}

	/**
	 * @method whereIn
	 * @return Class object
	 * 'WHERE some_column IN ("option-1", "option-1",...)'
	 * it adds AND claus if called again
	 **/
	public function whereIn($column, $list=null){
		$column = $this->escape($column);
		$list = is_array($list) ? "('".implode("', '", $list)."')" : $list;
		if ($this->where!==null) {
			$this->where .= " AND `{$column}` IN {$list}";
		}else{
			$this->where = "`{$column}` IN {$list}";
		}
		return $this;
	}

	/**
	 * @method orWhereIn
	 * @return Class object
	 * 'WHERE some_column IN ("option-1", "option-1",...)'
	 * it adds OR claus if called again
	 **/
	public function orWhereIn($column, $list=null){
		$column = $this->escape($column);
		$list = is_array($list) ? "('".implode("', '", $list)."')" : $list;
		if ($this->where!==null) {
			$this->where .= " OR `{$column}` IN {$list}";
		}else{
			$this->where = "`{$column}` IN {$list}";
		}
		return $this;
	}

	/**
	 * @method whereNotIn
	 * @return Class object
	 * 'WHERE some_column NOT IN ("option-1", "option-1",...)'
	 * it adds AND claus if called again
	 **/
	public function whereNotIn($column, $list=null){
		$column = $this->escape($column);
		$list = is_array($list) ? "('".implode("', '", $list)."')" : $list;
		if ($this->where!==null) {
			$this->where .= " AND `{$column}` NOT IN {$list}";
		}else{
			$this->where = "`{$column}` NOT IN {$list}";
		}
		return $this;
	}

	/**
	 * @method orWhereNotIn
	 * @return Class object
	 * 'WHERE some_column NOT IN ("option-1", "option-1",...)'
	 * it adds OR claus if called again
	 **/
	public function orWhereNotIn($column, $list=null){
		$column = $this->escape($column);
		$list = is_array($list) ? "('".implode("', '", $list)."')" : $list;
		if ($this->where!==null) {
			$this->where .= " OR `{$column}` NOT IN {$list}";
		}else{
			$this->where = "`{$column}` NOT IN {$list}";
		}
		return $this;
	}

	/**
	 * @method selectFrom
	 * @return (string)
	 * it completes remaining portion for whereIn
	 * '(SELECT `column_select` FROM `table_from`)'
	 * @uses
	 * $table->WhereIn("column1", $table->selectFrom("column2", "table2"));
	 **/
	public function selectFrom($column_select, $table_from){
		$column = $this->escape($column_select);
		$table = $this->escape($table_from);
		return "(SELECT `{$column}` FROM `{$table}`)";
	}

	/**
	 * it creates sql query for select columns from table
	 * where MONTH(column) = some_value
	 * 
	 * @method whereMonth
	 * @return Class object
	 * "WHERE MONTH($date_column) = $value"
	 **/
	public function whereMonth($date_column=null, $value=null){
		if ($date_column!==null && $value!==null) {
			if ($this->where!==null) {
				$this->where .= " AND MONTH({$date_column}) = '{$value}'";
			}else{
				$this->where = "MONTH({$date_column}) = '{$value}'";
			}
			return $this;
		}
	}
	
	/**
	 * it creates sql query for select columns from table
	 * where YEAR(column) = some_value
	 * 
	 * @method whereYear
	 * @return Class object
	 * "WHERE YEAR($date_column) = $value"
	 **/
	public function whereYear($date_column=null, $value=null){
		if ($date_column!==null && $value!==null) {
			if ($this->where!==null) {
				$this->where .= " AND YEAR({$date_column}) = '{$value}'";
			}else{
				$this->where = "YEAR({$date_column}) = '{$value}'";
			}
			return $this;
		}
	}
	
	/**
	 * it creates sql query for select columns from table
	 * where MONTH(column) = some_value AND YEAR(column) = some_value"
	 * 
	 * @method whereMonthYear
	 * @return Class object
	 * "WHERE MONTH($date_column) = $value" AND YEAR($date_column) = $value"
	 **/
	public function whereMonthYear($date_column=null, $month_value=null, $year_value=null){
		if ($date_column!==null && $month_value!==null && $year_value!==null) {
			if ($this->where!==null) {
				$this->where .= " AND MONTH({$date_column}) = '{$month_value}' AND YEAR({$date_column}) = '{$year_value}'";
			}else{
				$this->where = "MONTH({$date_column}) = '{$month_value}' AND YEAR({$date_column}) = '{$year_value}'";
			}
			return $this;
		}
	}
	
	/**
	 * it creates sql query for select columns from table
	 * where MONTH(column) = some_value OR YEAR(column) = some_value"
	 * 
	 * @method orWhereMonthYear
	 * @return Class object
	 * "WHERE MONTH($date_column) = $value" OR YEAR($date_column) = $value"
	 **/
	public function orWhereMonthYear($date_column=null, $month_value=null, $year_value=null){
		if ($date_column!==null && $month_value!==null && $year_value!==null) {
			if ($this->where!==null) {
				$this->where .= " AND (MONTH({$date_column}) = '{$month_value}' OR YEAR({$date_column}) = '{$year_value}')";
			}else{
				$this->where = "MONTH({$date_column}) = '{$month_value}' OR YEAR({$date_column}) = '{$year_value}'";
			}
			return $this;
		}
	}

	/**
	 * getWhere method same AS where method
	 * it takes additional argument
	 * @param $limit : refer limit() method for additional info
	 * @param $offset : refer limit() method for additional info
	 **/
	public function getWhere($where=null, $limit=null, $offset=null){
		$this->limit($limit, $offset);
		$this->where($where);
		return $this;
	}

	/**
	 * like method
	 * it handles LIKE statement
	 * default is AND LIKE if where already set
	 **/
	public function like($column=null, $value=null){
		if ($column!==null && $value!==null) {
			if ($this->where!==null) {
				$this->where .= " AND {$column} LIKE '%{$value}%'";
			}else{
				$this->where = " {$column} LIKE '%{$value}%'";
			}
		}
		return $this;
	}

	/**
	 * notLike method
	 * it handles NOT LIKE statement
	 * default is AND NOT LIKE if where already set
	 **/
	public function notLike($column=null, $value=null){
		if ($column!==null && $value!==null) {
			if ($this->where!==null) {
				$this->where .= " AND {$column} NOT LIKE '%{$value}%'";
			}else{
				$this->where = " {$column} NOT LIKE '%{$value}%'";
			}
		}
		return $this;
	}

	/**
	 * orLike method
	 * it handles LIKE statement
	 * default is OR LIKE if where already set
	 **/
	public function orLike($column=null, $value=null){
		if ($column!==null && $value!==null) {
			if ($this->where!==null) {
				$this->where .= " OR {$column} LIKE '%{$value}%'";
			}else{
				$this->where = " {$column} LIKE '%{$value}%'";
			}
		}
		return $this;
	}

	/**
	 * orNotLike method
	 * it handles NOT LIKE statement
	 * default is OR NOT LIKE if where already set
	 **/
	public function orNotLike($column=null, $value=null){
		if ($column!==null && $value!==null) {
			if ($this->where!==null) {
				$this->where .= " OR {$column} NOT LIKE '%{$value}%'";
			}else{
				$this->where = " {$column} NOT LIKE '%{$value}%'";
			}
		}
		return $this;
	}

	/**
	 * @method groupBy
	 * 
	 * The MySQL GROUP BY Statement
	 * --------------------------------------------------------------
	 * The GROUP BY statement groups rows that have the
	 * same values into summary rows, like
	 * "find the number of customers in each country".
	 * The GROUP BY statement is often used with aggregate functions
	 * (COUNT(), MAX(), MIN(), SUM(), AVG())
	 * to group the result-set by one or more columns.
	 * ===============================================================
	 * GROUP BY Syntax
	 * ---------------------------------------------------------------
	 * SELECT column_name(s)
	 * FROM table_name
	 * WHERE condition
	 * GROUP BY column_name(s)
	 * ORDER BY column_name(s);
	 * ===============================================================
	 **/
	public function groupBy($column=null, $value=null){
		$this->set($column, $value);
		if ($column!==null && $value!==null) {
			// for ("col1", "col2")
			$col1 = $this->set["columns"][0];
			$col2 = $this->set["values"][0];
			$this->group_by = "{$col1}, {$col2}";
		}elseif ($column!==null && $value===null){
			if (is_array($column)) {
				// for (["col", "val",..])
				$this->group_by = implode(", ", $this->set['values']);
			}elseif(count(explode(",", $column))>=1){
				// for ("col1, col2, ....") / ("col1")("col2")
				$this->group_by = implode(", ", $this->set['columns']);
			}
		}
	}

	/**
	 * @method orderBy
	 * @param $column : str (column name)
	 * @param $option : str (ASC/DESC/RANDOM[RAND()])
	 **/
	public function orderBy($column=null, $option=null){
		if ($option!==null) {
			$option = strtoupper($option);
		}
		if ($column!==null && $option===null) {
			$raw = explode(" ", $column);
			$raw1 = explode(".", $column);
			if (count($raw)>1) {
				$column = $raw[0];
				$option = $raw[1];
			}elseif (count($raw1)>1){
				$column = $raw1[0];
				$option = ".`{$raw1[1]}`";
			}else{
				$column = $raw[0];
				$option = null;
			}
			if ($this->order_by!==null) {
				$this->order_by .= ", `{$column}` {$option}";
			}else{	
				$this->order_by = "`{$column}` {$option}";
			}
		}elseif ($column!==null && $option!==null){
			if ($this->order_by!==null) {
				if ($option=="RANDOM") {
					$option = " RAND()";
					$this->order_by .= "{$option}";
				}else{
					$this->order_by .= ", {$column} {$option}";
				}
			}else{
				if ($option=="RANDOM") {
					$option = " RAND()";
					$this->order_by = "{$option}";
				}else{
					$this->order_by = "{$column} {$option}";
				}
			}
		}
		return $this;
	}

	/**
	 * paginate method to get pagination result
	 * @uses $table->table("listings")->paginate(1)->get()->getResult();
	 * to get pagination links use getPaginationLink method
	 * 
	 **/
	public function paginate($limit=10, $callback=null, array $options=array()){
		if ($callback==null){
			$callback = function($page, $url=''){
				return "{$url}?page={$page}";
			};
		}else{
			if (!is_callable($callback)) {
				throw new \Exception("{$callback} is not valid callable function");
				exit();
			}
		}
		if (!isset ($_GET['page']) ) {
			$page = 1;
		} elseif ($_GET['page']<1) {
			$page = 1;
		} else {
			$page = (int) $_GET['page'];
		}
		$this->get();
		$this->num_rows = (int) $this->conn->query($this->stmt)->num_rows;
		if ($limit<1) {
			$this->limit = 1;
		}else{
			$this->limit = $limit;
		}
		$this->num_pages = (int) ceil($this->num_rows / $this->limit);
		$this->offset = ($page-1) * $this->limit;
		$this->limit($this->limit, $this->offset);
		// Default Options
		$default = array(
			"container"					=>	"nav",
			"container_class"			=>	"pagination-nav",
			"container_aria_label"		=>	"Pagination pages",
			"pagination_wraper"			=>	"ul",
			"pagination_wraper_class"	=>	"pagination",
			"pagination_item"			=>	"li",
			"pagination_item_class"		=>	"page-item",
			"pagination_link"			=>	"a",
			"pagination_link_class"		=>	"page-link",
			"prev_link"					=>	"&lsaquo;",
			"next_link"					=>	"&rsaquo;",
			"first-last"				=>	false,
			"first_link"				=>	"&laquo;",
			"last_link"					=>	"&raquo;",
		);
		if (count($options)>0) {
			foreach ($options AS $key => $value) {
				$default[$key] = $value;
			}
		}
		if ($this->num_rows > $this->limit){
			$pagination_item = '<%1$s class="%2$s">%3$s</%1$s>';
			$pagination_link = '<%1$s class="%2$s" %3$s>%4$s</%1$s>';
			$firstLink = sprintf(
				$pagination_item,
				$default["pagination_item"],
				$default["pagination_item_class"].($page<=1 ? " disabled" : ''),
				sprintf(
					$pagination_link,
					$default["pagination_link"],
					$default["pagination_link_class"],
					"href='{$callback(1)}'",
					$default["first_link"],
				)
			);
			$lastLink = sprintf(
				$pagination_item,
				$default["pagination_item"],
				$default["pagination_item_class"].($page===$this->num_pages ? " disabled" : ''),
				sprintf(
					$pagination_link,
					$default["pagination_link"],
					$default["pagination_link_class"],
					"href='{$callback($this->num_pages)}'",
					$default["last_link"],
				)
			);
			$prevLink = sprintf(
				$pagination_item,
				$default["pagination_item"],
				$default["pagination_item_class"].($page<=1 ? " disabled" : ''),
				sprintf(
					$pagination_link,
					$default["pagination_link"],
					$default["pagination_link_class"],
					($page>1 ? "href='{$callback($page-1)}'" : ''),
					$default["prev_link"],
				)
			);
			$links = "";
			for($p = 1; $p<= $this->num_pages; $p++) {
				if ($p===$page) {
					$links .= sprintf(
						$pagination_item,
						$default["pagination_item"],
						$default["pagination_item_class"]." active",
						sprintf(
							$pagination_link,
							$default["pagination_link"],
							$default["pagination_link_class"],
							"",
							$p
						)
					);
				}else{
				  if (($p>= ($page-3) && $p<= ($page+3))) {
					$links .= sprintf(
						$pagination_item,
						$default["pagination_item"],
						$default["pagination_item_class"],
						sprintf(
							$pagination_link,
							$default["pagination_link"],
							$default["pagination_link_class"],
							"href='{$callback($p)}'",
							$p
						)
					);
				  }
				}
			}
			$nextLink = sprintf(
				$pagination_item,
				$default["pagination_item"],
				$default["pagination_item_class"].($page===$this->num_pages ? " disabled" : ''),
				sprintf(
					$pagination_link,
					$default["pagination_link"],
					$default["pagination_link_class"],
					($page<$this->num_pages ? "href='{$callback($page+1)}'" : ''),
					$default["next_link"],
				)
			);
			$wraper = sprintf(
				'<%1$s class="%2$s">%3$s</%1$s>',
				$default["pagination_wraper"],
				$default["pagination_wraper_class"],
				$default["first-last"] ? $firstLink.$prevLink.$links.$nextLink.$lastLink : $prevLink.$links.$nextLink
			);
			$container = sprintf(
				'<%1$s class="%2$s" aria-label="%3$s">%4$s</%1$s>',
				$default["container"],
				$default["container_class"],
				$default["container_aria_label"],
				$wraper
			);
			$this->paginationLink = $container;
		}
		return $this;
	}

	/**
	 * @method getPaginationLink
	 * @return pagination liks
	 * to echo by default pass true arg
	 **/
	public function getPaginationLink($echo=false){
		if ($echo===true) {
			echo $this->paginationLink;
		}else{
			return $this->paginationLink;
		}
	}

	// limit method
	public function limit($limit=null, $offset=null){
		// this sets limit and offset seperately
		// LIMIT $limit
		// if $limit not given but $offset is given then
		// $this->limit = count(*) of table
		if ($limit===null && $offset!==null) {
			$this->limit = $this->conn->query("SELECT count(*) FROM ".$this->table)->fetch_assoc()['count(*)'];
		}
		if ($limit!==null) {
			$this->limit = $limit;
		}
		// OFFSET $offset
		if ($offset!==null) {
			$this->offset = $offset;
		}
		return $this;
	}

	/**
	 * getQuery method returns parsed SQL query
	 * @param $reset=true then it will reset $stmt and returns null
	 **/
	public function getQuery($reset=false){
		if ($reset===true) {
			$this->stmt = null;
			$this->resetResult()->get();
			return $this->stmt;
		}else{
			if (is_array($this->stmt)) {
				return implode("<br>", $this->stmt);
			}else{
				return $this->stmt;
			}
		}
	}

	/**
	 * getResult method gets result AS object array
	 * @uses stdClass to get object of result
	 * 
	 * ex.
	 * require('Database.php');
	 * 
	 * $db = new Database();
	 * $table = $db->table("table_name");
	 * $table->select("col1, col2, col3");
	 * $table->get();
	 * $query = $table->getResult();
	 * foreach ($query AS $row) {
	 * 	echo $row->col1;
	 * 	echo $row->col2;
	 * 	echo $row->col3;
	 * }
	 **/
	public function getResult(){
		$this->result = [];
		$data = $this->conn->query($this->stmt);
		if ($this->num_pages){
			$this->paged_rows = $data->num_rows;
			if ($this->paged_rows > 0) {
				// output data of each row AS associative array
				while($row = $data->fetch_assoc()) {
					array_push($this->result, (object) self::AdvanceTrim($row));
				}
				return $this->result;
			}else{
				$this->sql_error = "Error: " . $this->stmt . "<br>" . $this->conn->error;
				return false;
			}
		}else{
			$this->num_rows = $data->num_rows;
			if ($this->num_rows > 0) {
				// output data of each row AS associative array
				while($row = $data->fetch_assoc()) {
					array_push($this->result, (object) self::AdvanceTrim($row));
				}
				return $this->result;
			}else{
				$this->sql_error = "Error: " . $this->stmt . "<br>" . $this->conn->error;
				return false;
			}
		}
	}

	/**
	 * getResultArray method gets result AS associative array
	 **/
	public function getResultArray(){
		$this->result = [];
		$data = $this->conn->query($this->stmt);
		if ($this->num_pages){
			$this->paged_rows = $data->num_rows;
			if ($this->paged_rows > 0) {
				// output data of each row AS associative array
				while($row = $data->fetch_array()) {
					array_push($this->result, self::AdvanceTrim($row));
				}
				return $this->result;
			}else{
				$this->sql_error = "Error: " . $stmt . "<br>" . $this->conn->error;
				return false;
			}
		}else{
			$this->num_rows = $data->num_rows;
			if ($this->num_rows > 0) {
				// output data of each row AS associative array
				while($row = $data->fetch_array()) {
					array_push($this->result, self::AdvanceTrim($row));
				}
				return $this->result;
			}else{
				$this->sql_error = "Error: " . $stmt . "<br>" . $this->conn->error;
				return false;
			}
		}
	}


	/**
	 * getRow method gets row AS object
	 * @param $row_index : int (row number)
	 * (0 for 1st)
	 * (1 for 2nd)...
	 * it also accept negative index
	 * (-1 for last)
	 * (-2 for 2nd last)...
	 **/
	public function getRow($row_index=0){
		$this->result = [];
		$data = $this->conn->query($this->stmt);
		$this->num_rows = $data->num_rows;
		if ($this->num_rows > 0) {
			// output data of each row
			while($row = $data->fetch_assoc()) {
				array_push($this->result, (object) self::AdvanceTrim($row));
			}
			// for +ve index
			if (isset($this->result[$row_index])) {
				return $this->result[$row_index];
			}elseif ($row_index<0){
				// this will return negative indexed object
				return $this->result[count($this->result)+$row_index];
			}else{
				$this->sql_error = "Error: " . $this->stmt . "<br>" . $this->conn->error;
				return false;
			}
		}else{
			$this->sql_error = "Error: " . $this->stmt . "<br>" . $this->conn->error;
			return false;
		}

	}


	/**
	 * getRowArray method gets row AS array
	 * @param $row_index : int (row number)
	 * (0 for 1st)
	 * (1 for 2nd)...
	 * it also accept negative index
	 * (-1 for last)
	 * (-2 for 2nd last)...
	 **/
	public function getRowArray($row_index=0){
		$this->result = [];
		$data = $this->conn->query($this->stmt);
		$this->num_rows = $data->num_rows;
		if ($this->num_rows > 0) {
			// output data of each row
			while($row = $data->fetch_array()) {
				array_push($this->result, self::AdvanceTrim($row));
			}
		}
		// for +ve index
		if (isset($this->result[$row_index])) {
			return $this->result[$row_index];
		}elseif ($row_index<0){
			// this will return negative indexed object
			return $this->result[count($this->result)+$row_index];
		}else{
			return null;
		}
	}


	/**
	 * resetResult method resets result return by any statement
	 **/
	public function resetResult(){
		$this->where = null;
		$this->order_by = null;
		$this->limit = null;
		$this->offset = null;
		$this->selector = null;
		$this->stmt = null;
		$this->join = null;
		$this->set = ["columns"=>[], "values"=>[]];
		return $this;
	}

	/**
	 * countResults method counts result return by getResultArray()
	 * @param $reset : boolean (true|false)
	 * by default it dont reset counter and return count of get result
	 **/
	public function countResults($reset=false){
		if ($reset===true) {
			$this->resetResult()->get();
		}
		$result = $this->getResult();
		return count($result);
	}

	/**
	 * countAll method counts result return by getResultArray()
	 * but only diffrence is it resets any counter set by any where clause and returns all result count from table
	 * @param $reset : boolean (true|false)
	 * by default it resets counter and get all available count from table
	 **/
	public function countAll($reset=true){
		if ($reset===true) {
			$this->resetResult()->get();
		}
		$result = $this->getResult();
		return count($result);	
	}

	/**
	 * set method sets values for insert or update method
	 * @param $column : string (column_name)
	 * 					set("col", "val")
	 * 					set(["col" => "val",..])
	 * 					set("col=val")
	 * @param $value : string (column_value)
	 * muti value array also taken in set method AS column argument
	 * like
	 * array(
	 *  "col1" => ["abc", "xyz", "pqr"],
	 *  "col2" => ["abc", "xyz", "pqr"],
	 *  "col3" => ["abc", "xyz", "pqr"],
	 * )
	 * this will prepare insert for multi value
	 **/ 
	public function set($column=null, $value=null){
		if ($column!==null && $value!==null) {
			// for ("col", "val")
			array_push($this->set["columns"], $this->escape($column));
			array_push($this->set["values"], $this->escape($value));
			return $this;
		}elseif ($column!==null && $value===null){
			// for (["col" => "val",..])
			if (is_array($column)) {
				$cols = array_keys($column);
				$vals = array_values($column);
				for ($i=0; $i < count($cols); $i++) { 
					array_push($this->set["columns"], $this->escape($cols[$i]));
					if (is_array($vals[$i])) {
						$valArr = [];
						foreach ($vals[$i] AS $val) {
							array_push($valArr, $this->escape($val));
						}
						array_push($this->set["values"], $valArr);
					}else{
						array_push($this->set["values"], $this->escape($vals[$i]));
					}
				}
			}elseif(count(explode("=", $column))===2){
				// for ("col=val")
				$strArray = explode("=", $column);
				array_push($this->set["columns"], $this->escape($strArray[0]));
				array_push($this->set["values"], $this->escape($strArray[1]));

			}elseif(count(explode(",", $column))>=1){
				$strArray = (explode(",", $column)>=2) ? explode(",", $column) : $column;
				// for ("col1, col2")
				if (is_array($strArray)) {
					foreach ($strArray AS $column) {
						array_push($this->set["columns"], $this->escape($column));
					}
				}else{
					// for ("column")
					array_push($this->set["columns"], $this->escape($column));
				}
			}
		}
	}

	/**
	 * insert method prepare sql query to insert data
	 * @param $data : array (takes ["column"=>"value",...])
	 * to save this records must run save() method after insert method
	 * @param $direct : bool (false| dont insert records directly)
	 * 						 (true | insert records directly)
	 * 						 (default | false)
	 * ========================================================
	 * if used with set method it directly insert records on insert() method call
	 * ex.	set("column","value")
	 * 		insert()
	 * it will directly inserts record set by set() method 
	 * ========================================================
	 **/
	public function insert($data=null, $direct=false){
		$columns = [];
		$values = [];
		$this->stmt = "INSERT INTO `{$this->table}` (%s) VALUES (%s)";
		if ($data!==null && is_array($data)) {
			// prepare insert query for save method
			if (count($data)>0) {
				foreach($data AS $col => $val) {
					array_push($columns, $this->escape($col));
					array_push($values, $this->escape($val));
				}
				$this->stmt = sprintf($this->stmt, "`".implode("`, `", $columns)."`", '"'.implode('", "', $values).'"');
			}
			return $this;
		}elseif($data===null){
			// insert records collected by set method
			if (count($this->set["columns"]) > 0 && count($this->set["values"]) > 0) {
				// if values are flat array
				if (count($this->set["values"]) === count($this->set["values"], COUNT_RECURSIVE)) {
					$this->stmt = sprintf($this->stmt, "`".implode("`, `", $this->set["columns"])."`", '"'.implode('", "', $this->set["values"]).'"');
				}else{
					// if values are multi dimentional array
					$this->stmt = [];
					for ($i=0; $i < count($this->set["values"][0]); $i++) {
						$stmt = "INSERT INTO `{$this->table}` (%s) VALUES ";
						$stmt = sprintf($stmt, "`".implode("`, `", $this->set["columns"])."`");
						$vals = "(";
						for($j=0; $j < count($this->set["values"]); $j++) {
							$vals .= '"'.$this->set["values"][$j][$i].'",';
						}
						$stmt .= substr($vals, 0, -2).'")';
						array_push($this->stmt, $stmt);
					}
				}
				// if direct is true then insert records directly
				// else it prepares sql for insert
				if ($direct===true) {
					if ($this->save()) {
						return true;
					}else{
						return false;
					}
				}else{
					return $this;
				}
			}
		}
	}

	/**
	 * replace method prepare sql query to replace data
	 * @param $data : array (takes ["column"=>"value",...])
	 * to save this records must run save() method after replace method
	 * @param $direct : bool (false| dont replace records directly)
	 * 						 (true | replace records directly)
	 * 						 (default | false)
	 * ========================================================
	 * if used with set method it directly replace records on replace() method call
	 * ex.	set("column","value")
	 * 		replace()
	 * it will directly replaces record set by set() method 
	 * ========================================================
	 * ------------------------{ Note }------------------------
	 * REPLACE Statement insert records | delete & insert records
	 * it can't be chained with where Statement
	 * --------------------------------------------------------
	 * ========================================================
	 **/
	public function replace($data=null, $direct=false){
		$columns = [];
		$values = [];
		$this->stmt = "REPLACE INTO `{$this->table}` (%s) VALUES (%s)";
		if ($data!==null && is_array($data)) {
			// prepare replace query for save method
			if (count($data)>0) {
				foreach($data AS $col => $val) {
					array_push($columns, $this->escape($col));
					array_push($values, $this->escape($val));
				}
				$this->stmt = sprintf($this->stmt, "`".implode("`, `", $columns)."`", '"'.implode('", "', $values).'"');
			}
			return $this;
		}elseif($data===null){
			// replace records collected by set method
			if (count($this->set["columns"]) > 0 && count($this->set["values"]) > 0) {
				// if values are flat array
				if (count($this->set["values"]) === count($this->set["values"], COUNT_RECURSIVE)) {
					$this->stmt = sprintf($this->stmt, "`".implode("`, `", $this->set["columns"])."`", '"'.implode('", "', $this->set["values"]).'"');
				}else{
					// if values are multi dimentional array
					$this->stmt = [];
					for ($i=0; $i < count($this->set["values"][0]); $i++) {
						$stmt = "REPLACE INTO `{$this->table}` (%s) VALUES ";
						$stmt = sprintf($stmt, "`".implode("`, `", $this->set["columns"])."`");
						$vals = "(";
						for($j=0; $j < count($this->set["values"]); $j++) {
							$vals .= '"'.$this->set["values"][$j][$i].'",';
						}
						$stmt .= substr($vals, 0, -2).'")';
						array_push($this->stmt, $stmt);
					}
				}
				// if direct is true then replace records directly
				// else it prepares sql for replace
				if ($direct===true) {
					if ($this->save()) {
						return true;
					}else{
						return false;
					}
				}else{
					return $this;
				}
			}
		}
	}

	/**
	 * to update multiple records try 'column1=value, column2=value2,...' AS param
	 * update("column=value", "col=val")
	 * @param $direct : bool (direct update | false)
	 * if set true it will directly update records
	 * only appied for using set()->where() method
	 **/
	public function update($data=null, $where=null, $direct=false){
		$this->stmt = "UPDATE $this->table SET";
		$set = '';
		if ($data!==null && !is_array($data) && $where!==null && !is_array($where)) {
			/**
			 * for => update("column=value", "col=val")
			 * single record only
			 * this needs to save() method call
			 */
			$this->where($where);
			$col = explode("=", $data)[0];
			$val = explode("=", $data)[1];
			$set .= " {$col}='{$val}'";
			$this->stmt .= "{$set} WHERE ".$this->where;
			return $this;
		}elseif ($data!==null && !is_array($data) && $where!==null && is_array($where)) {
			/**
			 * for => update("column=value", ["col"=>"val"])
			 * for single records
			 * this needs to save() method call
			 */
			$this->where($where);
			$col = explode("=", $data)[0];
			$val = explode("=", $data)[1];
			$set .= " {$col}='{$val}'";
			$this->stmt .= "{$set} WHERE ".$this->where;
			return $this;
		}elseif ($data!==null && is_array($data) && $where!==null && is_array($where)) {
			/**
			 * for => update(["column"=>"value"], ["col"=>"val"])
			 * for multiple records
			 * this needs to save() method call
			 */
			$this->where($where);
			foreach ($data AS $cols => $vals) {
				$set .= " {$cols}='{$vals}'";
			}
			$this->stmt .= "{$set} WHERE ".$this->where;
			return $this;
		}elseif($data===null && $where===null && count($this->set["columns"])>0){
			if ($this->where!==null) {
				/**
				 * for => update records prepared by set()->where() methods
				 * this method directly update records if $direct set true
				 */
				for ($i=0; $i < count($this->set["columns"]); $i++) { 
					$set .= " {$this->set["columns"][$i]}='{$this->set["values"][$i]}'";
					if ($i!==(count($this->set["columns"])-1)) {
						$set .= ", ";
					}
				}
				$this->stmt .= "{$set} WHERE {$this->where}";
				if ($direct===true) {
					if ($this->save()) {
						return true;
					}else{
						return false;
					}
				}else{
					return $this;
				}
			}else{
				throw new \Exception("SQL Error WHERE not set for UPDATE");
			}
		}else{
			throw new \Exception("SQL Error columns not set for UPDATE");
		}
	}

	/**
	 * delete method prepares relete records sql for table
	 * @param $where : string|array
	 * #string : "column=value"
	 * #array : ["column"=>"value"]
	 * call save() method to delete records
	 * @param $action : boolean (true|false)
	 * default false
	 * if dont want to call save() method and delete records
	 * directly then set $direct=true 
	 **/
	public function delete($where=null, $direct=false){
		$this->stmt = "DELETE FROM {$this->table} WHERE %s";
		if ($where!==null) {
			// for => delete("column=value")
			// for => delete(["column"=>"value"])
			$this->where($where);
			$this->stmt = sprintf($this->stmt, $this->where);
			return $this;
		}elseif ($where===null && $this->where!==null){
			// if $where set using where() method
			$this->stmt = sprintf($this->stmt, $this->where);
			return $this;
		}else{
			throw new \Exception("SQL Error WHERE not set");
		}

		if ($direct===true) {
			if ($this->save()) {
				return true;
			}else{
				return false;
			}
		}
	}


	/**
	 * inserId method
	 * If we perform an INSERT or UPDATE on a table
	 * with an AUTO_INCREMENT field, we can get
	 * the ID of the last inserted/updated record immediately.
	 **/
	public function insertId(){
		if (isset($this->conn->insert_id)) {
			$this->insert_id = $this->conn->insert_id;
		}
		return $this->insert_id;
	}

	/**
	 * @method save insert records
	 * prepare by sql query by insert() method
	 * OR
	 * by set()->where() methods
	 * depends on
	 * 			=>	update()
	 * 			=>	insert()
	 * 			=>	delete()
	 * this methods needs to call save() if not used set()->where()
	 **/
	public function save(){
		if (is_array($this->stmt)) {
			$ret = true;
			foreach ($this->stmt AS $stmt) {
				if ($this->conn->query($stmt)===true) {
					$this->status =  "success";
					$ret = true;
				}else {
					$this->status = $this->sql_error = "Error: " . $stmt . "<br>" . $this->conn->error;
					$ret = false;
				}
			}
			return $ret;
		}else{
			if ($this->conn->query($this->stmt)===true) {
				$this->status =  "success";
				return true;
			}else {
				$this->status = $this->sql_error = "Error: " . $this->stmt . "<br>" . $this->conn->error;
				return false;
			}
		}
	}

	/**
	 * @method	AdvanceTrim
	 * @param	string|Array $text
	 * multipurpose trim for array, object and string
	 * @since	1.2.1
	 **/
	public static function AdvanceTrim($text) {
		if (is_array($text)) {
			foreach ($text as $key => $value) {
				unset($text[$key]);
				$key = is_string($key) ? trim($key) : $key;
				if (is_array($value)) {
					$text[$key] = self::AdvanceTrim($value);
				} elseif (is_object($value)) {
					$text[$key] = (object) self::AdvanceTrim((array) $value);
				} else {
					$text[$key] = is_string($value) ? trim($value) : $value;
				}
			}
			return $text;
		} elseif (is_object($text)) {
			$text = (object) self::AdvanceTrim((array) $text);
			return $text;
		} else {
			return is_string($text) ? trim($text) : $text;
		}
	}

	/**
	 * @method close
	 * closes database connection
	 **/
	public function close(){
		try {
			$this->conn->close();
		} catch (mysqli_sql_exception $e) {
			$this->sql_error = $e->__toString();
		}
	}

	/**
	 * this class uses destructor method simply to close connection
	 **/
	public function __destruct(){
		if ($this->connect===true) {
			$this->close();
		}
	}
}