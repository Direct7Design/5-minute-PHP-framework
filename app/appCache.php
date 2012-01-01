<?php
/**
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 */
class appCache {
    
    private $_instance;
    
    private $_namespaceSuffix = "_namespace_key";
    
    public function __construct() {
	$host = appConfig::get("memcache_host");
	if($host){
	    $this->_instance = new Memcached();
	    $this->_instance->addServer($host, appConfig::get("memcache_port"));
	}
    }
    
    public function set($key, $val, $namespace = "", $expire = 0){
	if($this->_instance){
	    $spaceKey = $this->getNamespaceKey($namespace);	 
	    return $this->_instance->set($spaceKey.$key, $val, $expire);
	}
	return false;
    }
    
    public function get($key, $namespace = ""){
	if($this->_instance){
	    $spaceKey = $this->getNamespaceKey($namespace);	    
	    return $this->_instance->get($spaceKey.$key);
	}
	return false;
    }
    
    public function clear($namespace){
	if($this->_instance){
	    $spaceKey = $this->_instance->get($namespace.$this->_namespaceSuffix);
	    if($spaceKey){ //if the key does not exist, there is nothing to clear
		$this->_instance->increment($namespace.$this->_namespaceSuffix);
	    }
	}
    }
    
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