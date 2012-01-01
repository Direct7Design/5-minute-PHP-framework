<?php
/**
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * 
 * INSERT INTO `users` (`login`, `password`) VALUES ('Y/TP1oRLTyaTtLJLKSvIHYxMOZTSxz0NWMAwBn6FKpA=', 'Y/TP1oRLTyaTtLJLKSvIHYxMOZTSxz0NWMAwBn6FKpA=');
 */
class usersModel extends databaseMysql {
  
    protected $_dbName = "test";
    protected $_dbTable = "users";
    protected $_crypted = true;

    public function getUser($login, $password){
	$sql = "SELECT * FROM ".$this->_dbTable." WHERE login = :login AND password = :password";
	$params = array(':login' => $this->crypt($login), ':password' => $this->crypt($password));
        return $this->load($sql, $params);
    }
        
    public function add($login, $password){
	$data = array(
	    "login" => $this->crypt($login),
	    "password" => $this->crypt($password)
	);
	return $this->insert($data);
    }
   
}