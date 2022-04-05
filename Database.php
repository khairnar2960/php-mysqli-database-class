<?php
/**
 * PHP ORM Class by HitraA Technologies
 * @author Harshal Khairnar
 * @version 1.0
 * @license MIT
 */
require('ResultObject.php');

class Database{
	// Database connection properties
	private $server = 'localhost';
	private $user = 'root';
	private $password = '';
	private $database = 'test';
	private $table = null;
	private $conn;
	private $charset = "utf8mb4";

	// MySQL properties
	private $stmt = null;
	private $selector = null;
	private $joinFormat = '%3$s JOIN %1$s ON %2$s';
	private $join = null;
	private $limit = null;
	private $offset = null;
	private $where = null;
	private $group_by = null;
	private $order_by = null;
	private $set = ["columns"=>[], "values"=>[]];

	// output properties
	private $result = [];
	private $status = null;
	private $insert_id = null;

	// pagination properties
	private $perPage;
	private $page;

	public $sql_error;

	/**
	 * constructor method creates database connection
	 **/
	public function __construct(){
		$this->conn();
	}

	/**
	 * errorReporting metho enables mysqli error reporting
	 * @param $reporting : boolean (true|false)
	 * if true then error reporting is on
	 * else error reporting remains off
	 **/
	public function errorReporting($reporting=false){
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

	protected function dbExists(){
		$this->conn(false);
		$result = $this->conn->query("SHOW DATABASES LIKE '{$this->database}'");
		if ($result->num_rows > 0) {
			return true;
		}else{
			return false;
		}
	}

	protected function tableExists($table){
		$result = $this->conn->query("SHOW TABLES LIKE '{$table}'");
		if ($result->num_rows > 0) {
			return true;
		}else{
			return false;
		}
	}

	/**
	 * database connection method
	 * returns mysqli connection object into $conn property
	 * @param $db : boolean (true|false), default : true
	 * if $db=false it will return connection without database
	 **/
	private function conn($db=true){
		if ($db===true && $this->dbExists()) {
			$conn = new \mysqli($this->server, $this->user, $this->password, $this->database);
		}elseif($db===false){
			$conn = new \mysqli($this->server, $this->user, $this->password);
		}
		if (isset($conn)) {
			// check connection is done or not
			if ($conn->connect_error) {
			  $this->sql_error = $conn->connect_error;
			}else{
				$this->conn = $conn;
				$this->setCharset();
				return $this;
			}
		}else{
			throw new \Exception("Database '{$this->database}' does not exists");
		}
	}

	/**
	 * useDatabase method changes default database and
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
		$this->conn();
		return $this;
	}

	/**
	 * Create database method
	 * @param $database : string (database_name)
	 * returns (success : string) if true
	 * else returns (Error : string)
	 **/
	public function createDatabase($database=null){
		if ($database!==null) {
			$this->conn(false);
			$sql = "CREATE DATABASE ".$database;
			if (!$this->dbExists()) {
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

	/**
	 * database table setter method
	 * table must be set before any method execution
	 * it will throw an exception if null or not set
	 * @param $table : string (table_name)
	 **/
	public function table($table=null){
		if ($table!==null) {
			if ($this->tableExists($table)) {
				$this->table = $table;
				return $this;
			}else{
				throw new \Exception("Table '{$table}' not exists in '{$this->database}' database");
			}
		}else{
			throw new Exception(__CLASS__."->table() can't be null");
		}
	}

	/**
	 * escape method for mysqli::real_escape_string()
	 * @param $string : string (value_to_insert)
	 **/
	private function escape($string=null){
		if ($string!==null) {
			return $this->conn->real_escape_string($string);
		}
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
	 * select method for selecting columns
	 * @param $selector = "column1, column2,...."
	 **/
	public function select($column=null, $column_as=null){
		if ($column!==null && $column!=="") {
			if ($this->selector!==null) {
				$this->selector .= ", {$column}";
			}else{
				$this->selector = $column;
			}
			if ($column_as!==null && $column_as!=="") {
				$this->selector .= " as ".$column_as;
			}
		}else{
			$this->selector = "*";
		}
		return $this;
	}

	/**
	 * distinct
	 **/
	public function distinct(){
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
				$this->selector .= " as ".$column_as;
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
				$this->selector .= " as ".$column_as;
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
				$this->selector .= " as ".$column_as;
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
				$this->selector .= " as ".$column_as;
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
				$this->selector .= " as ".$column_as;
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
				$this->selector .= " as ".$column_as;
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
	 * ex. query = SELECT MIN(age) as age FROM mytable
	 * will be called selectMin("age")
	 * ex. query = SELECT MIN(age) as xyz FROM mytable
	 * will be called selectMin("age","xyz")
	 **/
	public function selectMin($column=null, $column_as=null){
		if ($column!==null) {
			$selector = "MIN({$column})";
		}
		if ($column_as!==null){
			$selector .= " as {$column_as}";
		}else{
			$selector .= " as {$column}";
		}
		$this->selector = $selector;
		return $this;
	}

	/**
	 * selectMax selects MAX(column) from table
	 * @param $column : string (column_name)
	 * @param $column_as : string (column_name as)
	 * ex. query = SELECT MAX(age) as age FROM mytable
	 * will be called selectMax("age")
	 * ex. query = SELECT MAX(age) as xyz FROM mytable
	 * will be called selectMax("age","xyz")
	 **/
	public function selectMax($column=null, $column_as=null){
		if ($column!==null) {
			$selector = "MAX({$column})";
		}
		if ($column_as!==null){
			$selector .= " as {$column_as}";
		}else{
			$selector .= " as {$column}";
		}
		$this->selector = $selector;
		return $this;
	}

	/**
	 * selectAvg selects AVG(column) from table
	 * @param $column : string (column_name)
	 * @param $column_as : string (column_name as)
	 * ex. query = SELECT AVG(age) as age FROM mytable
	 * will be called selectAvg("age")
	 * ex. query = SELECT AVG(age) as xyz FROM mytable
	 * will be called selectAvg("age","xyz")
	 **/
	public function selectAvg($column=null, $column_as=null){
		if ($column!==null) {
			$selector = "AVG({$column})";
		}
		if ($column_as!==null){
			$selector .= " as {$column_as}";
		}else{
			$selector .= " as {$column}";
		}
		$this->selector = $selector;
		return $this;
	}

	/**
	 * selectSum selects SUM(column) from table
	 * @param $column : string (column_name)
	 * @param $column_as : string (column_name as)
	 * ex. query = SELECT SUM(age) as age FROM mytable
	 * will be called selectSum("age")
	 * ex. query = SELECT SUM(age) as xyz FROM mytable
	 * will be called selectSum("age","xyz")
	 * to get output use Database()->get()->getRow()
	 **/
	public function selectSum($column=null, $column_as=null){
		if ($column!==null) {
			$selector = "SUM({$column})";
		}
		if ($column_as!==null){
			$selector .= " as {$column_as}";
		}else{
			$selector .= " as {$column}";
		}
		$this->selector = $selector;
		return $this;
	}

	/**
	 * selectCount selects COUNT(column) from table
	 * @param $column : string (column_name)
	 * @param $column_as : string (column_name as)
	 * ex. query = SELECT COUNT(age) as count_age FROM mytable
	 * will be called selectCount("age")
	 * ex. query = SELECT COUNT(age) as xyz FROM mytable
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
			// $selector .= " as count_{$column}";
		}elseif($column_as!==null){
			$selector .= " as {$column_as}";
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
			if ($column_on_column!==null) {
				if ($join_type!==null) {
					$join_type = strtoupper($join_type);
				}else{
					$join_type = "";
				}
				$this->join = sprintf($this->joinFormat, $table, $column_on_column, $join_type);
			}else{
				throw new Exception("SQL Error columns not selected for JOIN");
			}
		}else{
			throw new Exception("SQL Error table not selected for JOIN");
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
			$this->stmt = "SELECT {$this->selector} FROM ".$this->table;
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
	 * it will parse query as column='value'
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
	 * then specify as second argument
	 * ex. where(col2=val2, "or")
	 * ======================================================
	 * if $where is string and and_or != "AND/OR"
	 * then it will parse both arguments as seperate column and value
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
			$where = $where[0].$where[1]."'".trim($where[2],"'\"")."'";
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
	 * getWhere method same as where method
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
			$column = $raw[0];
			$option = $raw[1];
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
					$this->order_by .= ", `{$column}` {$option}";
				}
			}else{
				if ($option=="RANDOM") {
					$option = " RAND()";
					$this->order_by = "{$option}";
				}else{
					$this->order_by = "`{$column}` {$option}";
				}
			}
		}
		return $this;
	}


	/**
	 * paginate method to get pagination result
	 **/
	public function paginate($perPage=10, $page=null){
		$this->perPage = $perPage;
		$this->page = $page;
		return $this;
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
	 * getResult method gets result as object array
	 * @uses ResultObject Class to get object of result
	 * 
	 * ex.
	 * require('Database.php');
	 * 
	 * $db = new Database();
	 * $table = $db->table("table_name");
	 * $table->select("col1, col2, col3");
	 * $table->get();
	 * $query = $table->getResult();
	 * foreach ($query as $row) {
	 * 	echo $row->col1;
	 * 	echo $row->col2;
	 * 	echo $row->col3;
	 * }
	 **/
	public function getResult(){
		$this->result = [];
		$data = $this->conn->query($this->stmt);
		$this->num_rows = $data->num_rows;
		if ($this->num_rows > 0) {
			// output data of each row as associative array
			while($row = $data->fetch_assoc()) {
				array_push($this->result, new ResultObject($row));
			}
		}
		return $this->result;
	}

	/**
	 * getResultArray method gets result as associative array
	 **/
	public function getResultArray(){
		$this->result = [];
		$data = $this->conn->query($this->stmt);
		$this->num_rows = $data->num_rows;
		if ($this->num_rows > 0) {
			// output data of each row as associative array
			while($row = $data->fetch_assoc()) {
				array_push($this->result, $row);
			}
		}
		return $this->result;
	}


	/**
	 * getRow method gets row as object
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
				array_push($this->result, new ResultObject($row));
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
	 * getRowArray method gets row as array
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
			while($row = $data->fetch_row()) {
				array_push($this->result, $row);
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
	 * muti value array also taken in set method as column argument
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
						foreach ($vals[$i] as $val) {
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
					foreach ($strArray as $column) {
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
				foreach($data as $col => $val) {
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
	 * to update multiple records try 'column1=value, column2=value2,...' as param
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
			foreach ($data as $cols => $vals) {
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
				throw new Exception("SQL Error WHERE not set for UPDATE");
			}
		}else{
			throw new Exception("SQL Error columns not set for UPDATE");
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
			throw new Exception("SQL Error WHERE not set");
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
	 * save method insert records
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
			foreach ($this->stmt as $stmt) {
				if ($this->conn->query($stmt)===true) {
					$this->status =  "success";
					$ret = true;
				}else {
					$this->status =  "Error: " . $stmt . "<br>" . $this->conn->error;
					$ret = false;
				}
			}
			return $ret;
		}else{
			if ($this->conn->query($this->stmt)===true) {
				$this->status =  "success";
				return true;
			}else {
				$this->status =  "Error: " . $this->stmt . "<br>" . $this->conn->error;
				return false;
			}
		}
	}

	/**
	 * this class uses destructor method simply to close connection
	 **/
	public function __destruct(){
		try {
			$this->conn->close();
		} catch (mysqli_sql_exception $e) {
			$this->sql_error = $e->__toString();
		}
	}
}