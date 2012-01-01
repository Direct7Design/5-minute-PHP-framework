<?php
/**
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 */

class appPocket {
   
    
    /**
     *
     * @var appCookie 
     */
    private static $_cookies;
    /**
     *
     * @var appView 
     */
    private static $_view;
    private static $_request;
    private static $_db;
    private static $_user;
    private static $_cache;
       
    public static function request(){
	if(!self::$_request){
	    self::$_request = new appRequest();
	}
	return self::$_request;
    }
    
    public static function db(){
	if(!self::$_db){
	    self::$_db = new appDatabase();
	}
	return self::$_db;
    }
    
    public static function user(){
	if(!self::$_user){
	    self::$_user = new appUser();
	}
	return self::$_user;
    }
    
    public static function view(){
	if(!self::$_view){
	    self::$_view = new appView();
	}
	return self::$_view;
    }
    
    public static function cookie(){
	if(!self::$_cookies){
	    self::$_cookies = new appCookie();
	}
	return self::$_cookies;
    }
    
    public static function cache(){
	if(!self::$_cache){
	    self::$_cache = new appCache();
	}
	return self::$_cache;
    }

}
