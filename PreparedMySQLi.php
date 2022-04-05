<?php
class PreparedMySQLi{
	private $conn;
	protected $server, $database, $user, $password, $table;
	private $columns;
	private $stmt;
	private $prepared;
	private $data;
	private $insertID;
	public function __construct($server, $user, $password, $database){
		$this->server = $server;
		$this->user = $user;
		$this->password = $password;
		$this->database = $database;
	}
	public function connect(){
		$this->conn = new mysqli($this->server, $this->user, $this->password, $this->database);
		return $this->conn;
	}
	public function table($table){
		$this->table = $table;
		return $this;
	}
	public function prepareInsert(...$columns){
		$this->columns = $columns;
		$bind = array();
		for ($i=0; $i < count($columns); $i++) {
			array_push($bind, "?");
		}
		$this->stmt = "INSERT INTO `{$this->table}` (`".implode("`, `", $this->columns)."`) VALUES (".implode(", ", $bind).")";
		foreach ($this->columns as $key => $col) {
			$this->columns[$key] = &${$this->columns[$key]};
		}
		$this->prepared = $this->conn->prepare($this->stmt);
		return $this;
	}
	private function getTypeParams(){
		$param = "";
		foreach ($this->data as $key => $value) {
			if (is_array($value)) {
				$param = array();
				foreach ($value as $k => $val) {
					if (gettype($val)==="boolean") {
						array_push($param, "i");
						$this->data[$key][$k] = intval($val);
					}else{
						array_push($param, gettype($val)[0]);
					}
				}
				$param = implode("", $param);
			}else{
				if (gettype($value)==="boolean") {
					$param .= "i";
					$this->data[$key] = intval($value);
				}else{
					$param .= gettype($value)[0];
				}
			}
		}
		return $param;
	}
	public function bind(array $data){
		$this->data = $data;
		$param = $this->getTypeParams();
		$this->prepared->bind_param("{$param}", ...$this->columns);
	}
	public function execute(){
		if (is_array($this->data[0])) {
			foreach ($this->data as $key => $value) {
				foreach ($value as $k => $val) {
					$this->columns[$k] = $val;
				}
				$this->prepared->execute();
			}
		}else{
			foreach ($this->data as $key => $value) {
				$this->columns[$key] = $value;
			}
			$this->prepared->execute();
		}
		$this->insertID = $this->prepared->insert_id;
	}
	public function select($cols="*"){
		$this->stmt = "SELECT {$cols} FROM {$this->table}";
	}
	public function insert_id(){
		return $this->insertID;
	}
}
$db = new PreparedMySQLi("localhost", "root", "", "test");
$db->table("test_table");
$db->connect();
$db->prepareInsert("name", "surname", "email");

// single record
// $db->bind(array("fullname1", "fanceysurname1", "fancy1@email.com"));
// $db->execute();
// echo $db->insert_id();


// multiple records
// $db->bind(
// 	array(
// 		["fullname7", "fanceysurname7", "fancy7@email.com"],
// 		["fullname8", "fanceysurname8", "fancy8@email.com"],
// 		["fullname9", "fanceysurname9", "fancy9@email.com"],
// 		["fullname10", "fanceysurname10", "fancy10@email.com"],
// 		["fullname11", "fanceysurname11", "fancy11@email.com"],
// 	)
// );
// $db->execute();
// echo $db->insert_id();



// $result = $mysqli->query('SELECT id, label FROM test');
// var_dump($result->fetch_all(MYSQLI_ASSOC));