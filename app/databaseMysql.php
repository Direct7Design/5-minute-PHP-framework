<?php
/**
 * Driver for MySQL.
 * Almost every method in this class will throw 503 error on connection problems.
 * @uses PHP_MANUAL#PDO to connect to the database.
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 * @subpackage databaseDrivers
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
    
    /**
     * Default id field name. 
     * Can be changed per model.
     * @var string
     */
    public $_idField = "id";
    
    /**
     * Whether or not the data should be cached.
     * Can be changed per model.
     * @var type 
     */
    public $_cache = false;
    
    /**
     * Default cache namespace. It's set in {@link __construct()}.
     * @var string 
     */
    public $_cacheKey = "";
    
    /**
     * Whether or not the data should be encrypted.
     * Can be changed per model.
     * @var bool 
     */
    protected $_crypted = false;
    
    /**
     * Selects appropriate database table and sets {@link $_cacheKey}.
     */
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
        
    /**
     * Selects appropriate database table.
     * @return mixed Value returned by {@link PDO::exec()}. 
     */
    private function selectTable(){
	if(!$this->_dbName){
	    $this->throwError(500,"Model ".get_class($this)." does not have a table set!");
	}
	
	return self::getInstance()->exec("USE ".$this->_dbName);
    }
    
    /**
     * Returns the name of current table.
     * @return string 
     */
    public function getTableName(){
	return $this->_dbTable;
    }
    
    /**
     * Executes given SQL query and returnes the result.
     * @param string $sql SQL query to execute.
     * @param array $params Params to be passed with the query.
     * @param bool $onlyExec If the results should be fetched, or only the query executed.
     * @return MySqlResult 
     */
    final public function load($sql, $params = array(), $onlyExec = false){	
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
    
    /**
     * Shortcut method for deleting rows by id (so it doesn't have to be added to each model).
     * @param string $id Id of the row to be deleted.
     * @return bool Returns TRUE on success or FALSE on failure.
     */
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
       
    /**
     * Shortcut method for getting rows by id (so it doesn't have to be added to each model).
     * @param string $id Id of the rows to be returned.
     * @return MySqlResult Result. 
     */
    public function getById($id){
	$sql = "SELECT * FROM ".$this->_dbTable." WHERE ".$this->_idField." = :id";
	return $this->load($sql, array(":id" => $id));
    }
    
    /**
     * Shortcut method for getting all rows in table (so it doesn't have to be added to each model).
     * Results can be returned by "pages".
     * @param int $page Number of the page to return. If 0 all rows will be returned.
     * @param int $perPage Number of the rows per page to return. Only if $page != 0.
     * @return MySqlResult Results. 
     */
    public function getAll($page = 0, $perPage = 20){
	$sql = "SELECT * FROM ".$this->_dbTable;
	if($page > 0){
	    $offset = $page*$perPage-$perPage;
	    $sql .= " LIMIT $perPage OFFSET $offset";
	}
	return $this->load($sql);
    }
    
    /**
     * Encrypts data in model if encryption is set.
     * If encryption is off, this will simply return the original data.
     * @param mixed $data Data to be encrypted.
     * @param string $iv IV for encryption.
     * @return mixed Encrypted data.
     */
    public function crypt($data, $iv = false){ 
        if($this->_crypted){
	    return parent::crypt($data, md5($this->_dbTable));
        }
        return $data;
    }
    
    
    /**
     * Decrypts data in model if encryption is set.
     * If encryption is off, this will simply return the original data.
     * @param mixed $data Data to be decrypted.
     * @param string $iv IV for encryption.
     * @return mixed Decrypted data.  
     */
    public function decrypt($data, $iv = false){
        if($this->_crypted){
            return parent::decrypt($data, md5($this->_dbTable));
        }
        return $data;
    }
}

/**
 * MySQL result object. Can be used to access and manipulate database data.
 * @package frameworkCore
 * @subpackage databaseResults
 */
class MySqlResult extends databaseResult {
    
    /**
     * The result.
     * @var mixed
     */
    private $_result;
    /**
     * Id field used in results, got from the parent model.
     * @var string 
     */
    private $_id;
    /**
     * Current position for the iterator.
     * @var int
     */
    private $_position;
    /**
     * Parent model object.
     * @var appDatabase 
     */
    private $_parentModel;
    
    /**
     * Fills appropriate information about the result.
     * @param mixed $data Result data.
     * @param appDatabase $parentModel Parent model object.
     */
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
    
    /**
     * Returns given field from the result. 
     * If $field is not passed, whole original result will be returned - note it will NOT be decrypted.
     * @param string $field Name of the field.
     * @return mixed Value for the field. 
     */
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
    
    /**
     * Adds related data to given object, so it can be accessed later by {@link get()}.
     * @param string $name Name of the relation.
     * @param mixed $data Data to be stored.
     */
    public function addRelation($name, $data){
	if(!$this->multiple){
	    $this->_relation[$name] = $data; 
	}
    }
    
    /**
     * Sets given key in the result to given value.
     * @param string $field Name of the field.
     * @param mixed $value Value for which it should be set.
     * @return MySqlResult 
     */
    public function set($field, $value){
	$this->_result[$this->_position][$field] = $value;
	return $this; //to allow chaining ->save() after this method
    }
    
    /**
     * Saves the current result to the database.
     * @param bool $complete If the result is multiple, whether only the current result should be saved or whole cursor.
     * @return bool Whether the save was successfull. Save will abort after the first error.
     * @uses saveOne() to save each result.
     */
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
    
    /**
     * Saves the given row if id field exists in it.
     * @param array $row Row to be saved.
     * @return bool Whether the save was successfull.
     */
    private function saveOne($row){
	$keys = array();
	$values = array();
	foreach($row as $key => $value){
	    $keys[] = "$key = :$key";
	    $values[":".$key] = $value;
	}
	if(empty($keys)) return false;
	
	$sql = "UPDATE ".$this->_parentModel->getTableName()." SET ".implode(", ", $keys)." WHERE ".$this->_id." = :".$this->_id;
	return $this->_parentModel->load($sql, $values, true);   
    }
    
    /**
     * Rewinds the cursor.
     */
    public function rewind() {
        $this->_position = 0;
    }

    /**
     * Returns the result.
     * @return mixed 
     */
    public function current() {
        return $this->_result[$this->_position];
    }

    /**
     * Returns current interator key.
     * @return int
     */
    public function key() {
        return $this->_position;
    }

    /**
     * Moves the cursor to the next result.
     */
    public function next() {
        ++$this->_position;
    }

    /**
     * Tells whether the current position in cursor is valid.
     * @return bool
     */
    public function valid() {
        return isset($this->_result[$this->_position]);
    }
    
}