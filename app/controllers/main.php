<?php
class appController extends appCore {
    
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
    
    public function logout(){
	if($this->user()->properUser()){
	    $this->user()->logout();
	}
	$this->redirect("");
    }
}