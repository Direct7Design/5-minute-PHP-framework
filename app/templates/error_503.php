<?php 
/**
 * Custom 503 error page.
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 * @subpackage templates
 */
?>
<!DOCTYPE html> 
<html>
    <head>
        <title>503 - Custom error page</title>      
	<link rel="stylesheet" href="<?php echo appConfig::get("relative_url")?>www/css/global.css" media="all" />	
    </head>
    <body>
	<div class="container error_page">
            <h1>503 - This is custom error page!</h1>
            This 503 error is thrown to distinguish database errors from other errors. Only database errors are thrown as 503, every other major error is 500.
            <br /><br />
            Error information and stack trace will be shown below if "debug" option is on.
        </div>	
    </body>
    
</html>