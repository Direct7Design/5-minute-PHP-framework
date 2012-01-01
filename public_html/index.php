<?php
/**
 * Entry point for all requests.
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 */

/* Include the framework. */
require_once '../app/appCore.php';

/* Start! */
$c = new appCore();
/**
 * This are major config settings that should be set to proper values.
 * For more information about this settings, refer to {@link appConfig::$_defaults}.
 */
$c->start(array(
    "absolute_url" => "http://localhost:8888/sites/framework/public_html/", //with the slash
    "relative_url" => "/sites/framework/public_html/", //with the slash at the beggining and slash at the end
    "debug" => true,
    "mongo_db_host" => "localhost",
    "crypt_std_key" => "wl2okswwxrqqw52k89kq3k1ou29xw66s",
    "mysql_db_host" => "localhost",
    "mysql_db_port" => 3306,
    "mysql_db_user" => "root",
    "mysql_db_pass" => "root"
));
