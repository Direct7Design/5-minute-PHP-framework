<?php
/**
 * This is an example model used with MongoDB. To use the example for user's login add the following data to your MongoDB:
 * 
 * db.users.insert({"login": "Y/TP1oRLTyaTtLJLKSvIHYxMOZTSxz0NWMAwBn6FKpA=", "password": "Y/TP1oRLTyaTtLJLKSvIHYxMOZTSxz0NWMAwBn6FKpA="}
 * 
 * To use this model, rename this file to "usersModel.php". 
 * 
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 * @subpackage databaseModels
 */
class usersModel extends databaseMongoDB {

    protected $_dbName = "test";
    protected $_dbTable = "users";
    protected $_crypted = true;

    /**
     * Gets user document (if exists) by login and password.
     * @param string $login Login (clear-text).
     * @param string $password Password (clear-text).
     * @return MongoDBResult 
     */
    public function getUser($login, $password){
	$query = array('login' => $this->crypt($login), 'password' => $this->crypt($password));
        return $this->load($query);
    }
        
    /**
     * Adds new user.
     * @param string $login Login (clear-text).
     * @param string $password Password (clear-text).
     * @return mixed Result returned by {@link insert()}. 
     */
    public function add($login, $password){
	$data = array(
	    "login" => $this->crypt($login),
	    "password" => $this->crypt($password)
	);
	return $this->insert($data);
    }
   
}