<?php
/**
 * Example controller for the framework. The index() method should always exist!
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 * @subpackage controllers
 */
class welcomeController extends appCore {
    
    /**
     * Shows "welcome" page.
     */
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