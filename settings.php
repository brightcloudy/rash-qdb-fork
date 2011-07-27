<?php
// Server Information
//
$template			= './templates/bash_template/bash_template.php';
$GET_SEPARATOR		= ini_get('arg_separator.output');
$GET_SEPARATOR_HTML	= htmlspecialchars($GET_SEPARATOR, ENT_QUOTES);

// Database information
//
$phptype = 'mysql';			// options include dbase, fbsql, ibase, ifx, msql, mssql,
							// mysql, mysqli, oci8, odbc, pgsql, sqlite, and sybase
							//
							// Put the database your system has as what is assigned to
							// $phptype, if you don't know, it's probably mysql, if not
							// contact your host and find out
							//
$hostspec = 'localhost';	// host of server, if you don't know, it's probably localhost
							//
$port = '';					// port to use to connect to database server, only uncomment if you
							// need to change defaults
							//
$socket = '';				// socket for database, only uncomment if you need to change defaults
							//
$database = 'rash';		// the database to use on the server
							//
$username = 'root';		// the username for the database
							//
$password = 'trimuph0';		// password of the $username account

// Language Information
$language   = 'US-english'; // currently onle US-english works, please email me at liverbubble@gmail.com if you can help make new language packs
							//

// Paging System Information
$quote_limit= 10;			// quote limit is a variable related to the new paging system, this variable tells ?browse how many quotes to display at one time
							//
$page_limit = 5;			// also related to the new paging system, how many page numbers to show once, i find 5 looks the nicest
							//

// RSS information
$rss_url    = 'http://qdb.rawrnix.com';		//omit trailing slash or face eternal hellfire 
												// your rss feed is at yoursite.com/rash/?rss
$rss_title  = '#nethack QDB';	// title of your rss feed 

$rss_desc = 'Quote Database for the IRC channel #NetHack'; // description of the rss feed

?>
