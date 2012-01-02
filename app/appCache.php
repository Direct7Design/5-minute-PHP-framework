<?php
/**
 * This class is responsible for caching with memcached.
 * To enable caching, set "memcache_host" config setting to appropriate value. 
 * If "memcache_host" is not set, caching will be disabled.
 * 
 * @uses PHP_MANUAL#Memcached to cache the data.
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 */
class appCache {
    
    /**
     * Holds the Memcached instance.
     * @var Memcached 
     */
    private $_instance;
    
    /**
     * Suffix added to every key to simulate namespaces.
     * @see http://code.google.com/p/memcached/wiki/FAQ#Namespaces
     * @var string  
     */
    private $_namespaceSuffix = "_namespace_key";
    
    /**
     * Creates new Memcached instance if "memcache_host" is defined in configuration.
     */
    public function __construct() {
	$host = appConfig::get("memcache_host");
	if($host){
	    $this->_instance = new Memcached();
	    $this->_instance->addServer($host, appConfig::get("memcache_port"));
	}
    }
    
    /**
     * Sets the given key with value in cache.
     * @param string $key Key to use.
     * @param string $val Value to save.
     * @param string $namespace Optional namespace.
     * @param int $expire Optional number of seconds for which the value should be stored.
     * @see http://pl.php.net/manual/en/memcached.expiration.php
     * @return bool Returns true on success, false on failure (or if caching is disabled). 
     */
    public function set($key, $val, $namespace = "", $expire = 0){
	if($this->_instance){
	    $spaceKey = $this->getNamespaceKey($namespace);	 
	    return $this->_instance->set($spaceKey.$key, $val, $expire);
	}
	return false;
    }
    
    /**
     * Returns previously set key (if still valid).
     * @param string $key Key to retrieve.
     * @param string $namespace Optional namespace.
     * @return mixed Value for the specified key or false on failure (or if caching is disabled). 
     */
    public function get($key, $namespace = ""){
	if($this->_instance){
	    $spaceKey = $this->getNamespaceKey($namespace);	    
	    return $this->_instance->get($spaceKey.$key);
	}
	return false;
    }
    
    /**
     * Clears the given namespace. Used when the original data has changed and cached data is outdated.
     * @param string $namespace Namespace to clear.
     */
    public function clear($namespace){
	if($this->_instance){
	    $spaceKey = $this->_instance->get($namespace.$this->_namespaceSuffix);
	    if($spaceKey){ //if the key does not exist, there is nothing to clear
		$this->_instance->increment($namespace.$this->_namespaceSuffix);
	    }
	}
    }
    
    /**
     * Gets appropriate namespace key or creates a new key for given namespace if it doesn't exist.
     * @param string $namespace Namespace to use.
     * @return mixed Namespace key or false if caching is disabled 
     */
    private function getNamespaceKey($namespace){
	if($this->_instance){
	    $spaceKey = $this->_instance->get($namespace.$this->_namespaceSuffix);
	    if(!$spaceKey){
		$spaceKey = rand(1, 99999);
		$this->_instance->set($namespace.$this->_namespaceSuffix, $spaceKey);
	    }
	    return $spaceKey;
	}
	return false;
    }

}