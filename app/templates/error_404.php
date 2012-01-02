<?php 
/**
 * Custom 404 error page.
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 * @subpackage templates
 */
?>
<!DOCTYPE html> 
<html>
    <head>
        <title>404 - Custom error page</title>
	<link rel="stylesheet" href="<?php echo appConfig::get("relative_url")?>www/css/global.css" media="all" />
    </head>
    <body>
	<div class="container error_page">
            <h1>404 - This is custom error page!</h1>
        We're sorry, the page you're looking for is not here...<br />
        Maybe try going to <a href="<?php echo appConfig::get("relative_url")?>">our home page</a> or check the url for spelling errors.
        <br /><br />
        Error information and stack trace will be shown below if "debug" option is on.
	</div>	
    </body>
    
</html>