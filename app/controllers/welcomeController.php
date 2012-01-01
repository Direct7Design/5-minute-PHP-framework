<?php
class welcomeController extends appCore {
    
    public function index() {
	$this->user()->refresh();
	if(!$this->user()->properUser()){
	    $this->redirect("");
	}
        
        $menuHelper = new menuHelper();
        $menuHelper->getMenu();
	
	$this->view()->addTemplate("welcome");
    }
    
}