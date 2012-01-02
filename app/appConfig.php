<?php
/**
 * This is a class takes care of handling configuration options.
 * 
 * Refer to documentation of {@link $_defaults} variable for specific information on config variables.
 * You can specify your own config settings in index.php.
 * 
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 */
class appConfig {
    
    /**
     * Internal variable holding config options.
     * @var array 
     */
    private static $_config;
    
    /**
     * Default values needed by framework. 
     * Do not change this values, you can overwride all of them (and add more)
     * on framework startup (in index.php) on {@link appCore::start()}.
     * 
     * Description of the values:
     * 
     * * General settings *
     * - REQUIRED: absolute_url: absolute url to the site WITH the ending slash, for example: "http://example.com/mysite/"
     * - REQUIRED: relative_url: relative url to the site WITH the beginning and ending slash, for example: "/mysite/"
     * - cookie_name: name of the session (so the cookie) send to the user,
     * - debug: true/false - whether or not to display debug information. This should be set to true only while in developement!
     * - date_format: date format used on the site: this is used for example to automatically convert date values to appropriate date object when inserting with MongoDB
     * - date_hour_format: as above, but with time,
     * - restrict: list of controllers with access restricted to specified ips, example:
     *  "restrict" => array(
     *      "admin" => array(	    
     *          "127.0.0.1", 
     *          "::1", //127.0.0.1 in ipv6   
     *      )
     *  )
     *  
     *  If user tries to access given controller from different ip, he will be shown a 404 error. 
     *  This can be used as a very (very!) simple protection for specific controllers.
     *
     * * Database-specific settings *
     * - mysql_db_host: MySQL host, used when connecting to MySQL,
     * - mysql_db_port: MySQL port, used when connecting to MySQL (if not defined, default {@link PHP_MANUAL#PDO} value will be used),
     * - mysql_db_user: MySQL username, used when connecting to MySQL,
     * - mysql_db_pass: MySQL password for given username, used when connecting to MySQL,
     * 
     * - mongo_db_socket: path to socket used to connect to MongoDB instance,
     * - mongo_db_host: MongoDB host - this will be used to connect to MongoDB instance if mongo_db_socket is NOT set. 
     * - mongo_db_port: MongoDB port (only if mongo_db_socket is not set). If not specified, default 27017 will be used.
     * - mongo_db_user: username used to connect to MongoDB instance,
     * - mongo_db_pass: password used to connect to MongoDB instance,
     * - memcache_host: Memcache host, if not set caching will not be used,
     * - memcache_port: Memcache port,
     * 
     * - crypt_std_key: secret crypt key, used to crypt database values (if set in model),
     * 
     * @var array 
     */
    protected static $_defaults = array(	
        "absolute_url" => false,
	"relative_url" => false,
        "cookie_name" => "PHPSESSID",
	"debug" => false,
	"date_format" => "Y-m-d",
	"date_hour_format" => "Y-m-d H:i:s",
        "restrict" => array(),
	"mysql_db_host" => false,
	"mysql_db_port" => false,
	"mysql_db_user" => false,
	"mysql_db_pass" => false,
	"mongo_db_socket" => false,
	"mongo_db_host" => false,
        "mongo_db_port" => false,
	"mongo_db_user" => false,
	"mongo_db_pass" => false,
	"memcache_host" => false,
	"memcache_port" => 11211,
        "crypt_std_key" => ""       
    );
    
    /**
     * Returns config option, if found. 
     * If the value is not set on startup, default value from {@link $_defaults}
     * is used. If neither value is found, false is returned.
     * @param string $setting Name of the option.
     * @return string Found option or false if not found.
     */
    public static function get($setting){
	if(isset(self::$_config[$setting])){
	    return self::$_config[$setting];
	}
	if(isset(self::$_defaults[$setting])){
	    return self::$_defaults[$setting];
	}
	return false;
    }
    
    /**
     * Sets all config variables, overwriting any settings that were set before.
     * This should be called at very beginning of the app init to set main config options.
     * @param array $values 
     */
    public static function set($values){
	self::$_config = $values;
    }
    
    /**
     * Sets (or overwrides) given config option.
     * @param string $key Name of the option.
     * @param string $val Value of the option.
     */
    public static function setKey($key, $val){
        self::$_config[$key] = $val;
    }
}