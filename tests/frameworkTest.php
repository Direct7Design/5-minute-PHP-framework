<?php
/**
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkTests
 */
class frameworkTest extends PHPUnit_Extensions_OutputTestCase {

    private $_core;
    private $_corePath; 
    
    public function setUp() {
	require_once '../app/appCore.php';
	session_id("phpunit");
	$this->_core = new appCore();
	$this->_corePath = stream_resolve_include_path('../app/appCore.php');
	
	$this->assertNotEmpty($this->_corePath, "Path to appCore.php could not be resolved!");
    }
    
 
    /**
     * @covers appCore::start
     * @covers appCore::dispatch
     * @covers appCore::end
     * @covers appController::index
     */
    public function testLoginPage(){
	$_SERVER['REQUEST_URI'] = "/example/";
	
	$this->expectOutputRegex("/Login/");
	$this->_core->start(array(
	    "absolute_url" => "http://localhost/example/",
	    "relative_url" => "/example/",
	    "debug" => true,
	    "crypt_std_key" => "wl2okswwxrqqw52k89kq3k1ou29xw66s",
	));
    }
    
    /**
     * Tests whether redirection works appropriately.
     */
    public function testWelcomePage(){
	$_SERVER['REQUEST_URI'] = "/example/welcome/";

	try{
	    $this->_core->start(array(
		"absolute_url" => "http://localhost/example/",
		"relative_url" => "/example/",
		"debug" => true,
		"crypt_std_key" => "wl2okswwxrqqw52k89kq3k1ou29xw66s",
	    ));
	}
	catch(Exception $e){
	    $this->assertAttributeInternalType("int", "line", $e, "Line in the exception should be int");
	    $this->assertAttributeInternalType("array", "trace", $e, "There should be an array with trace");
	    $this->assertAttributeContains(array(
		    "file" => $this->_corePath,
		    "line" => $e->getLine(),
		    "function" => "header",
		    "args" => array(
			0 => "Location: /example/"
		    )
		)
		, "trace", $e);

	}
    }
    
    public function test404(){
	$_SERVER['REQUEST_URI'] = "/example/404/";

	try{
	    $this->_core->start(array(
		"absolute_url" => "http://localhost/example/",
		"relative_url" => "/example/",
		"debug" => true,
		"crypt_std_key" => "wl2okswwxrqqw52k89kq3k1ou29xw66s",
	    ));
	}
	catch(Exception $e){
	    $this->assertAttributeInternalType("int", "line", $e, "Line in the exception should be int");
	    $this->assertAttributeInternalType("array", "trace", $e, "There should be an array with trace");
	    $this->assertAttributeContains(array(
		    "file" => $this->_corePath,
		    "line" => $e->getLine(),
		    "function" => "header",
		    "args" => array(
			0 => "HTTP/1.1 404 Not Found"
		    )
		)
		, "trace", $e);

	}

    }
}