<?php
require_once  'DB.php';
$dsn = array(
    'phptype'  => $phptype,
    'username' => $username,
    'password' => $password,
    'hostspec' => $hostspec,
    'port'     => $port,
    'socket'   => $socket,
    'database' => $database,
);
$db =& DB::connect($dsn);
if (DB::isError($db)) {
    die($db->getMessage());
}
?>
