<?php
/**
 * Tests checking the environment - PHP and extensions versions.
 * 
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkTests
 */
class environmentTest extends PHPUnit_Framework_TestCase {
    
    /**
     * Minimal required PHP version.
     * @var string
     */
    private $_min_php_ver = '5.3.0';
    
    /**
     * List of extensions needed by framework.
     * Note that those are not required, but some features will be disabled without them.
     * @var array List of extensions with message about the feature it enables.
     */
    private $_extensions = array(
	"mcrypt" => "Encrypting of database contents will not work!",
	"memcached" => "Caching of the database results will not work!",
	"PDO" => "Connection to MySQL database will not work!",
	"pdo_mysql" => "Connection to MySQL database will not work!",
	"mongo" => "Connection to MongoDB database will not work!"
    );
    
    /**
     * List of the minimal tested versions for the extensions.
     * Some features may work fine with previous versions, but it was not tested.
     * @var array 
     */
    private $_extensions_versions = array(
	"mcrypt" => "1.0",
	"memcached" => "2.0.0",
	"PDO" => "1.0.3",
	"pdo_mysql" => "1.0.2",
	"mongo" => "1.2.6"
    );
    
    /**
     * List of warnings found.
     * @var array 
     */
    private $_warnings = array();
    
    /**
     * Main test in this case - tests PHP version, loaded extensions and their versions.
     * If this test fails with message "Warnings occured" some features may not work (as listed in error message),
     * but the framework should work fine without them.
     */
    public function testPHPandExtensions(){
	
	//check php version
	$this->assertGreaterThanOrEqual(0, version_compare(PHP_VERSION, $this->_min_php_ver), "Your PHP version must be at least $this->_min_php_ver.");

	//check which extensions are available
	foreach($this->_extensions as $extension => $message){
	    try {
		$this->assertTrue(extension_loaded($extension));
	    }
	    catch (PHPUnit_Framework_ExpectationFailedException $e) {
	       $this->_warnings[$extension] = "Extension $extension was not found: $message";
	    }
	}
	
	//check if versions of extension are valid
	foreach($this->_extensions_versions as $extension => $version){
	    if(isset($this->_warnings[$extension])) continue; //skip all not found extensions
	    
	    $ext_version = phpversion($extension);
	    try {
		$this->assertGreaterThanOrEqual(0, version_compare($ext_version, $version));
	    }
	    catch (PHPUnit_Framework_ExpectationFailedException $e) {
	       $this->_warnings[] = "Least tested version of $extension extension is $version. Detected version is: $ext_version. Consider upgrading!";
	    }
	}

	$this->assertEmpty($this->_warnings, "Warnings occured: \n".implode("\n", $this->_warnings));;
    }
}