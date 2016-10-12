<?php
/*
 * +------------------------------------------------------------------------+
 * | MaskPHP - A PHP Framework For Beginners                                |
 * | @package       : MaskPHP                                               |
 * | @authors       : MaskPHP                                               |
 * | @copyright     : Copyright (c) 2015, MaskPHP                           |
 * | @since         : Version 1.0.0                                         |
 * | @website       : http://maskphp.com                                    |
 * | @e-mail        : support@maskphp.com                                   |
 * | @require       : PHP version >= 5.3.0                                  |
 * +------------------------------------------------------------------------+
 */

namespace System\Database;

class Mysql{
	public
		$config	= array(),
		$debug 	= array();

	protected
		$current_server = null,
		$link			= array();

	function __construct($args = null){
		// set config
		if($args){
			$this->config = (array)$args;
		}

		// auto close all connect
		\M::get('event')->hook('system.shutdown', function(){
			$this->close();
		});

		\M::get('event')->hook('system.debug.on_get_data', function(&$debug){
			$count 	= 0;
			$msg 	= '';
			foreach($this->debug as $items){
				$count += count($items);
				if($items){
					$msg .= '<br>- ' . implode('<br>- ', $items);
				}
			}

			if($count > 0){
				$debug['sql_query'] = array('label' => 'SQL Query', 'msg' => $count . $msg);
			}

			return $debug;
		});
	}

	/**
	 * get server
	 * @param  string $server
	 */
	public function get($server = 'local'){
		// get config
		if(!isset($this->config[$server])){
			$args = $this->config;
		}else{
			$args = $this->config[$server];
		}

		// get instance
		if(!isset($this->link[trim_lower($server)])){
			$this->link[$server] = new Mysql_Query($server, $args);
		}

		// debug
		if(!isset($this->debug[$server])){
			$this->debug[$server] = array();
		}

		return $this->link[$server];
	}

	/**
	 * close connect
	 */
	public function close(){
		foreach($this->link as $v){
			mysqli_close($v);
		}
		$this->link = array();
		return $this;
	}

	/**
	 * expand controller method
	 * @param  string $name
	 * @param  array $args
	 */
	function __call($name, $args){
		return \M::get('event')->expand('system.database.mysql.expand.' . $name, $args, $this);
	}
}

class Mysql_Query{
	public
		$error			= null;

	protected
		$identifier 	= '/^[a-zA-Z0-9\$\_\.\*]+$/',
		$statement		= null,

		$default_query	= array(
			'TABLE'		=> '',

			'OPTION'	=> array(),
			
			'FIELD'		=> array(),

			'VALUE'		=> array(),

			'JOIN'		=> array(),

			'WHERE'		=> array(),
			'HAVING'	=> array(),
			'ON'		=> array(),

			'GROUP_BY'	=> array(),
			'ORDER_BY'	=> array(),

			'LIMIT'		=> '',

			'DUPLICATE'	=> array(),

			'PARTITION'	=> array(),

			'EXTRA'		=> ''
		),
		$server 		= null,
		$current_db 	= null,
		$query			= null,
		$link 			= null;

	function __construct($server, $args = null){
		// set query
		$this->query = $this->default_query;

		// server name
		$this->server = $server;

		// connect
		$this->connect($args);
	}

	/**
	 * connect
	 * @param  array $args
	 */
	public function connect($args){
		// set port
		if(empty($args['port'])){
			$args['port'] = 3306;
		}

		// set socket
		if(!isset($args['socket'])){
			$args['socket'] = null;
		}

		// set database
		if(empty($args['database'])){
			$args['database'] = 'mysql';
		}

		$this->current_db = $args['database'];

		// connect
		$this->link = new \mysqli($args['host'], $args['username'], $args['password'], $args['database'], $args['port'], $args['socket']);

		// set charset
		if(!empty($args['charset'])){
			$this->link->set_charset($args['charset']);
		}

		return $this;
	}

	public function get_link(){
		return $this->link;
	}

	/**
	 * get database
	 * @param  string $name
	 */
	
	public function database($name){
		$this->current_db = trim_lower($name);
		mysqli_select_db($this->link, $name);
		return $this;
	}

	/**
	 * close connect
	 */
	public function close(){
		mysqli_close($this->link);
	}

	/**
	 * set charset
	 * @param  string $charset
	 */
	public function charset($charset = 'utf8'){
		$this->link->set_charset($charset);
		return $this;
	}

	/**
	 * escape
	 * @param  $var
	 */
	public function escape($var){
		if(is_string($var)){
			if(function_exists('mysql_real_escape_string')){
				$var = mysqli_real_escape_string($this->link, $var);
			}else{
				$s = array('\\', "\0", "\n", "\r", "'", '"', "\x1a");
				$d = array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z');
				$var = str_replace($s, $d, $var);
			}
			$var = '"' . $var . '"';
		}elseif(is_null($var)){
			return 'NULL';
		}elseif(!is_numeric($var)){
			$var = $var ? 'TRUE' : 'FALSE';
		}

		// core.database.mysql.escape
		\M::get('event')->change('system.database.mysql.on_escape', $var);

		return $var;
	}

	/**
	 * escape name
	 * @param  string $name
	 */
	private function escape_name($name){
		$name = explode(',', $name);
		$ret = '';

		foreach($name as $n){
			if(!preg_match($this->identifier, trim_lower($n))){
				return $n;
			}

			if(strpos($n, '.')){
				$n = explode('.', $n);
				$ret .= ',`' . $n[0] . '`.' . ($n[1] === '*' ? '*' : '`' . $n[1] . '`');
			}else{
				$ret .= ', `' . $n . '` ';
			}
		}

		return trim($ret, ',');
	}

	/**
	 * get current database
	 */
	public function get_current_db(){
		if(!$this->current_db){
			$query = $this->link->query('SELECT database() AS db');
			while($row = mysqli_fetch_array($query, MYSQLI_ASSOC)){
				$this->current_db = $row['db'];
				break;
			}
			mysqli_free_result($query);
		}

		return $this->current_db;
	}

	/**
	 * excute sql query
	 * @param  string $sql
	 * @param  boolean $type: true -> objec; false -> array
	 */
	public function query($sql = null, $type = false){
		// store data
		static $data = array();

		// check select
		$select = false;
		if(!is_string($sql) || !trim($sql)){
			// get type
			$type = $sql === true ? true : false;

			// get sql
			$sql = $this->build_query_string($this->statement);
		}

		// check is SELECT
		if(preg_match('/^\s*(SELECT|SHOW)/i', $sql)){
			$select = true;
		}

		// excute query
		\M::get('event')->change('system.database.mysql.on_get_sql_query', $sql);
		$result = $this->link->query($sql);

		// set default
		$this->statement = null;
		$this->query = $this->default_query;

		// check error
		if($this->error = $this->link->error){
			return false;
		}

		$debug = false;
		if(isset(\M::get('debug')->display['sql_query'])){
			$debug = true;
		}

		if($select){
			// remove LIMIT 0,...
			$hash = preg_replace('/LIMIT0,(\d+)$/i', 'LIMIT$1', preg_replace('/[`\s]+/', '', $sql));
			// remove WHERE 1 AND
			$hash = preg_replace('/WHERE\s+1\s+AND/i', 'WHERE', $hash);

			if(!isset($data[trim_lower($hash)])){
				$data[$hash] = array();
				while($row = $type ? mysqli_fetch_object($result) : mysqli_fetch_array($result, MYSQLI_ASSOC)){
					// on_get_row
					\M::get('event')->change('system.database.mysql.on_get_row', $row);
					$data[$hash][] = $row;
				}
			}else{
				$debug = false;
			}

			// on_get_data
			\M::get('event')->change('system.database.mysql.on_get_data', $data[$hash]);

			mysqli_free_result($result);
		}

		// hook debug
		if($debug){
			\M::get('db')->driver('mysql')->debug[$this->server][] = $sql . ' <i>(' . $this->server . '.' . $this->get_current_db() . ')</i>';
		}

		return $select ? $data[$hash] : true;
	}

	/**
	 * build query string
	 * @param  string $statement
	 */
	private function build_query_string($statement){
		$q =& $this->query;
		// where
		$where	= ' ' . ($q['WHERE'] ? ' WHERE 1 ' . implode(' ', $q['WHERE']) : '');
		// option
		$option = ' ' . implode(' ' , $q['OPTION']);
		// extra
		$extra 	= ' ' . $q['EXTRA'] . ' ';

		switch($statement){
			case 'SELECT':
				$field 	= ' ' . implode(',' , $q['FIELD']);
				$join 	= ' ' . implode(' ', $q['JOIN']);
				$on		= ' ' . ($q['ON'] ? ' ON 1 ' . implode(' ', $q['ON']) : '');
				$group	= ' ' . ($q['GROUP_BY'] ? ' GROUP BY ' . implode(',', array_keys($q['GROUP_BY'])) : '');
				$having	= ' ' . ($q['HAVING'] ? ' HAVING 1 ' . implode(' ', $q['HAVING']) : '');
				$limit 	= ' ' . ($q['LIMIT'] ? ' LIMIT ' . $q['LIMIT'] : '');
				// order by
				$order = '';
				foreach($q['ORDER_BY'] as $k => $v){
					$order .= $k . ' ' . $v . ',';
				}
				$order = ' ' . ($q['ORDER_BY'] ?  ' ORDER BY ' . trim($order, ',') . ' ' : '');

				return 'SELECT ' . $option . $field . ' FROM ' . $q['TABLE'] . $join . $on . $where . $group . $having . $order . $limit;
			break;
			
			case 'INSERT':
				$field		= ' ' . ($q['FIELD'] ? ' (' .  implode(',' , $q['FIELD']) . ')' : '');
				$duplicate 	= ' ' . ($q['DUPLICATE'] ? ' ON DUPLICATE KEY UPDATE ' . implode(', ', $q['DUPLICATE']) : '');
				$value 		= implode(',' , $q['VALUE']);

				return 'INSERT ' . $option . ' INTO ' . $q['TABLE'] . $field . ' VALUES ' . $value . $extra . $duplicate;
			break;

			case 'UPDATE':
				$value = implode(',', $q['VALUE']);
				return 'UPDATE ' . $option . $q['TABLE'] . ' SET ' . $value . $where . $extra;
			break;

			case 'DELETE':
				return 'DELETE ' . $option . ' FROM ' . $q['TABLE'] . $extra . $where;
			break;

			default:
				return '';
			break;
		}
	}

	/**
	 * fetch one row
	 * @param  string $sql
	 * @param  boolean $type: true -> objec; false -> array
	 */
	public function fetch_one($sql = null, $type = false){
		return $this->fetch_row($sql, $type);
	}

	/**
	 * fetch one row
	 * @param  string $sql
	 * @param  boolean $type: true -> objec; false -> array
	 */
	public function fetch_row($sql = null, $type = false){
		if(!is_string($sql)){
			$type = $sql === true ? true : false;
		}

		$ret = $this->fetch($sql, $type, true);

		if(isset($ret[0])){
			$ret = $ret[0];
		}
		return $ret;
	}

	/**
	 * fetch all row
	 * @param  string $sql
	 * @param  boolean $type: true -> objec; false -> array
	 * @param  boolean $fetch_one
	 */
	public function fetch($sql = null, $type = false, $fetch_one = false){
		if(!is_string($sql)){
			$type = $sql === true ? true : false;
			$sql = $this->build_query_string($this->statement);
		}

		// check is SELECT
		if(!preg_match('/^\s*SELECT/i', $sql)){
			\M::exception('System\Database\Mysql\Mysql_Query->fetch(...) : excuting the query "%s" failed', $sql);
		}

		if($fetch_one){
			$sql = preg_replace('/LIMIT\s*[0-9,]+\s*$/i', '', $sql);
			$sql .= ' LIMIT 1 ';
		}

		return $this->query($sql, $type);
	}

	/**
	 * get last insert id
	 */
	public function last_insert_id(){
		try{
			return mysqli_insert_id($this->link);
		}catch(\Exception $e){
			return 0;
		}
	}

	/**
	 * SELECT 
	 * @param  string|array $fields
	 * @param  string $key
	 */
	public function select($fields = '*', $key = null){
		if(!$fields){
			$fields = '*';
		}

		$this->field($fields, $key);
		$this->statement = 'SELECT';
		return $this;
	}

	/**
	 * FIELD
	 * @param  string|array $fields
	 * @param  string $key
	 */
	public function field($fields = '', $key = null){
		if(is_string($fields)){
			$fields = explode(',', trim($fields, ','));
		}

		if(count($fields) == 1 && $key){
			$field = $fields[0];
			$fields = array($field => $key);
		}

		foreach($fields as $k => $v){
			if(is_string($k)){
				$this->query['FIELD'][trim_lower($k)] = ($k === '*' ? '*' : $this->escape_name($k)) . ($v ? ' AS ' . $this->escape_name($v) : ' ');
			}else{
				$this->query['FIELD'][trim_lower($v)] = $v === '*' ? '*' : $this->escape_name($v);
			}
		}

		return $this;
	}

	/**
	 * OPTION
	 * @param  string $option
	 */
	function option($option){
		$option = strtoupper(trim_lower($option));
		$this->query['OPTION'][$option] = $option;
		return $this;
	}

	/**
	 * from
	 * @param  string $table
	 * @param  string $key
	 */
	public function from($table, $key = ''){
		\M::get('event')->change('system.database.mysql.on_filter_table', $table);

		if(preg_match($this->identifier, $table)){
			$this->query['TABLE'] = $this->escape_name($table);
		}else{
			$this->query['TABLE'] = '(' . $table . ')';
		}

		$this->query['TABLE'] .= (!empty($key) ? ' AS ' . $this->escape_name($key) : '');

		return $this;
	}

	/**
	 * INNER JOIN
	 * @param  string $table
	 * @param  string $key
	 * @param  string $type
	 */
	public function inner_join($table, $key = '', $type = 'INNER JOIN'){
		$this->query['JOIN'][] = $type . ' ' 
			. (preg_match($this->identifier, $table) ? $this->escape_name($table) : '(' . $table . ')')
			. (!empty($key) ? ' AS ' . $this->escape_name($key) : '');
		return $this;
	}

	/**
	 * LEFT JOIN
	 * @param  string $table
	 * @param  string $key
	 */
	public function left_join($table, $key = ''){
		return $this->inner_join($table, $key, 'LEFT JOIN');
	}

	/**
	 * RIGHT JOIN
	 * @param  string $table
	 * @param  string $key
	 */
	public function right_join($table, $key = ''){
		return $this->inner_join($table, $key, 'RIGHT JOIN');
	}

	/**
	 * WHERE
	 * @param  string $field
	 * @param  string $operator
	 * @param  $value
	 * @param  string $type
	 * @param  string $clause
	 * @param  boolea $escape
	 */
	public function where($field, $operator = null, $value = EMPTY_VALUE, $type = 'AND', $clause = 'WHERE', $escape = true){
		$type = $type . ' ';

		if(!$operator && is_empty($value)){
			$this->query[$clause][] = ' ' . $type . ' ' . $field;
		}elseif($operator && !is_empty($value)){
			$ret = ' ' . $type . $this->escape_name($field) . ' ' . $operator . ' ';

			$operator = strtoupper(trim($operator));
			switch($operator){
				case 'IN':
					$_value = '';
					if(is_string($value)){
						$value = explode(',', $value);
					}

					foreach($value as $v){
						$_value .= $this->escape(trim($v)) . ',';
					}
					
					$_value = trim($_value, ',');

					$ret .= '(' . $_value . ')';
				break;

				case 'BETWEEN':
					if(is_array($value)){
						$ret .= $this->escape($value[0]) . ' AND ' . $this->escape($value[1]);
					}else{
						$ret .= $value;
					}
				break;

				case 'GREATEST':
				case 'INTERVAL':
					if(is_array($value)){
						$ret .= '(' . implode(',', $value) . ')';
					}else{
						$ret .= '(' . $value . ')';
					}
				break;

				default:
					if(is_string($value)){
						$ret .= $escape ? $this->escape($value) : $this->escape_name($value);
					}else{
						$ret .= (int)$value;
					}
				break;
			}

			$this->query[$clause][] = $ret;
		}

		return $this;
	}

	/**
	 * OR WHERE
	 * @param  string $field
	 * @param  string $operator
	 * @param  $value
	 */
	public function or_where($field, $operator = '', $value = ''){
		return $this->where($field, $operator, $value, 'OR');
	}

	/**
	 * ON
	 * @param  string $field
	 * @param  string $operator
	 * @param  $value
	 * @param  string $type
	 */
	public function on($field, $operator = '', $value = '', $type = 'AND'){
		return $this->where($field, $operator, $value, $type, 'ON', false);
	}

	/**
	 * OR ON
	 * @param  string $field
	 * @param  string $operator
	 * @param  $value
	 */
	public function or_on($field, $operator = '', $value = ''){
		return $this->on($field, $operator, $value, 'OR');
	}

	/**
	 * HAVING
	 * @param  string $field
	 * @param  string $operator
	 * @param  $value
	 * @param  string $type
	 */
	public function having($field, $operator = '', $value = '', $type = 'AND'){
		return $this->where($field, $operator, $value, $type, 'HAVING');
	}

	/**
	 * OR HAVING
	 * @param  string $field
	 * @param  string $operator
	 * @param  $value
	 */
	public function or_having($field, $operator = '', $value = ''){
		return $this->having($field, $operator, $value, 'OR');
	}

	/**
	 * GROUP BY
	 * @param  string $field
	 */ 
	public function group_by($field){
		$field = explode(',', $field);
		foreach($field as $v){
			$this->query['GROUP_BY'][$this->escape_name($v)] = null;
		}
		return $this;
	}

	/**
	 * ORDER BY
	 * @param  string $field
	 * @param  int|boolean $sort
	 */
	public function order_by($field, $sort = true){
		if(is_array($field)){
			foreach($field as $k => $v){
				$this->query['ORDER_BY'][$this->escape_name($k)] = (boolean)$v ? 'DESC' : 'ASC';
			}

			return $this;
		}

		$field = explode(',', $field);
		foreach($field as $v){
			$this->query['ORDER_BY'][$this->escape_name($v)] = (boolean)$sort ? 'DESC' : 'ASC';
		}
		return $this;
	}

	/**
	 * LIMIT 
	 * @param  int $segment
	 * @param  int $offest
	 */
	public function limit($segment, $offset = 0){
		$this->query['LIMIT'] = (int)$offset . ',' . (int)$segment;
		return $this;
	}

	/**
	 * DELETE
	 */
	public function delete(){
		$this->statement = 'DELETE';
		return $this;
	}

	/**
	 * UPDATE
	 * @param  string|array $field
	 * @param  $value
	 */
	public function update($field, $value = null){
		$fields = array();
		if(is_string($field)){
			$fields[$field] = $value;
		}else{
			$fields = (array)$field;
		}

		foreach($fields as $k => $v){
			$k = $this->escape_name($k);
			$this->query['VALUE'][$k] = $k . '=' . $this->escape($v);
		}

		$this->statement = 'UPDATE';
		return $this;
	}

	/**
	 * INSERT MULTIPLE ROWS
	 * @param  array $rows
	 */
	public function insert($rows){
		foreach($rows as $row){
			$ret = '';
			foreach($row as $k => $v){
				if(is_string($k)){
					$this->field($k);
				}

				$ret .= $this->escape($v) . ',';
			}

			$this->query['VALUE'][] = '(' . trim($ret, ',') .')';
		}
		$this->statement = 'INSERT';
		return $this;
	}

	/**
	 * INSERT ONE ROW
	 * @param  array $row
	 */
	public function insert_row($row){
		return $this->insert(array($row));
	}

	/**
	 * INTO
	 * @param  string $table
	 */
	public function into($table){
		\M::get('event')->change('system.database.mysql.on_filter_table', $table);
		$this->query['TABLE'] = $this->escape_name($table);
		return $this;
	}

	/**
	 * on duplicate
	 * @param  string|array $args
	 */
	public function on_duplicate($args){
		if(is_string($args)){
			$this->query['DUPLICATE'][] = $args;
		}else if(is_array($args)){
			foreach($args as $k => $v){
				$this->query['DUPLICATE'][$k = $this->escape_name($k)] = $k . '=' . $v;
			}
		}

		return $this;
	}

	/**
	 * extra query sql
	 * @param  string $query
	 */
	public function extra($query){
		$this->query['EXTRA'] = $query;
		return $this;
	}

	/**
	 * get list database
	 */
	public function get_database(){
		$ret = array();
		foreach($this->query('SHOW DATABASES') as $v){
			$ret[] = current($v);
		}
		return $ret;
	}

	/**
	 * get list tables
	 * @param  string $db;
	 */
	public function get_table($db = null){
		if($db){
			$this->database($db);
		}

		$ret = array();
		foreach($this->query('SHOW TABLES') as $v){
			$ret[] = current($v);
		}
		return $ret;
	}

	/**
	 * get list fields
	 * @param  string $table
	 * @param  string $database
	 */
	public function get_field($table, $database = null){
		if($database){
			$this->database($database);
		}

		$ret = array();
		if($result = $this->link->query('SELECT * FROM ' . $table . ' LIMIT 1')){
			foreach($result->fetch_fields() as $f){
				$ret[] = $f->name;
			}
		}
		return $ret;
	}

	/**
	 * partition
	 * @param  string|array $partition
	 */
	public function partition($partition){
		if(is_string($partition)){
			$partition = explode(',', $partition);
		}
		
		foreach($partition as $v){
			if(!isset($this->query['PARTITION'][$v = $this->escape_name($v)])){
				$this->query['PARTITION'][$v] = null;
			}
		}

		return $this;
	}

	/**
	 * create partition
	 * @param  string $table
	 * @param  string $by
	 * @param  string|array $p
	 * @param  boolean $ignore
	 */
	public function create_partition($table, $by, $p, $ignore = true){
		$sql = 	"ALTER " . ($ignore ? 'IGNORE' : '') . " TABLE " . $this->escape_name($table) . " PARTITION BY " . $by;

		$partition = '';
		if(is_int($p) && $p > 0){
			$partition .= "PARTITIONS " . $p;
		}elseif(is_string($p)){
			$partition .= trim(trim(trim($p), ')'), '(');
		}else{
			foreach($p as $k => $v){
				$partition .= "PARTITION " . $this->escape_name($k) . " VALUES " . $v . ",";
			}

			$partition = ' (' . trim($partition, ',') . ')';
		}

		$sql .= $partition;
		return $this->query($sql);
	}

	/**
	 * add partition
	 * @param  string $table
	 * @param  string|array $p
	 */
	public function add_partition($table, $p){
		$partition = '';
		if(is_string($p)){
			$partition .= trim(trim(trim($p), ')'), '(');
		}else{
			foreach($p as $k => $v){
				$partition .= "PARTITION " . $this->escape_name($k) . " VALUES " . $v . ",";
			}

			$partition = trim($partition, ',');
		}

		return $this->query("ALTER TABLE " . $this->escape_name($table) . " ADD PARTITION (" . $partition . ")");
	}

	/**
	 * drop partition range
	 * @param  string $table
	 * @param  string|array $p
	 */
	public function drop_partition($table, $p){
		$partition = array();
		if(is_string($p)){
			$partition = explode(',', $p);
		}

		foreach($partition as $v){
			$partition .= $this->escape_name($v) . ',';
		}
		$partition = trim($partition, ',');

		return $this->query("ALTER TABLE " . $this->escape_name($table) . " DROP PARTITION " . $partition);
	}

	/**
	 * empty partition
	 * deletes all rows from partition
	 * @param  string $table
	 * @param  string|array $p
	 */
	public function empty_partition($table, $p){
		$partition = array();
		if(is_string($p)){
			$partition = explode(',', $p);
		}

		foreach($partition as $v){
			$partition .= $this->escape_name($v) . ',';
		}
		$partition = trim($partition, ',');

		return $this->query("ALTER TABLE " . $this->escape_name($table) . " TRUNCATE PARTITION " . $partition);
	}

	/**
	 * expand controller method
	 * @param  string $name
	 * @param  array $args
	 */
	function __call($name, $args){
		return \M::get('event')->expand('system.database.mysql_query.expand.' . $name, $args, $this);
	}
}