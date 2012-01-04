<?php
/**
 * Core file of the framework.
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 */

/**
 * Include required files.
 */
require_once 'functions.php';
require_once 'controllers/main.php';

/**
 * Main class taking care of routing and errors.
 * @package frameworkCore
 */
class appCore {
    
    /**
     * List of possible http messages.
     * @var array 
     */
    private static $_messages = array(
        //Informational 1xx
        100 => '100 Continue',
        101 => '101 Switching Protocols',
        //Successful 2xx
        200 => '200 OK',
        201 => '201 Created',
        202 => '202 Accepted',
        203 => '203 Non-Authoritative Information',
        204 => '204 No Content',
        205 => '205 Reset Content',
        206 => '206 Partial Content',
        //Redirection 3xx
        300 => '300 Multiple Choices',
        301 => '301 Moved Permanently',
        302 => '302 Found',
        303 => '303 See Other',
        304 => '304 Not Modified',
        305 => '305 Use Proxy',
        306 => '306 (Unused)',
        307 => '307 Temporary Redirect',
        //Client Error 4xx
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        402 => '402 Payment Required',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        406 => '406 Not Acceptable',
        407 => '407 Proxy Authentication Required',
        408 => '408 Request Timeout',
        409 => '409 Conflict',
        410 => '410 Gone',
        411 => '411 Length Required',
        412 => '412 Precondition Failed',
        413 => '413 Request Entity Too Large',
        414 => '414 Request-URI Too Long',
        415 => '415 Unsupported Media Type',
        416 => '416 Requested Range Not Satisfiable',
        417 => '417 Expectation Failed',
        //Server Error 5xx
        500 => '500 Internal Server Error',
        501 => '501 Not Implemented',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable',
        504 => '504 Gateway Timeout',
        505 => '505 HTTP Version Not Supported'
    );
       
    /**
     * View object. This should be accessed by {@link view()}.
     * @var appView 
     */
    protected $_view;
    
    /**
     * Cookie object. This should be accessed by {@link cookies()}.
     * @var appCookie 
     */
    protected $_cookies;
    
    /**
     * First and only method that should be called to start the framework.
     * It handles all initializations, creates basic objects, starts sessions, etc.
     * @param array $config Config information that should be used. 
     */
    public function start($config){
	set_include_path(get_include_path().PATH_SEPARATOR.__DIR__."/controllers/".PATH_SEPARATOR.__DIR__."/helpers/".PATH_SEPARATOR.__DIR__."/lib/");
	spl_autoload_register(array($this, "loadClass"));
	
	appConfig::set($config);
	ini_set("short_open_tag", "1");
        
        if(appConfig::get("debug")){
            error_reporting(E_ALL|E_STRICT);
            ini_set('display_errors','On');
        }
        
        if(!appConfig::get("absolute_url")){
            $this->throwError(500, "absolute_url config setting is not defined!");
        }
        if(!appConfig::get("relative_url")){
            $this->throwError(500, "relative_url config setting is not defined!");
        }
        
	$this->_view = appPocket::view();
	$this->_cookies = appPocket::cookie();
	
	session_name(appConfig::get("cookie_name"));
        if(session_id() === ''){
            session_start();
        }
	
	$this->dispatch();
	$this->end();
    }
    
    /**
     * This is called by {@link start()} to handle routing.
     * Handles request uri and calls appropriate controller as needed.
     */
    private function dispatch(){
	$relUrl = appConfig::get("relative_url");
	$requestUri = $_SERVER['REQUEST_URI'];
	if($relUrl == "" || $relUrl == "/"){
	    if(strpos($_SERVER['REQUEST_URI'], "/") === 0){
		$requestUri = substr($requestUri, 1);
	    }
	}else{
	    $requestUri = str_replace(appConfig::get("relative_url"), "", $_SERVER['REQUEST_URI']);
	}
	
        $request = explode("/", $requestUri);
	foreach($request as &$val){ //clear all params
	    $val = $this->cleanArgument($val);
	}

	$controllerName = false;
	$method = "index"; //default method
	$params = array();
	
	if(count($request) == 1 && $request[0] == ""){ //dispatch empty calls
	    $controllerName = "app";
        }
        elseif(count($request) > 0){ //dispatch class with functions
	    $controllerName = $request[0];  //get controller name

            if(isset($request[1]) && !empty($request[1])){ //get method if in url
                $method = $request[1];
	    }
	    
	    unset($request[0]); //clean request table
	    unset($request[1]); //clean request table
	    $params = array_filter($request); //remove all empty stuff - should have only params left
        }	

	if($controllerName){
            //check ip restrictions
            $restrict = appConfig::get("restrict");
            if($restrict && isset($restrict[$controllerName])){
                if(!in_array($_SERVER['REMOTE_ADDR'], $restrict[$controllerName])){
                    $this->throw404("Access restricted");
                }
            }
            
            //go on
	    $controllerName .= "Controller";

	    $c = new $controllerName();
	    if(is_callable(array($c, $method))){ //try controller with method as given
		call_user_func_array(array($c, $method), $params); 
	    }else{	
                array_unshift($params, $method);
                call_user_func_array(array($c, "index"), $params); 
//		$this->throw404("Cannot call $controllerName -> $method - no such method!");
	    }
	}
	else{
	    $this->throw404("Call not recognized");
	}
    }
    
    /**
     * Helper method used by {@link dispatch()} to remove all unneeded information from request uri parts.
     * Mainly, it removes any additions from GET requests: changes "action?get_param1=1&_getparam2=2" to "action".
     * @param string $arg Part of the request uri to clean.
     * @return string Cleaned string. 
     */
    private function cleanArgument($arg){
	if(preg_match("/(?P<url>.*)(\?)+(.*)/", $arg, $matches)){
	    return strtolower($matches['url']);
	}
	return strtolower($arg);
    }
    
    /**
     * Loads appropriate file for given class, if such file exists.
     * Throws 404 error if not found. This is used to provide lazy-loading feature.
     * @param string $class Name of the class to load.
     */
    private function loadClass($class){
	if(stream_resolve_include_path($class.".php")){
	    require_once($class.".php");
	}
	else{
	    $this->throwError(404, "Cannot load class $class");
	}
    }
    
    
    /**
     * Shortcut for {@link self::sthrowError()} that can be called non-statically.
     */
    public function throwError($code, $message = false){
        self::sthrowError($code, $message);
    }
    
    /**
     * Throws given error and ends the request. If the appropriate error template exists it will be shown.
     * To use the template name the appropriate file in templates/ as "error_numberofError.php", for example "error_404.php".
     * If a debug mode is on ("debug" option in appConfig), then it will also include $message in the output.
     * @uses $messages to show error message.
     * @param type $code
     * @param type $message 
     */
    public static function sthrowError($code, $message = false){
	if (substr(PHP_SAPI, 0, 3) === 'cgi'){
            //Send Status header if running with fastcgi
	    header('Status: '.self::$_messages[$code]);  
        }else{
            //Else send HTTP message
            header('HTTP/1.1 '.self::$_messages[$code]);
        }
        
	$templateShown = false;
	if(stream_resolve_include_path("templates/error_$code.php")){
	    include "templates/error_$code.php";
	    $templateShown = true;
	}
	
        flush();
        session_write_close();
	
	if(appConfig::get("debug")){
	    echo self::$_messages[$code]." ".$message;
	    echo "<pre>";
	    debug_print_backtrace();
	    echo "</pre>";
	    
	    if($code >= 500) trigger_error($message, E_USER_ERROR);    
	}
	elseif(!$templateShown){
	    echo self::$_messages[$code];
	}
	
        die();
    }
    
    /**
     * Shortcut to {@link throwError(404)}.
     */
    public function throw404($message = false){
        $this->throwError(404, $message);
    }
    
    /**
     * Redirect's user to another location.
     * @uses throwError() to issue a "302" header.
     * @param string $url Url where the user should be redirected.
     */
    public function redirect($url = false){
	$redirect = appConfig::get("relative_url");
	if($url){
	    $url = trim($url);
	    if(strpos($url, "http") === 0){
		$redirect = $url;
	    }
	    else{
		$redirect .= $url;
	    }   
	}
	header("Location: $redirect");
	self::throwError(302);
    }
    
    /**
     * Called at the end by {@link start()} - sends cookies, renders the view and closes session.
     */
    private function end(){
	$this->_cookies->sendCookies();
        $this->_view->render();
        session_write_close();
    }
    
    /**
     * Lazy-loading of an appRequest class that may not be always needed.
     * Every controller should call this method to access {@link appRequest} class.
     * @return appRequest 
     */
    protected function request(){
	return appPocket::request();
    }
    
    /**
     * Lazy-loading of an appDatabase class that may not be always needed.
     * Every controller should call this method to access {@link appDatabase} class.
     * @return appDatabase 
     */
    protected function db(){
	return appPocket::db();
    }
    
    /**
     * Lazy-loading of an appUser class that may not be always needed.
     * Every controller should call this method to access {@link appUser} class.
     * @return appUser 
     */
    protected function user(){
	return appPocket::user();
    }
    
    /**
     * Lazy-loading of an appView class that may not be always needed.
     * Every controller should call this method to access {@link appView} class.
     * @return appView 
     */
    protected function view(){
	return appPocket::view();
    }
    
    /**
     * Lazy-loading of an appCookie class that may not be always needed.
     * Every controller should call this method to access {@link appCookie} class.
     * @return appCookie 
     */
    protected function cookies(){
	return appPocket::cookie();
    }
        
    /**
     * Lazy-loading of an appCache class that may not be always needed.
     * Every controller should call this method to access {@link appCache} class.
     * @return appCache 
     */
    protected function cache(){
	return appPocket::cache();
    }

}
