<?php
/**
 * Driver for MongoDB.
 * Almost every method in this class will throw 503 error on connection problems.
 * @uses PHP_MANUAL#Mongo to connect to the database.
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 * @subpackage databaseDrivers
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
    
    /**
     * Default id field name. 
     * Can be changed per model.
     * @var string
     */
    public $_idField = "_id";
    
    /**
     * Holds current collection object.
     * @var MongoCollection 
     */
    protected $_collection;
    
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
     * Checks if all data is in place for connection and sets {@link $_cacheKey}.
     */
    public function __construct() {
        $this->check();
	$this->_cacheKey = $this->_dbName.$this->_dbTable;
    }

    /**
     * Creates an instance of the connection (if needed) or returns existing one.
     * Can create 503 error when Mongo returns exception or 500 when connection data is missing.
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
        
    /**
     * Checks if all data for connection is set.
     */
    private function check(){
	if(!$this->_dbName){
	    $this->throwError(500,"Model ".get_class($this)." does not have a database name set!");
	}
	if(!$this->_dbTable){
	    $this->throwError(500,"Model ".get_class($this)." does not have a collection name set!");
	}
    }
    
    /**
     * Returns current collection, if connected.
     * @return MongoCollection 
     */
    public function getCollection(){
	return $this->_collection;
    }
    
    /**
     * Connects to appropriate collection. Can stop after connecting to the database, if that's needed.
     * Throws 503 errors on connection problems.
     * @param bool $dbOnly If true - it will only connect to the database and return MongoDB object.
     * @return MongoDB|NULL Returns MongoDB if $dbOnly = true or NULL if sucessfully connected to collection.
     */
    private function connectToCollection($dbOnly = false){
	//get db
	try {$db = self::getInstance()->selectDB($this->_dbName);} catch(InvalidArgumentException $e){ appCore::sthrowError(503, 'Could not connect: '.$e->getMessage());}
	if($dbOnly) return $db;

	//get collection
	$this->_collection = $db->selectCollection($this->_dbTable);
	try{$error = $db->lastError();} catch(MongoException $e){appCore::sthrowError(503, 'Could not get last error: '.$e->getMessage());}
	if(!$error['ok']) appCore::sthrowError(503, 'Error while getting collection: '.$this->_dbTable.": ".var_export($error, true));
    }
    
    /**
     * Loads given query. 
     * If caching is on: returns cached result if it exists or sets a new cache key.
     * Refer to given resource for information on how to create queries.
     * @param array $query Query to be run on collection.
     * @param bool $fetchAll Optional: Whether findOne() or find() should be run (do you need one or all the results?)
     * @param array $fields Optional list of fields that should be returned. By default the whole document is returned.
     * @return MongoDBResult
     * @see PHP_MANUAL#mongocollection.find.php
     */
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
    
    /**
     * Inserts given row to the collection.
     * @param array $row Row to be inserted.
     * @param bool $safe Whether the insert should be "safe".
     * @return mixed Returns value returned by {@link MongoCollection::insert()}.
     * @see PHP_MANUAL#mongocollection.insert.php
     */
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
    
    /**
     * Updates the given document with given data.
     * @param string $id Id of the document to be updated.
     * @param array $data Update query.
     * @param bool $safe Whether the update should be "safe".
     * @return mixed Returns value returned by {@link MongoCollection::update()}. 
     * @see PHP_MANUAL#mongocollection.update.php
     */
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
    
    /**
     * Executes given command on the database (not collection!).
     * @param array $command Command to be executed.
     * @return mixed Returns value returned by {@link MongoDB::execute()}.  
     * @see PHP_MANUAL#mongodb.execute.php
     */
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
    
    /**
     * Saves given row.
     * @param array $row Row to be saved.
     * @param bool $safe Whether the save should be "safe".
     * @return mixed Returns value returned by {@link MongoCollection::save()}. 
     * @see PHP_MANUAL#mongocollection.save.php
     */
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
      
    /**
     * Shortcut method for deleting documents by id (so it doesn't have to be added to each model).
     * @param string $id Id of the document to be deleted.
     * @return mixed Returns value returned by {@link MongoCollection::remove()}. 
     * @see PHP_MANUAL#mongocollection.remove.php 
     */
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
       
    /**
     * Shortcut method for getting documents by id (so it doesn't have to be added to each model).
     * @param string $id Id of the document to be returned.
     * @return MongoDBResult Result. 
     */
    public function getById($id){
	$query = array($this->_idField => new MongoId($id));
	return $this->load($query);
    }
    
    /**
     * Shortcut method for getting all documents in collection (so it doesn't have to be added to each model).
     * Results can be returned by "pages".
     * @param int $page Number of the page to return. If 0 all documents will be returned.
     * @param int $perPage Number of the documents per page to return. Only if $page != 0.
     * @return MongoDBResult Results. 
     */
    public function getAll($page = 0, $perPage = 20){
	$result = $this->load(array(), true);
	if($page > 0){
	    $result->page($page, $perPage);
	}
	return $result;
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
 * MongoDB result object. Can be used to access and manipulate database data.
 * @package frameworkCore
 * @subpackage databaseResults
 */
class MongoDBResult extends databaseResult {
    
    /**
     * If result is more than one document, this will hold the cursor.
     * @var MongoCursor 
     */
    private $_cursor;
    /**
     * Id field used in results, got from the parent model.
     * @var string 
     */
    private $_idField;
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
     * Whether the data should be saved with "safe".
     * @var bool
     */
    private $_safe = false;
    /**
     * Holds relations information.
     * @var mixed
     */
    private $_relation;
    
    /**
     * Fills appropriate information about the result.
     * @param mixed $data MongoCursor or array with the results.
     * @param appDatabase $parentModel Parent model object.
     */
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
    
    /**
     * Returns given field from the result. 
     * If $field is not passed, whole original result will be returned - note it will NOT be decrypted.
     * @param string $field Name of the field.
     * @return mixed Value for the field. 
     */
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
    
    /**
     * Returns current result from the iteration.
     * @return mixed  
     */
    public function getCurrent(){
	if($this->multiple) return $this->_cursor->current();
	return $this->_result;
    }
    
    /**
     * Sets given key in the result to given value.
     * It will automatically convert any date-alike string to MongoDate.
     * @uses createProperValue() to convert date-alike strings.
     * @param string $field Name of the field.
     * @param mixed $value Value for which it should be set.
     * @return MongoDBResult 
     */
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
    
    /**
     * Converts any date-alike string to MongoDate.
     * @param string $value 
     * @return MongoDate 
     */
    private function createProperValue($value){
	if(is_string($value) && ($t = strtotime($value)) && (date(appConfig::get('date_format'), $t) == $value || date(appConfig::get('date_hour_format'), $t) == $value)){
	    return new MongoDate($t);
	}
	return $value;
    }
    
    /**
     * Saves the current result to the database.
     * @param bool $complete If the result is multiple, whether only the current result should be saved or whole cursor.
     * @return mixed Result from {@link databaseMongoDB::save()}. 
     * @uses saveOne() to save each result.
     */
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
    
    /**
     * Saves the given row if id field exists in it.
     * Will throw 500 error if id field is not supplied.
     * @param array $row Row to be saved.
     * @return mixed Result from {@link databaseMongoDB::save()}.  
     */
    private function saveOne($row){
	if(!isset($row[$this->_idField])) $this->throwError(500, "Can't save the current row, no id field!");
	
        return $this->_parentModel->save($row, $this->_safe);
    }
    
    /**
     * Rewinds the cursor.
     */
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

    /**
     * Returns the result.
     * @return MongoDBResult 
     */
    public function current() {
	if($this->multiple) return $this;
        return $this->_result;
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

    /**
     * Tells whether the current position in cursor is valid.
     * @return bool
     */
    public function valid() {
	if($this->multiple) return $this->_cursor->valid();
        if($this->_position > 0) return false; //one element
    }
    
    /**
     * Sets whether {@link save()} should be "safe".
     * @param bool $is True or false.
     * @return MongoDBResult 
     */
    public function safe($is){
	$this->_safe = (bool)$is;
	return $this; //to allow chaining save()
    }
    
    /**
     * Moves the cursor to given "page".
     * @param int $page Page to move to.
     * @param int $perPage Results per page.
     * @return MongoDBResult 
     */
    public function page($page, $perPage){
	if($this->multiple && $this->_position == 0){
	    $this->_cursor->skip($page*$perPage-$perPage)->limit($perPage);
	    return $this;
	}
	return false;
    }
    
    /**
     * Sorts results by given key.
     * @param string $by Key to sort by.
     * @return MongoDBResult 
     */
    public function sort($by){
	if($this->multiple && $this->_position == 0){
	    $this->_cursor->sort($by);
	    return $this;
	}
	return false;
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
     * Returns number of results returned or counts number of internal elements in document.
     * @param string $element Name of the element that should be counted.
     * @return int  
     */
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