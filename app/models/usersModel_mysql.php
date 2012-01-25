<?php
/**
 * This is an example model used with MySQL. To use the example for user's login add the following data to your MySQL:
 * 
 * CREATE TABLE `users` (
 *  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 *  `login` varchar(50) DEFAULT NULL,
 *  `password` varchar(50) DEFAULT NULL,
 *  PRIMARY KEY (`id`)
 * )  DEFAULT CHARSET=utf8;
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
     * @return MySqlResult 
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
     * @return bool True on success, false on failure.
     */
    public function add($login, $password){
	$sql = "INSERT IGNORE INTO ".$this->_dbTable." (login, password) VALUES (:login, :password)";
	$params = array(':login' => $this->crypt($login), ':password' => $this->crypt($password));
	return $this->load($sql, $params, true);
    }
   
}