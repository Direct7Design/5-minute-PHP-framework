<?php 
/**
 * Custom 500 error page.
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 * @subpackage templates
 */
?>
<!DOCTYPE html> 
<html>
    <head>
        <title>500 - Custom error page</title>      
	<link rel="stylesheet" href="<?php echo appConfig::get("relative_url")?>www/css/global.css" media="all" />	
    </head>
    <body>
	<div class="container error_page">
            <h1>500 - This is custom error page!</h1>
            We are experiencing some issues, so try <a href="javascript:location.reload(true)">refreshing the page</a> or come back later.
            <br /><br />
            Error information and stack trace will be shown below if "debug" option is on.
        </div>	
    </body>
    
</html>