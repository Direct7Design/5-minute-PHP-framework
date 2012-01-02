<?php
/**
 * This is a class taking care of views and returning html to the user.
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 */
class appView {
    
    /**
     * List of templates to be rendered.
     * @var array
     */
    private $_templates = array();
    
    /**
     * List of variables to be passed to templates.
     * @var array
     */
    private $_templates_args = array();
    
    /**
     * List of templates to be send via ajax.
     * @var array 
     */
    private $_ajax = array();
    
    /**
     * List of js files to be included.
     * @var array 
     */
    private $_js = array();
    
    /**
     * If the request should be send like ajax.
     * @var bool
     */
    private $_isAjax = false;
    
    /**
     * Whether or not the views should be disabled.
     * @var bool
     */
    private $_disabled = false;
    
    /**
     * Ajax data that should be returned as json-encoded string.
     * @var array 
     */
    private $_ajaxData = array();
    
    /**
     * Title of the page - used in <title> tags.
     * If not set - the default title will be used as defined in template.
     * @var string
     */
    public static $title = false;   
    
    /**
     * Adds "templates/" directory to the include path.
     */
    public function __construct(){
	set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__."/templates/");
    }
    
    /**
     * Main function called to render the views.
     * This returns false if views are disabled ({@link $_disabled}).
     * It returns ONLY ajax or ONLY "normal" templates: 
     * if {@link $_isAjax} is set to true and there are some ajax templates added - the call will be returned as json encoded string.
     * In other case "normal" html will be returned to the user.
     * @return bool Returns false if views are disabled.
     */
    public function render(){
	if($this->_disabled) return false;
	
	if(empty($this->_ajax) && !$this->_isAjax){
	    //non-ajax
	    $this->_templates_args['templates'] = $this->_templates;
	    if(!empty($this->_js)){
                foreach($this->_js as &$val){
                    if(strpos($val, "http") !== 0){
                        $val = appConfig::get("relative_url")."www/js/".$val;
                    }
                }                
		$this->_templates_args['extraJs'] = $this->_js;
	    }
	    extract($this->_templates_args);
	    
	    ob_start();
	    require 'templates/main.php';
	    $ajTempls = ob_get_clean();
	    echo $ajTempls;
        }elseif(!empty($this->_ajaxData)){
            echo json_encode($this->_ajaxData);
	}else{
	    //ajax
	    extract($this->_templates_args);
	    ob_start();
	    $ajTempls = "";
	    foreach($this->_ajax as $templ){
		require 'templates/'.$templ;
		$ajTempls = ob_get_clean();
	    }
	    echo json_encode($ajTempls);
	}
	
    }
    
    /**
     * Adds "normal" (non-ajax) template to the list of templates to be rendered.
     * Templates are "chained" (not replaced) - that means one template can be rendered more than once.
     * @param string $template Name of the template file.
     */
    public function addTemplate($template){
	$template = trim($template);
	if(substr($template, -4) != ".php"){ //doesn't end with ".php"
	    $template .= ".php";
	}
	$this->_templates[] = $template;
    }
    
    /**
     * Adds a variable for "normal" (non-ajax) templates. 
     * Those variables are later to be used inside the template file.
     * @param string $name Name of the variable. Has to be a valid PHP variable name.
     * @param string $val Value of the variable.
     */
    public function addTemplateVal($name, $val){
	$this->_templates_args[$name] = $val;
    }
    
    /**
     * Adds ajax template to the list of templates to be rendered.
     * Templates are "chained" (not replaced) - that means one template can be rendered more than once.
     * @param string $template Name of the template file.
     */
    public function addAjaxTemplate($template){
	$template = trim($template);
	if(substr($template, -4) != ".php"){ //doesn't end with ".php"
	    $template .= ".php";
	}
	$this->_ajax[] = $template;
    }
    
    /**
     * Adds .js file to the list of .js files that needs to be included in the template.
     * @param string $jsFile Js file name.
     */
    public function addJs($jsFile){
	$this->_js[$jsFile] = $jsFile;
    }
    
    /**
     * Sets whether or not the call should be returned as ajax.
     * Note: for this to work as ajax there has to be at least one ajax template added!
     * @param bool $is 
     */
    public function setAjax($is){
	$this->_isAjax = (bool)$is;
    }
    
    /**
     * Disables the views so that no content is returned from here.
     */
    public function disable(){
	$this->_disabled = true;
    }
    
    /**
     * Creates a link to the given path. Relative links are returned by default.
     * For example, to create a link for controller "user", pass "user" as $url.
     * This should be used in templates to create links, especially for internals of this application.
     * @param string $url Path for the link.
     * @param bool $absolute Whether the returned link should be absolute url.
     * @return string Fixed url. 
     */
    public function link($url, $absolute = false){
	if($absolute) return appConfig::get("absolute_url").$url;
	return appConfig::get("relative_url").$url;
    }
    
    /**
     * Adds data to be returned as json-encoded string.
     * @param mixed $data Data to be returned.
     */
    public function sendAjax($data){
        $this->_isAjax = true;
        if(is_array($data)){
            $this->_ajaxData = array_merge($this->_ajaxData, $data);   
        }
        else{
            $this->_ajaxData[] = $data;
        }   
    }
    
    /**
     * Sets title to the currently shown page.
     * @param string $title Title to show.
     */
    public function setTitle($title){
        self::$title = $title;
    }
}