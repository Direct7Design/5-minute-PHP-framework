<?php
/**
 * This is a class taking care of all connecions and queries done to databases.
 * It extends appCore to get access to throwError() method.
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 */
class appDatabase extends appCore {
    
    public function __construct() {
	set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__."/models/");
    }
    
    public function getModel($modelName){
	$modelName .= "Model";
	if(class_exists($modelName)){
	    return new $modelName();
	}
	else{
	    $this->throwError(500, "No such model: $modelName");
	}
    }
    
    public function crypt($data, $iv = false){ 
	if(!$iv) $iv = md5(time());
	return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, appConfig::get("crypt_std_key"), $data, MCRYPT_MODE_CBC, $iv));
    }
    
    public function decrypt($data, $iv = false){
        if(is_string($data)){
	    if(!$iv) $iv = md5(time());
            $data = base64_decode($data);
            return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256,appConfig::get("crypt_std_key"), $data, MCRYPT_MODE_CBC, $iv));
        }
        return $data;
    }
}

abstract class databaseResult extends appDatabase implements Countable, Iterator {
   
    public $multiple;
    public $count;
    
    abstract public function get($field = false);
    
    abstract public function set($field, $value);
    
    abstract public function save($complete = false);
    
    public function count(){
	return $this->count;
    }
}