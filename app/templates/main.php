<?php 
/**
 * Main template for the whole views. This file should always exist!
 * All other templates are added to this file.
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 * @subpackage templates
 */
?>
<!DOCTYPE html> 
<html>
    <head>
        <title><?php echo (appView::$title)?'Framework title - '.appView::$title:'Framework example'?></title>
        <meta name="description" content="" />
	<meta name='author' content="" />
	<meta name="keywords" content="" />
        <link rel="stylesheet" href="<?php echo appConfig::get("relative_url")?>www/css/global.css" media="all"/>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    </head>
    <body>
	<div class="container">
        <?php foreach($templates as $template){ include($template); }?>
	</div>	
    </body>
    <script type="text/javascript" src="<?php echo appConfig::get("relative_url")?>www/js/global.js"></script>
    <?php if(isset($extraJs) && !empty($extraJs)): 
	foreach($extraJs as $js):?>
    <script type="text/javascript" src="<?php echo appConfig::get("relative_url")?>www/js/<?php echo $js?>"></script>
    <?php 
	endforeach;
    endif;?>
</html>