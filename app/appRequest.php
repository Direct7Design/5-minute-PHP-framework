<?php
/**
 * Takes care of all forms data.
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 */
class appRequest {
    
    /**
     * Looks for the argument passed via GET or POST.
     * Can do an "extended" search: if the $name contains spaces and no argument with original name was found, 
     * it will replace all spaces with "_" and try to do a search with this name.
     * @param string $name Name of the argument to be found.
     * @param bool $extended Whether or not to perform an "extended" search.
     * @return mixed Found argument or false.
     */
    public function get($name, $extended = false){
	if(isset($_GET[$name])){
	    return $_GET[$name];
	}
	if(isset($_POST[$name])){
	    return $_POST[$name];
	}
	
	if($extended){ //extended search - look for elements with "_" instead of " "
	    $name = str_replace(" ", "_", $name);
	    return self::get($name);
	}
	return false;
    }
    
    /**
     * Removes argument from GET and POST arrays (if exists).
     * @param string $name Name of the argument.
     */
    public function clear($name){
	if(isset($_GET[$name])){
	    unset($_GET[$name]);
	}
	if(isset($_POST[$name])){
	    unset($_POST[$name]);
	}
    }
}