<?php
/**
 * User management. 
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 */
class appUser extends appCore{
    
    /**
     * Name of the session key used to store user's information.
     * @var string
     */
    private $_name = "logged_member";
   
    /**
     * Takes care of all the wrong data that my appear.
     */
    public function __construct() {
	if(!$this->properUser()){
	    $this->logout();
	}
	return $this;
    }
    
    /**
     * Tells wheter or not a proper user is logged in.
     * @return bool 
     */
    public function properUser(){
	if(isset($_SESSION[$this->_name]['id'])){                             
	    return true;
	}
	return false;
    }
    
    /**
     * Returns id of the current admin or false if not a proper admin.
     * @return int or false. 
     */
    public function getUser(){
	if($this->properUser()){
	    return $_SESSION[$this->_name]['id'];
	}
	return false;
    }
    
    /**
     * Logins the user with given username and password. 
     * @param string $user User's name
     * @param string $password User's password.
     * @return bool Whether or not such user exists and was logged in.  
     */
    public function login($user, $password){
	$usersModel = $this->db()->getModel("users");
	if(($data = $usersModel->getUser($user, $password))){
	    $_SESSION[$this->_name]['id'] = $data->get("id");
            $user_data = array(
                "login" => $data->get("login"),
            );
	    $_SESSION[$this->_name]['data'] = $user_data;
	    return true;
	}
	return false;
    }
    
    /**
     * Logouts the current user.
     */
    public function logout(){
	$_SESSION[$this->_name] = NULL;
    }
    
    /**
     * Returns specific information about current user.
     * @param string $what Name of the data to be returned, for example "login".
     * @return string Found string or false if no such key exists.  
     */
    public function get($what){
	if(isset($_SESSION[$this->_name][$what])){
	    return $_SESSION[$this->_name][$what];
	}
	if(isset($_SESSION[$this->_name]['data'][$what])){
	    return $_SESSION[$this->_name]['data'][$what];
	}
	return false;
    }
    
    /**
     * Refreshes user's info saved in session. 
     * This should be called whenever any changes to the user's record in the db are done.
     * @return bool Whether or not refresh was successful. 
     */
    public function refresh(){return;
	if($this->properUser()){
	    $usersModel = $this->db()->getModel("users");
	    $data = $usersModel->getById($_SESSION[$this->_name]['id']);
	    if($data){
		$_SESSION[$this->_name]['id'] = $data->get("id");
                $user_data = array(
                    "login" => $data->get("login"),
                );
		$_SESSION[$this->_name]['data'] = $user_data;
		return true;
	    }
	    else{
		$this->logout();
	    }
	}
	else{
	    $this->logout();
	}
	return false;
    }
   
}