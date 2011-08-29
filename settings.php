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

$CONFIG['secret_salt'] = 'changeme'; // Used to encrypt some things.

// Site info
$CONFIG['site_short_title'] = 'nhqdb';
$CONFIG['site_long_title'] = '#NetHack Quote Database';
$CONFIG['rss_url'] = 'http://127.0.0.1';
$CONFIG['rss_title'] = 'Rash QDB';
$CONFIG['rss_desc'] = 'Quote Database for the IRC channel';
$CONFIG['rss_entries'] = 15;
$CONFIG['language'] = 'US-english';
$CONFIG['admin_email'] = 'qdb@this.domain';

// Misc configs
$CONFIG['quote_limit'] = 10;	// how many quotes ?browse displays at once
$CONFIG['page_limit'] = 5;	// how many page numbers to show
$CONFIG['quote_list_limit'] = 50; // Number of quotes shown in ?top/?bottom/?latest/?queue/?random
$CONFIG['moderated_quotes'] = 1; // Quotes need to be accepted by a moderator
$CONFIG['login_required'] = 0;   // User register & login required for adding/voting/flagging?
$CONFIG['auto_flagged_quotes'] = 0; // Quotes are automatically marked checked by a moderator when they're added.
$CONFIG['captcha'] = 'nocaptcha';
$CONFIG['use_captcha'] = array('flag'=>1, 'add_quote'=>0, 'register_user'=>1);
$CONFIG['timezone'] = 'America/New_York';
$CONFIG['news_time_format'] = 'Y-m-d';
$CONFIG['quote_time_format'] = 'F j, Y';

// No need to change these
$CONFIG['GET_SEPARATOR'] = ini_get('arg_separator.output');
$CONFIG['GET_SEPARATOR_HTML'] = htmlspecialchars($CONFIG['GET_SEPARATOR'], ENT_QUOTES);

