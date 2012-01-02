<?php
/**
 * This file contains major classes responsible for database handling.
 * @package frameworkCore
 */

/**
 * Takes care of all connecions and queries done to databases.
 * It extends appCore to get access to throwError() method.
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 */
class appDatabase extends appCore {
    
    /**
     * Adds "models/" directory to include path.
     */
    public function __construct() {
	set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__."/models/");
    }
    
    /**
     * Returns appropriate model object. This should be used to access each model.
     * @param string $modelName Name of the model object (without "Model" suffix).
     * @return appDatabase Appropriate model object, {@link databaseMongoDB} or {@link databaseMysql}.
     */
    public function getModel($modelName){
	$modelName .= "Model";
	if(class_exists($modelName)){
	    return new $modelName();
	}
	else{
	    $this->throwError(500, "No such model: $modelName");
	}
    }
    
    /**
     * Common method used by database drivers to encrypt the data.
     * @param string $data Data to be encrypted.
     * @param string $iv IV for encryption.
     * @return string Encrypted data. 
     */
    public function crypt($data, $iv = false){ 
	if(!$iv) $iv = md5(time());
	return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, appConfig::get("crypt_std_key"), $data, MCRYPT_MODE_CBC, $iv));
    }
    
    /**
     * Common method used by database drivers to decrypt the data.
     * @param string $data Data to be decrypted.
     * @param string $iv IV for encryption.
     * @return string Decrypted data. 
     */
    public function decrypt($data, $iv = false){
        if(is_string($data)){
	    if(!$iv) $iv = md5(time());
            $data = base64_decode($data);
            return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256,appConfig::get("crypt_std_key"), $data, MCRYPT_MODE_CBC, $iv));
        }
        return $data;
    }
}

/**
 * Abstract class for each database driver.
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 * @subpackage databaseDrivers
 */
abstract class databaseResult extends appDatabase implements Countable, Iterator {
   
    /**
     * Whether or not the result is one or more rows.
     * @var bool
     */
    public $multiple;
    /**
     * Number of the results returned. Will be always 1 is {@link $multiple} = false.
     * @var int 
     */
    public $count;
    
    /**
     * Returns given field from the result.
     */
    abstract public function get($field = false);
    
    /**
     * Sets given key in the result to given value.
     * Usable with {@link save()}.
     */
    abstract public function set($field, $value);
    
    /**
     * Saves the current result to the database.
     * Handy when some data was changed with {@link set()}.
     */
    abstract public function save($complete = false);
    
    /**
     * Returns number of the returned.
     * @return int 
     */
    public function count(){
	return $this->count;
    }
}