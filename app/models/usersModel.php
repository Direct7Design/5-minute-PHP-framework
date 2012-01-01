<?php
/**
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * 
 * db.users.insert({"login": "Y/TP1oRLTyaTtLJLKSvIHYxMOZTSxz0NWMAwBn6FKpA=", "password": "Y/TP1oRLTyaTtLJLKSvIHYxMOZTSxz0NWMAwBn6FKpA="}
 */
class usersModel extends databaseMongoDB {
  
    protected $_dbName = "test";
    protected $_dbTable = "users";
    protected $_crypted = true;

    public function getUser($login, $password){
	$query = array('login' => $this->crypt($login), 'password' => $this->crypt($password));
        return $this->load($query);
    }
        
    public function add($login, $password){
	$data = array(
	    "login" => $this->crypt($login),
	    "password" => $this->crypt($password)
	);
	return $this->insert($data);
    }
   
}