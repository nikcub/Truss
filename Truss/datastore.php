<?php

$_sql_entities = <<<EOF
CREATE TABLE Entities (
    id INTEGER AUTOINCREMENT,
    updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    body MEDIUMBLOB
);
EOF;

class DataStore {

	protected static $dh = null;
	protected static $name = '';
	protected static $options = array();

	public static function init($name, $options=null) {
		self::$name = $name;
		self::$options = $options;

		self::connect();
	}

	protected function connect() {
		$conn_template = "sqlite:%s";
		$connstr = sprintf($conn_template, self::get_data_filepath());

		try {
			self::$dh = new PDO($connstr);
			self::$dh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			// self::$dh->exec("SET CHARACTER SET utf8");
		} catch (PDOException $e) {
			throw new ServerError($e->getMessage());
		}
	}

	protected function load_entities() {
		$rs = self::$dh->exec('SELECT * FROM Entities');
		d($rs);
		echo $rs;
	}

	public function init_entities() {
		$sql = "create table Entities( id INTEGER PRIMARY KEY AUTOINCREMENT, created TIMESTAMP DEFAULT CURRENT_TIMESTAMP, value BLOB);";
		self::$dh->exec($sql);
	}

	public function put($entity) {

		if($entity->has_id()) {
			$sql = "UPDATE Entities set value='%s' where id=" . $entity->id;
		} else {
			$r = self::_save_entity($entity->pack());
			// $sql = "INSERT INTO Entities (value) VALUES ('%s')";		
			return $r;
		}
		$data = $entity->pack();
		$st = sprintf($sql, $data);
		d($st);
		try {
			$r = self::$dh->exec($st);
			d('insert return:' . $r .':');	
		} catch(PDOException $e) {
			d($e->getMessage());
		}
		
	}

	protected function _save_entity($data, $retry=0) {
		if($retry > 2) throw new ServerError('Too many retries');

		try {
			$stmt = self::$dh->prepare("INSERT INTO Entities (value) VALUES (:val)");
			$stmt->bindParam(':val', $data);
			$r = $stmt->execute();
			d('insert return:' . $r .':');
		} catch(PDOException $e) {
			d($e->getCode());
			// if($e->getCode() == 'HY000') {
				if(strstr($e->getMessage(), 'no such table: ')) {
					self::init_entities();
					$retry++;
					return self::_save_entity($data, $retry);
				// }
			} else {
				throw new ServerError($e->getMessage());
			}
		}
	}

	protected function create_store() {

	}
	protected function get_data_filepath() {
		return self::$options['datastore_dir'] . DIRECTORY_SEPARATOR . self::get_data_filename();
	}

	protected function get_data_filename() {
		return '.'. self::$name . '.db';
	}

	// get by key
	public function get($key) {

	}

	// get by query
	// for eg. DataStore.Query('SELECT * FROM Posts orderby datetime');
	public function Query($qs) {
		$res = array();
		try {
			foreach(self::$dh->query('select * from Entities') as $row) {
				$res[] = $row;
			}
			return $res;
		} catch (PDOException $e) {
			var_dump($e);
		}
		// foreach(self::$dh->query('select * from Entities') as $row) {
			// $res[] = $row;
		// }
		// return $res;
	}

}


class Field {
	public function escapeString($string) {
		return mysql_real_escape_string($string);
	}

	public function escapeArray($array) {
	    array_walk_recursive($array, create_function('&$v', '$v = mysql_real_escape_string($v);'));
		return $array;
	}
	
	public function to_bool($val) {
	    return !!$val;
	}
	
	public function to_date($val) {
	    return date('Y-m-d', $val);
	}
	
	public function to_time($val) {
	    return date('H:i:s', $val);
	}
	
	public function to_datetime($val) {
	    return date('Y-m-d H:i:s', $val);
	}
}

abstract class Model {
	protected $id;

	public function __set($name, $value) {
		echo property_exists($this, $name);
		if(property_exists($this, $name)) {
			$this->$$name = $value;
			return true;
		}
		throw new Exception('Model has no key ' . $name);
	}

	public function __get($name) {
		d('Model _get(' . $name .')');
		if(property_exists($this, $name)) {
			return $this->$$name;
		}
		throw new Exception('Model key fail ' . $name);
	}

	public function has_id() {
		return (property_exists($this, 'id') && isset($this->id) && is_int($this->id));
	}

	public function put() {
		DataStore::put($this);
	}

	public function pack() {
		return json_encode(get_class_vars(get_class($this)));
	}

	public function set() {

	}

	// get last x entries, with a pager/cursor
	public function last($num, $page) {

	}
	// Posts.all().order('dtime').query()
	public function all() {

	}

	public function filter() {

	}

	public function order() {

	}

	public function query() {

	}
}

abstract class Backend {

}

class Sqlite extends Backend {

}
