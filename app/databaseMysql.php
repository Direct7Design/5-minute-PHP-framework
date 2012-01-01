<?php
/**
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 */
class databaseMysql extends appDatabase {
  
    /**
     * Instance of the connection.
     * Implements singleton.
     * @var PDO 
     */
    private static $_instance; 
    
    /**
     * Database name to use - should be set for each model.
     * @var string
     */
    protected $_dbName;
    
    /**
     * Database table to use - should be set for each model.
     * @var string 
     */
    protected $_dbTable;
    
    public $_idField = "id";
    
    public $_cache = false;
    public $_cacheKey = "";
    protected $_crypted = false;
    
    public function __construct() {
        $this->selectTable();
	$this->_cacheKey = $this->_dbName.$this->_dbTable;
    }

    /**
     * Creates an instance of the connection (if needed) or returns existing one.
     * Can create 503 error when PDO returns exception or 500 when connection data is missing.
     * @return PDO Connection instance 
     */
    private static function getInstance(){ 
        if(!self::$_instance){ 
	    $host = appConfig::get("mysql_db_host");
	    $port = appConfig::get("mysql_db_port");
	    $user = appConfig::get("mysql_db_user");
	    $pass = appConfig::get("mysql_db_pass");
	    
	    if($host === false){
		appCore::sthrowError(500, "No db host provided!");
	    }
	    if($user === false){
		appCore::sthrowError(500, "No db user provided!");
	    }
	    if($pass === false){
		appCore::sthrowError(500, "No db password provided!");
	    }
	    if($port === false){
		appCore::sthrowError(500, "No db port provided!");
	    }

	    $dsn = "mysql:host=$host;port=$port";
	    
	    try{
		self::$_instance = new PDO($dsn, $user, $pass);
                if(!appConfig::get("debug")){
                    self::$_instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);                
                }
                else{
                    self::$_instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);                
                }
               self::$_instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);            
	    }
	    catch (PDOException $e){
		appCore::sthrowError(503, 'Could not connect: '.$e->getMessage());
	    }
        } 

        return self::$_instance; 
    }
        
    private function selectTable(){
	if(!$this->_dbName){
	    $this->throwError(500,"Model ".get_class($this)." does not have a table set!");
	}
	
	return self::getInstance()->exec("USE ".$this->_dbName);
    }
    
    public function getTableName(){
	return $this->_dbTable;
    }
    
    final public function load($sql, $params = false, $onlyExec = false){	
	if($this->_cache && !$fetchAll){
	    $cacheKey = sha1($sql);
	    $cachedVal = $this->cache()->get($cacheKey, $this->_cacheKey);
	    if($cachedVal) return $cachedVal;
	}

        $sth = self::getInstance()->prepare($sql);
	$exec = $sth->execute($params);
	if($onlyExec){
	    return $exec;
	}
	if(!$exec){
	    $error = $sth->errorInfo();
	    $this->throwError(500,$error[2]);
	}
	
	$result = $sth->fetchAll();
	if($result){
	    $mres = new MySqlResult($result, $this);
	    if($this->_cache && !$fetchAll){ 
		$this->cache()->set($cacheKey, $mres, $this->_cacheKey);
	    }
	    return $mres;	
	}
	return false;
    }
    
    public function deleteById($id){
	$sth = self::getInstance()->prepare("DELETE FROM ".$this->_dbTable." WHERE ".$this->_idField." = :id");
	$exec = $sth->execute(array(":id" => $id));
	if($this->_cache){
	    $this->cache()->clear($this->_cacheKey);
	}
	if(!$exec){
	    $error = $sth->errorInfo();
	    $this->throwError(500,$error[2]);
	}
	return $exec;
    }
       
    public function getById($id){
	$sql = "SELECT * FROM ".$this->_dbTable." WHERE ".$this->_idField." = :id";
	return $this->load($sql, array(":id" => $id));
    }
    
    public function getAll($page = 0, $perPage = 20){
	$sql = "SELECT * FROM ".$this->_dbTable;
	if($page > 0){
	    $offset = $page*$perPage-$perPage;
	    $sql .= " LIMIT $perPage OFFSET $offset";
	}
	return $this->load($sql);
    }
    
    public function crypt($data, $iv = false){ 
        if($this->_crypted){
	    return parent::crypt($data, md5($this->_dbTable));
        }
        return $data;
    }
    
    public function decrypt($data, $iv = false){
        if($this->_crypted){
            return parent::decrypt($data, md5($this->_dbTable));
        }
        return $data;
    }
}

class MySqlResult extends databaseResult {
    
    private $_result;
    private $_id;
    private $_position;
    private $_parentModel;
    
    public function __construct($data, $parentModel) {
       if(is_array($data)){
	   if(count($data) > 1){ //multiple results
	       $this->multiple = true;
	       $this->count = count($data);	       
	   }
	   else{
	       $this->multiple = false;
	       $this->count = 1;   
	   }
	   $this->_result = $data;
	   $this->_id = $parentModel->_idField;
	   $this->_parentModel = $parentModel;
	   $this->_position = 0;
       }
       else{
	   $this->throwError(500, "Returned result is not an array!".var_export($data, true));
       }
    }
    
    public function get($field = false){
	if(!$field) return $this->_result;
	
	if(isset($this->_result[$this->_position][$field])){
	    return $this->_parentModel->decrypt($this->_result[$this->_position][$field]);
	}
	
	$relationName = explode(".", $field);
	if(count($relationName) == 2 && isset($this->_relation[$relationName[0]])){
	    return $this->_relation[$relationName[0]]->get($relationName[1]);
	}

	return;	
    }
    
    public function addRelation($name, $data){
	if(!$this->multiple){
	    $this->_relation[$name] = $data; 
	}
    }
    
    public function set($field, $value){
	$this->_result[$this->_position][$field] = $value;
	return $this; //to allow chaining ->save() after this method
    }
    
    public function save($complete = false){
	if(!$this->valid()) $this->throwError(500, "Can't save the current object, pointer too big!");
	
	if($complete && $this->multiple){
	    foreach($this->_result as $result){
		$f = $this->saveOne($result);
		if(!$f){
		    return false;
		}
	    } 
	    return true;
	}else{
	    return $this->saveOne($this->_result[$this->_position]);
	}
    }
    
    private function saveOne($row){
	$keys = array();
	$values = array();
	foreach($row as $key => $value){
	    $keys[] = "$key = :$key";
	    $values[":".$key] = $value;
	}
	if(empty($keys)) return false;
	
	$sql = "UPDATE ".$this->_parentModel->getTableName()." SET ".implode(", ", $keys);
	return $this->_parentModel->load($sql, $values, true);   
	}
    
    public function rewind() {
        $this->_position = 0;
    }

    public function current() {
        return $this->_result[$this->_position];
    }

    public function key() {
        return $this->_position;
    }

    public function next() {
        ++$this->_position;
    }

    public function valid() {
        return isset($this->_result[$this->_position]);
    }
    
}