<?php
/**
 * This is a class taking care of handling cookies.
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 */
class appCookie {
    
    /**
     * Name of the main cookie holding the session id.
     * This is set to config setting called "cookie_name".
     * @var string
     */
    private $_name;
    
    /**
     * Cookies to be send.
     * @var array 
     */
    private $_cookies = array();
    
    public function __construct() {
	$this->_name = appConfig::get("cookie_name");
    }
    
    /**
     * Returns given cookie value or main cookie value if $id is empty.
     * @param string $id Cookie key to look for (if any).
     * @return string Cookie value or false if not found. 
     */
    public function getCookie($id = false){ 
        if($id){
            if(isset($_COOKIE[$id])){
                return $_COOKIE[$id];
            }
        }
        elseif(isset($_COOKIE[$this->_name])){
            return $_COOKIE[$this->_name];
        }
        return false;
    }
    
    /**
     * Sets value to be set in the cookies.
     * This HAS TO be called BEFORE {@link sendCookies()} is called.
     * @param string $name Key name.
     * @param string $value Key value.
     */
    public function setCookie($name, $value){
        $this->_cookies[$name] = $value;
    }
    
    /**
     * Sends cookies to the user. 
     * Note: cookies are not crypted.
     */
    public function sendCookies(){
        foreach($this->_cookies as $cName => $cVal){
            setcookie($cName, $cVal, time()+94608000, "/", "", false, false);
        }
        flush();
    }
    
    /**
     * Sends info to the user that cookie should be deleted.
     * @param string $id Name of the cookie.
     */
    public function deleteCookie($id){
        if(isset($_COOKIE[$id])){
            setcookie($cName, false, time()-31000000, "/", "", false, false);
        }
    }
}