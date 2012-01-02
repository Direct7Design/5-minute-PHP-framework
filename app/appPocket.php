<?php
/**
 * Used for lazy-loading, keeps one copy of each common object, if needed.
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 */

class appPocket {

    /**
     * @var appCookie 
     */
    private static $_cookies;
    /**
     * @var appView 
     */
    private static $_view;
    /**
     * @var appRequest 
     */
    private static $_request;
    /**
     * @var appDatabase 
     */
    private static $_db;
    /**
     * @var appUser 
     */
    private static $_user;
    /**
     * @var appCache 
     */
    private static $_cache;
       
    /**
     * Returns appRequest object.
     * @return appRequest 
     */
    public static function request(){
	if(!self::$_request){
	    self::$_request = new appRequest();
	}
	return self::$_request;
    }
    
    /**
     * Returns appDatabase object.
     * @return appDatabase 
     */
    public static function db(){
	if(!self::$_db){
	    self::$_db = new appDatabase();
	}
	return self::$_db;
    }
    
    /**
     * Returns appUser object.
     * @return appUser 
     */
    public static function user(){
	if(!self::$_user){
	    self::$_user = new appUser();
	}
	return self::$_user;
    }
    
    /**
     * Returns appView object.
     * @return appView 
     */
    public static function view(){
	if(!self::$_view){
	    self::$_view = new appView();
	}
	return self::$_view;
    }
    
    /**
     * Returns appCookie object.
     * @return appCookie 
     */
    public static function cookie(){
	if(!self::$_cookies){
	    self::$_cookies = new appCookie();
	}
	return self::$_cookies;
    }
    
    /**
     * Returns appCache object.
     * @return appCache 
     */
    public static function cache(){
	if(!self::$_cache){
	    self::$_cache = new appCache();
	}
	return self::$_cache;
    }

}
