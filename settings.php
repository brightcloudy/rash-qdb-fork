<?php
$CONFIG['template'] = './templates/nhqdb_template/nhqdb_template.php';

// Database
$CONFIG['phptype'] = 'mysql';
$CONFIG['hostspec'] = 'localhost';
$CONFIG['port'] = '';
$CONFIG['socket'] = '';
$CONFIG['database'] = 'rashdb';
$CONFIG['username'] = 'root';
$CONFIG['password'] = '';
$CONFIG['db_table_prefix'] = 'rash';

// Site info
$CONFIG['site_short_title'] = 'nhqdb';
$CONFIG['site_long_title'] = '#NetHack Quote Database';
$CONFIG['rss_url'] = 'http://127.0.0.1';
$CONFIG['rss_title'] = 'Rash QDB';
$CONFIG['rss_desc'] = 'Quote Database for the IRC channel';
$CONFIG['language'] = 'US-english';
$CONFIG['admin_email'] = 'qdb@this.domain';

// Misc configs
$CONFIG['quote_limit'] = 10;	// how many quotes ?browse displays at once
$CONFIG['page_limit'] = 5;	// how many page numbers to show
$CONFIG['quote_list_limit'] = 50; // Number of quotes shown in ?top/?bottom/?latest/?queue/?random
$CONFIG['moderated_quotes'] = 1; // Quotes need to be accepted by a moderator
$CONFIG['captcha'] = 'nocaptcha';
$CONFIG['timezone'] = 'America/New_York';
$CONFIG['news_time_format'] = 'Y-m-d';
$CONFIG['quote_time_format'] = 'F j, Y';

// No need to change these
$CONFIG['GET_SEPARATOR'] = ini_get('arg_separator.output');
$CONFIG['GET_SEPARATOR_HTML'] = htmlspecialchars($CONFIG['GET_SEPARATOR'], ENT_QUOTES);

