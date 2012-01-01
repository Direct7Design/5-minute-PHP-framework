<?php
class menuHelper extends appCore {
    
    public function getMenu(){
	$this->view()->addTemplate("menu");
    }
    
}