<?php

include 'util_funcs.php';

function db_query($sql) {
    include 'settings.php';
    require_once 'DB.php';
    $dsn = array(
		 'phptype'  => $CONFIG['phptype'],
		 'username' => $CONFIG['username'],
		 'password' => $CONFIG['password'],
		 'hostspec' => $CONFIG['hostspec'],
		 'port'     => $CONFIG['port'],
		 'socket'   => $CONFIG['socket'],
		 'database' => $CONFIG['database'],
		 );
    $db =& DB::connect($dsn);
    if (DB::isError($db)) {
	print $db->getMessage().'<br />';
	return 1;
    }
    $res =& $db->query($sql);
    if (DB::isError($res)) {
	print $res->getMessage().'<br />';
	return 1;
    } else {
	print "OK<br />";
	return 0;
    }
}


$def_template = './templates/bash_template/bash_template.php';

if (file_exists($def_template)) {
    /*require('language/US-english.lng');*/
    require $def_template;
} else {
    class BaseTemplate {
	function printheader($txt, $topleft='', $topright='') {}
	function printfooter() {}
    }
    $TEMPLATE = new BaseTemplate();
}

$TEMPLATE->printheader('Install Rash Quote Management System');

If($_SERVER['QUERY_STRING'] == md5('create_file')){
    if (file_exists('settings.php')){
	die("settings.php already exists.");
    }
    $data = array('template' => "'".$_POST['template']."'",
		  'phptype' => "'".$_POST['phptype']."'",
		  'hostspec' => "'".$_POST['hostspec']."'",
		  'port' => "''",
		  'socket' => "''",
		  'database' => "'".$_POST['database']."'",
		  'username' => "'".$_POST['username']."'",
		  'password' => "'".$_POST['password']."'",
		  'db_table_prefix' => "'".$_POST['db_table_prefix']."'",
		  'site_short_title' => "'".$_POST['site_short_title']."'",
		  'site_long_title' => "'".$_POST['site_long_title']."'",
		  'rss_url' => "'".$_POST['rss_url']."'",
		  'rss_title' => "'".$_POST['rss_title']."'",
		  'rss_desc' => "'".$_POST['rss_desc']."'",
		  'language' => "'US-english'",
		  'quote_limit' => $_POST['quote_limit'],
		  'page_limit' => $_POST['page_limit'],
		  'timezone' => "'".$_POST['timezone']."'",
		  'news_time_format' => "'".$_POST['news_time_format']."'",
		  'quote_time_format' => "'".$_POST['quote_time_format']."'",
		  'GET_SEPARATOR' => "ini_get('arg_separator.output')",
		  'GET_SEPARATOR_HTML' => 'htmlspecialchars($CONFIG[\'GET_SEPARATOR\'], ENT_QUOTES)');
    if (!write_settings('settings.php', $data)) {
	die("Sorry, cannot write settings.php");
    }

    if (!file_exists('settings.php')){
	die("settings.php does not exist.");
    }

    print '<h2>Creating database tables...</h2>';

    function mk_db_table($tablename,$fields)
    {
	print 'Create table '.$tablename.': ';
	return db_query('CREATE TABLE '.$tablename.' ('.$fields.');');
    }

    function mk_user($username, $password)
    {
	print 'Creating user '.$username.': ';
	$salt = str_rand();
	$level = 1;
	$str = "INSERT INTO ".db_tablename('users')." (user, password, level, salt) VALUES('$username', '".crypt($password, "\$1\$".substr($salt, 0, 8)."\$")."', '$level', '\$1\$".$salt."\$');";
	return db_query($str);
    }

    $error = mk_db_table(db_tablename('quotes'), "id int(11) NOT NULL auto_increment primary key,
							quote text NOT NULL,
							rating int(7) NOT NULL,
							flag int(1) NOT NULL,
							date int(10) NOT NULL");

    $error |= mk_db_table(db_tablename('queue'), "id int(11) NOT NULL auto_increment primary key,
							quote text NOT NULL");


    $error |= mk_db_table(db_tablename('tracking'), "id int(11) NOT NULL auto_increment primary key,
							ip varchar(15) NOT NULL,
							quote_id text NOT NULL,
							vote text NOT NULL,
							flag text NOT NULL");

    $error |= mk_db_table(db_tablename('users'), "user varchar(20) NOT NULL,
							`password` varchar(255) NOT NULL,
							level int(1) NOT NULL,
							salt text");

    $error |= mk_db_table(db_tablename('news'), "id int(11) NOT NULL auto_increment primary key,
							news text NOT NULL,
							date int(10) NOT NULL");

    $error |= mk_user($_POST['adminuser'], $_POST['adminpass']);

    if ($error) {
	print 'There were some errors...';
    } else {
	print 'Everything should now be OK.';
    }
    print '<p><a href="./">QDB main page</a>';
}
else {
    if(!file_exists('settings.php')){
?>
<h2>Install</h2>
<pre><form action="?<?=md5('create_file')?>" method="post">
  Template File Path: <input type="text" name="template" value="<?php echo $def_template; ?>" style="width: 215pt">
  DB Type:	      <input type="text" name="phptype" value="mysql">
  DB Hostname:	      <input type="text" name="hostspec" value="localhost">
  DB Database:	      <input type="text" name="database" value="rash">(which database to use)
  DB Username:	      <input type="text" name="username" value="username">
  DB Password:	      <input type="password" name="password" value="password">

  DB table prefix:    <input type="text" name="db_table_prefix" value="rash">

  Admin Username:     <input type="text" name="adminuser" value="admin">
  Admin Password:     <input type="password" name="adminpass" value="password">

  Site Short Title:   <input type="text" name="site_short_title" value="QMS">
  Site Long Title:    <input type="text" name="site_long_title" value="Quote Management System">

  RSS URL:            <input type="text" name="rss_url" value="<?php echo 'http://'.$_SERVER['SERVER_NAME'];?>">
  RSS Title:          <input type="text" name="rss_title" value="Rash QDB">
  RSS Description:    <input type="text" name="rss_desc" value="Quote Database for the IRC channel">

  Quote limit:        <input type="text" name="quote_limit" value="10"> (number of quotes shown per page when browsing)
  Page limit:         <input type="text" name="page_limit" value="5"> (how many page numbers shown when browsing)

  Timezone:           <input type="text" name="timezone" value="America/New_York">
  News time format:   <input type="text" name="news_time_format" value="Y-m-d">
  Quote time format:  <input type="text" name="quote_time_format" value="F j, Y">

  <input type="submit" value="Submit">
 </form>
</pre>
<?php
    } else {
	die("settings.php already exists.");
    }
}
$TEMPLATE->printfooter();

?>