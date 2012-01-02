<?php
/**
 * This is an example model used with MySQL. To use the example for user's login add the following data to your MySQL:
 * 
 * INSERT INTO `users` (`login`, `password`) VALUES ('Y/TP1oRLTyaTtLJLKSvIHYxMOZTSxz0NWMAwBn6FKpA=', 'Y/TP1oRLTyaTtLJLKSvIHYxMOZTSxz0NWMAwBn6FKpA=');
 * 
 * To use this model, rename this file to "usersModel.php". 
 * 
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 * @subpackage databaseModels
 */
class usersModel extends databaseMysql {
  
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
	$sql = "SELECT * FROM ".$this->_dbTable." WHERE login = :login AND password = :password";
	$params = array(':login' => $this->crypt($login), ':password' => $this->crypt($password));
        return $this->load($sql, $params);
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