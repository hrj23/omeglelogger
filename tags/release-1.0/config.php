<?php
	// Add your mysql database settings here:
	$GLOBALS['__DB_LIST']['write']['user'] = "YOUR_MYSQLDB_USERNAME";
	$GLOBALS['__DB_LIST']['write']['pass'] = "YOUR_MYSQLDB_PASSWORD";
	$GLOBALS['__DB_LIST']['write']['db'] = "OmegleLogger";
	$GLOBALS['__DB_LIST']['write']['host'] = "localhost";

	// You probably don't need to touch this line:
	$GLOBALS['__DB_LIST']['read']['alias'] = "write";

	require_once("database.php");
?>
