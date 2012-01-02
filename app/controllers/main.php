<?php
/**
 * Main controller for the framework. The index() method should always exist!
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 * @subpackage controllers
 */
class appController extends appCore {
    
    /**
     * Method called for empty requests ("home" page).
     */
    public function index() {
	$this->user()->refresh();
	if($this->user()->properUser()){
	    $this->redirect("welcome");
	}
	
	$user = $this->request()->get("login");
	$password = $this->request()->get("password");
	if($user && $password){
	    if($this->user()->login($user, $password)){
		$this->redirect("welcome");
	    }
	    $this->view()->addTemplateVal("login_error", true);
	}
	
	$this->view()->addTemplate("login");
    }
    
    /**
     * Logouts the user. Opened by /app/logout
     */
    public function logout(){
	if($this->user()->properUser()){
	    $this->user()->logout();
	}
	$this->redirect("");
    }
}