<?php
/**
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 */
class databaseMongoDB extends appDatabase {
  
    /**
     * Instance of the connection.
     * Implements singleton.
     * @var MongoDb 
     */
    private static $_instance; 
    
    /**
     * Database name to use - should be set for each model.
     * @var string
     */
    protected $_dbName;
    
    /**
     * Database collection to use - should be set for each model.
     * @var string 
     */
    protected $_dbTable;
    
    public $_idField = "_id";
    
    protected $_collection;
    
    public $_cache = false;
    public $_cacheKey = "";
    protected $_crypted = false;
    
    public function __construct() {
        $this->check();
	$this->_cacheKey = $this->_dbName.$this->_dbTable;
    }

    /**
     * Creates an instance of the connection (if needed) or returns existing one.
     * Can create 503 error when PDO returns exception or 500 when connection data is missing.
     * @return Mongo Connection instance 
     */
    private static function getInstance(){ 
        if(!self::$_instance){ 
            $socket = appConfig::get("mongo_db_socket");
            
            if(!$socket){
                $host = appConfig::get("mongo_db_host");
                $port = appConfig::get("mongo_db_port");
                
                if($host === false){
                    appCore::sthrowError(500, "No db host provided!");
                }
                if($port === false){
                    $port = 27017;
                }
                $socket = "mongodb://$host:$port";
            }
            
            $user = appConfig::get("mongo_db_user");
            $pass = appConfig::get("mongo_db_pass");
            $options = array();
            if($user && $pass){
                $options = array(
                    "username" => $user,
                    "password" => $pass
                );
            }      
	    	    
	    try{
		self::$_instance = new Mongo($socket, $options);
	    }
	    catch(MongoConnectionException $e){
		appCore::sthrowError(503, 'Could not connect: '.$e->getMessage());
	    }
        } 

        return self::$_instance; 
    }
        
    private function check(){
	if(!$this->_dbName){
	    $this->throwError(500,"Model ".get_class($this)." does not have a database name set!");
	}
	if(!$this->_dbTable){
	    $this->throwError(500,"Model ".get_class($this)." does not have a collection name set!");
	}
    }
    
    public function getCollection(){
	return $this->_collection;
    }
    
    private function connectToCollection($dbOnly = false){
	//get db
	try {$db = self::getInstance()->selectDB($this->_dbName);} catch(InvalidArgumentException $e){ appCore::sthrowError(503, 'Could not connect: '.$e->getMessage());}
	if($dbOnly) return $db;

	//get collection
	$this->_collection = $db->selectCollection($this->_dbTable);
	try{$error = $db->lastError();} catch(MongoException $e){appCore::sthrowError(503, 'Could not get last error: '.$e->getMessage());}
	if(!$error['ok']) appCore::sthrowError(503, 'Error while getting collection: '.$this->_dbTable.": ".var_export($error, true));
    }
    
    final public function load($query, $fetchAll = false, $fields = false){
	if($this->_cache && !$fetchAll){
	    $cacheKey = sha1(serialize($query).serialize($fields));
	    $cachedVal = $this->cache()->get($cacheKey, $this->_cacheKey);
	    if($cachedVal) return $cachedVal;
	}

        $result = array();
	$this->connectToCollection();
	
	if($fetchAll){
	    if($fields) try{$result = $this->_collection->find($query, $fields);} catch(MongoConnectionException $e){ appCore::sthrowError(503, 'Error while query: '.$e->getMessage()); }
	    else try{$result = $this->_collection->find($query);} catch(MongoConnectionException $e){ appCore::sthrowError(503, 'Error while query: '.$e->getMessage()); }
	}
	else{
	    if($fields) try{$result = $this->_collection->findOne($query, $fields);} catch(MongoConnectionException $e){ appCore::sthrowError(503, 'Error while query: '.$e->getMessage()); }
	    else try{$result = $this->_collection->findOne($query);} catch(MongoConnectionException $e){ appCore::sthrowError(503, 'Error while query: '.$e->getMessage()); }
	    
	}
	if($result){ 
	    $mres = new MongoDBResult($result, $this);
	    if($this->_cache && !$fetchAll){ 
		$this->cache()->set($cacheKey, $mres, $this->_cacheKey);
	    }
	    return $mres;
	}
	return false;
    }
    
    final public function insert($row, $safe = true){
	$this->connectToCollection();
	
	try{
	    $insert = $this->_collection->insert($row, array("safe" => $safe));
	    if($this->_cache){
		$this->cache()->clear($this->_cacheKey);
	    }
	    return $insert;
	}
	catch(MongoCursorException $e){
	     appCore::sthrowError(503, 'Could not insert with "safe": '.$e->getMessage());
	}
	catch(MongoCursorTimeoutException $e){
	     appCore::sthrowError(503, 'Could not insert - timeout: '.$e->getMessage());
	}
    }
    
    final public function update($id, $data, $safe = true){
        $this->connectToCollection();
        
        try{
	    $update = $this->_collection->update(array($this->_idField => new MongoId($id)), $data, array("safe" => $safe));
	    if($this->_cache){
		$this->cache()->clear($this->_cacheKey);
	    }
	    return $update;
	}
	catch(MongoCursorException $e){
	     appCore::sthrowError(503, 'Could not update with "safe": '.$e->getMessage());
	}
	catch(MongoCursorTimeoutException $e){
	     appCore::sthrowError(503, 'Could not update - timeout: '.$e->getMessage());
	}
    }
    
    final public function command($command){
	$db = $this->connectToCollection(true);
	try {
            $com = $db->command($command);
        }
        catch(MongoException $e){
            $this->throwError(500, "Cannot execute command: ".$e->getMessage());
        }
        return $com;
    }
    
    public function save($row, $safe = true){
        try {
	    $safe = $this->getCollection()->save($row, array("safe" => $safe));
	    if($this->_cache){
		$this->cache()->clear($this->_cacheKey);
	    }
	    return $safe;
	}
	catch(MongoCursorException $e){
	    $this->throwError(500, "Couldn't save row to db: ".$e->getMessage());
	}
	catch(MongoCursorTimeoutException $e){
	    $this->throwError(500, "Timeout when saving row to db: ".$e->getMessage());
	} 
    }
              
    public function deleteById($id){
	$this->connectToCollection();
	try {
	    $safe = $this->getCollection()->remove(array($this->_idField => new MongoId($id)), array("justOne" => true,"safe" => true));
	    if($this->_cache){
		$this->cache()->clear($this->_cacheKey);
	    }
	    return $safe;
	}
	catch(MongoCursorException $e){
	    $this->throwError(500, "Couldn't remove row $id: ".$e->getMessage());
	}
	catch(MongoCursorTimeoutException $e){
	    $this->throwError(500, "Timeout when removing row $id: ".$e->getMessage());
	} 
    }
       
    public function getById($id){
	$query = array($this->_idField => new MongoId($id));
	return $this->load($query);
    }
    
    public function getAll($page = 0, $perPage = 20){
	$result = $this->load(array(), true);
	if($page > 0){
	    $result->page($page, $perPage);
	}
	return $result;
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

class MongoDBResult extends databaseResult {
    
    private $_cursor;
    private $_idField;
    private $_position;
    private $_parentModel;
    private $_safe = false;
    private $_relation;
    
    public function __construct($data, $parentModel) {
       $this->_parentModel = $parentModel;
       $this->_position = 0;
       $this->_idField = $parentModel->_idField;
	
       if($data instanceof MongoCursor){
	   $this->multiple = true;
	   $this->_cursor = $data;
	   $this->count = $data->count();
       }
       elseif(is_array($data) && isset($data[$this->_idField])){
	   $this->_result = $data;
	   $this->multiple = false;
	   $this->count = 1;
       }
       else{
	   $this->multiple = false;
	   $this->_result = array();
       }
    }
    
    public function get($field = false){
	if(!$field){
	    if($this->multiple) return $this->_cursor;
	    return $this->_result;
	}
	if($field == "id") $field = $this->_idField;
	
	if($this->multiple){
	    $r = $this->_cursor->current();
	    if(isset($r[$field])){
                if($field == $this->_idField){
                    return $r[$field];
                }
                return $this->_parentModel->decrypt($r[$field]); //return data to the user - if any
            }
	}
	else{
	    if(isset($this->_result[$field])){
                if($field == $this->_idField){
                    return $this->_result[$field];
                }
		return $this->_parentModel->decrypt($this->_result[$field]);
	    }
	    $relationName = explode(".", $field);
	    if(count($relationName) == 2 && isset($this->_relation[$relationName[0]])){
		return $this->_relation[$relationName[0]]->get($relationName[1]);
	    }
	}
	return;
    }
    
    public function getCurrent(){
	if($this->multiple) return $this->_cursor->current();
	return $this->_result;
    }
    
    public function set($field, $value){
	if($this->multiple){
	    $r = $this->_cursor->current();
	    if($r){
		if(!isset($r[$this->_idField])) $this->throwError(500, "Can't set changes to current object, there's not id!");
		
                if(!isset($this->_result[$this->_position])){
                    $this->_result[$this->_position] = $r;
                }
		$this->_result[$this->_position][$field] = $this->_parentModel->crypt($this->createProperValue($value));
	    }else{
		$this->throwError(500, "Can't set changes to current object, cursor has ended!");
	    }
	}
	else{
	    $this->_result[$field] = $this->_parentModel->crypt($this->createProperValue($value));
	}
	return $this; //to allow chaining ->save() after this method
    }
    
    private function createProperValue($value){
	if(is_string($value) && ($t = strtotime($value)) && (date(appConfig::get('date_format'), $t) == $value || date(appConfig::get('date_hour_format'), $t) == $value)){
	    return new MongoDate($t);
	}
	return $value;
    }
    
    public function save($complete = false){
	if($this->multiple){
	    if($complete){
		foreach($this->_result as $values){
		    $f = $this->saveOne($values);
		    if(!$f){
			return false;
		    }
		}
	    }
	    else{
		if(!isset($this->_result[$this->_position])) $this->throwError(500, "Can't save the current object, pointer too big!");
		return $this->saveOne($this->_result[$this->_position]);
	    }
	}
	else{
	    return $this->saveOne($this->_result);
	}
    }
    
    private function saveOne($row){
	if(!isset($row[$this->_idField])) $this->throwError(500, "Can't save the current row, no id field!");
	
        return $this->_parentModel->save($row, $this->_safe);
    }
    
    public function rewind() {
        $this->_position = 0;
	if($this->multiple){
	    try {
		$this->_cursor->rewind();
	    }
	    catch(MongoConnectionException $e){
		$this->throwError(500, "Can't rewind cursor - can't reach db: ".$e->getMessage());
	    }
	    catch(MongoCursorTimeoutException $e){
		$this->throwError(500, "Can't rewind cursor - timeout: ".$e->getMessage());
	    }
	}
    }

    public function current() {
	if($this->multiple) return $this;
        return $this->_result;
    }

    public function key() {
        return $this->_position;
    }

    public function next() {
        ++$this->_position;
	if($this->multiple){
	    try {
		$this->_cursor->next();
	    }
	    catch(MongoConnectionException $e){
		$this->throwError(500, "Can't get next cursor element - can't reach db: ".$e->getMessage());
	    }
	    catch(MongoCursorTimeoutException $e){
		$this->throwError(500, "Can't get next cursor element - timeout: ".$e->getMessage());
	    }
	}
    }

    public function valid() {
	if($this->multiple) return $this->_cursor->valid();
        if($this->_position > 0) return false; //one element
    }
    
    public function safe($is){
	$this->_safe = (bool)$is;
	return $this; //to allow chaining save()
    }
    
    public function page($page, $perPage){
	if($this->multiple && $this->_position == 0){
	    $this->_cursor->skip($page*$perPage-$perPage)->limit($perPage);
	    return $this;
	}
	return false;
    }
    
    public function sort($by){
	if($this->multiple && $this->_position == 0){
	    $this->_cursor->sort($by);
	    return $this;
	}
	return false;
    }
    
    public function addRelation($name, $data){
	if(!$this->multiple){
	    $this->_relation[$name] = $data; 
	}
    }
    
    public function count($element = false){
        if(!$element) return $this->count;
	//or count interal elements
	$element = explode(".", $element); 
        if(count($element) == 2){
            $e = $this->get($element[0]);
            $c = 0;
            foreach($e as $ee){
                if($ee[$element[1]]) $c++;
            }
            return $c;
        }
        return $this->count;
    }
}