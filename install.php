<?php
/*
error_reporting(E_ALL);
ini_set('display_errors','On');
*/
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

function update_rash_quotes()
{
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

    $res =& $db->query("SELECT * from ".db_tablename('quotes').' LIMIT 1');
    if (!(DB::isError($res))) {
	while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	    if (isset($row['queue'])) { /* already up-to-date */
		print 'Quotes -table is up-to-date<br />';
		return 0;
	    }
	}
    }

    $res =& $db->query("SELECT * from ".db_tablename('queue'));
    if (!(DB::isError($res))) {
	while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	    $pending[] = $row;
	}
    } else {
	print 'Queue -table is already dropped.<br>';
	return 0;
    }

    if (count($pending) > 0) {
	print 'Updating queued quotes...';
    }

    $res =& $db->query("DROP TABLE ".db_tablename('queue'));

    $res =& $db->query("ALTER TABLE ".db_tablename('quotes'). " ADD queue int(1) not null");
    $res =& $db->query("UPDATE ".db_tablename('quotes'). " SET queue=0");

    foreach ($pending as $row) {
        $res =& $db->query("INSERT INTO ".db_tablename('quotes')." (quote, rating, flag, queue, date) VALUES(".$db->quote($row['quote']).", 0, 0, 1, '".mktime()."')");
    }

    if (DB::isError($res)) {
	print $res->getMessage().'<br />';
	return 1;
    }
    print 'OK<br />';
    return 0;
}

function update_old_users()
{
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

    $users = array();

    $res =& $db->query("SELECT * from ".db_tablename('users'));
    if (!(DB::isError($res))) {
	while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	    if (isset($row['id'])) { /* already up-to-date */
		print 'Users -table is up-to-date<br />';
		return 0;
	    }
	    $users[] = $row;
	}
    }

    if (count($users) > 0) {
	print 'Updating old RASH-style users table...';
    } else {
	print 'Creating users table...';
    }

    $res =& $db->query("DROP TABLE ".db_tablename('users'));

    $res =& $db->query("CREATE TABLE ".db_tablename('users'). " (id int(11) NOT NULL auto_increment primary key,
							user varchar(20) NOT NULL,
							`password` varchar(255) NOT NULL,
							level int(1) NOT NULL,
							salt text)");

    foreach ($users as $row) {
	$res =& $db->query("INSERT INTO ".db_tablename('users')." (user, password, level, salt) VALUES (".
			   $db->quote($row['user']).",".
			   $db->quote($row['password']).",".
			   $db->quote($row['level']).",".
			   $db->quote($row['salt']).")");
    }

    if (DB::isError($res)) {
	print $res->getMessage().'<br />';
	return 1;
    }
    print 'OK<br />';
    return 0;
}

function update_old_tracking()
{
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

    $tracking = array();

    $res =& $db->query("SELECT * from ".db_tablename('tracking'));
    if (!(DB::isError($res))) {
	while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	    if (isset($row['user_ip'])) { /* already up-to-date */
		print 'Tracking -table is up-to-date<br />';
		return 0;
	    }
	    $tracking[] = $row;
	}
    }

    if (count($tracking) > 0) {
	print 'Updating old RASH-style '.db_tablename('tracking').' table...';
    } else {
	print 'Creating '.db_tablename('tracking').' table...';
    }

    $res =& $db->query("DROP TABLE ".db_tablename('tracking'));

    $res =& $db->query("CREATE TABLE ".db_tablename('tracking'). " (id int NOT NULL auto_increment primary key,
                              user_ip varchar(15) NOT NULL,
                              user_id int,
                              quote_id int NOT NULL,
                              vote int NOT NULL)");

    foreach ($tracking as $row) {

	$qids = explode(",", $row['quote_id']);
	$votes = explode(",", $row['vote']);

	for ($idx = 0; $idx < count($qids); $idx++) {
	    if ($votes[$idx] == '1') {
		$query ="INSERT INTO ".db_tablename('tracking')." (user_ip, user_id, quote_id, vote) VALUES (".
		    $db->quote($row['ip']).",".
				   "null,".
				   $qids[$idx].",".
				   "2)"; /* 2 = assumed + vote, old table doesn't keep track! */
		$res =& $db->query($query);
		if (DB::isError($res)) {
		    print $res->getMessage().'<br />'.$query.'<br>';
		    return 1;
		}

	    }
	}
    }

    if (DB::isError($res)) {
	print $res->getMessage().'<br />';
	return 1;
    }
    print 'OK<br />';
    return 0;

}

$languages = array('US-english','Finnish');

$captchas = array(array('name'=>'nocaptcha', 'desc'=>'No CAPTCHA'),
		  array('name'=>'42captcha', 'desc'=>'The Ultimate Question CAPTCHA'),
		  array('name'=>'nhcaptcha', 'desc'=>'NetHack CAPTCHA'));

$captcha_uses = array('flag'=>'Flagging a quote',
		      'add_quote' => 'Adding a quote');

$templates = array('./templates/bash_template/bash_template.php' => 'bash.org lookalike',
		   './templates/rash_template/rash_template.php' => 'Rash QMS',
		   './templates/nhqdb_template/nhqdb_template.php' => 'nhqdb');
$def_template = './templates/bash_template/bash_template.php';

require 'basetemplate.php';

if (file_exists($def_template)) {
    /*require('language/US-english.lng');*/
    require $def_template;
} else {
    class TempTemplate extends BaseTemplate  {
    }
    $TEMPLATE = new TempTemplate();
}

$TEMPLATE->printheader('Install Rash Quote Management System');

If (isset($_POST['submit'])) {
    if (file_exists('settings.php')){
	die("settings.php already exists.");
    }
    if (!isset($_POST['template'])) {
	header('Location: install.php');
	exit;
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
		  'rss_url' => "'".preg_replace('/\/$/','',$_POST['rss_url'])."'",
		  'rss_title' => "'".$_POST['rss_title']."'",
		  'rss_desc' => "'".$_POST['rss_desc']."'",
		  'rss_entries' => (!isset($_POST['rss_entries']) || ($_POST['rss_entries'] < 1)) ? 15 : $_POST['rss_entries'],
		  'language' => "'".$_POST['language']."'",
		  'captcha' => "'".$_POST['captcha']."'",
		  'use_captcha' => "array(".(isset($_POST['use_captcha']) ? ("'".implode("'=>1, '", $_POST['use_captcha'])."'=>1"): '').")",
		  'admin_email' => "'".$_POST['admin_email']."'",
		  'quote_limit' => $_POST['quote_limit'],
		  'page_limit' => $_POST['page_limit'],
		  'quote_list_limit' => $_POST['quote_list_limit'],
		  'moderated_quotes' => (($_POST['moderated_quotes'] == 'on') ? 1 : 0),
		  'auto_flagged_quotes' => (($_POST['auto_flagged_quotes'] == 'on') ? 0 : 1),
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
                                                        queue int(1) NOT NULL,
							date int(10) NOT NULL");

    $error |= update_rash_quotes();

    $error |= update_old_tracking();
    $error |= update_old_users();

    $error |= mk_db_table(db_tablename('news'), "id int(11) NOT NULL auto_increment primary key,
							news text NOT NULL,
							date int(10) NOT NULL");

    if (trim($_POST['adminuser']) != '')
	$error |= mk_user($_POST['adminuser'], $_POST['adminpass']);

    if ($error) {
	print '<p>There were some errors...';
    } else {
	print '<p>Everything should now be OK.';
    }
}
else {
    if(!file_exists('settings.php')){

	if (!write_settings('settings.php', null)) {
	    die('Cannot write settings.');
	}
	@unlink('settings.php');

	function mk_rss_url()
	{
	    return 'http://'.$_SERVER['SERVER_NAME'] . preg_replace('/\/install.php$/', '', $_SERVER['REQUEST_URI']);
	}

?>
<h2>Install</h2>
<form action="./install.php" method="post">
<table>
 <tr>
  <td>Template</td>
  <td><select name="template"><? foreach ($templates as $k=>$v) { echo '<option value="'.$k.'">'.$v; } ?></select>
 </tr>
 <tr>
	<td>&nbsp;</td><td>&nbsp;</td>
 </tr>
 <tr>
  <td>DB Type
  <td><input type="text" name="phptype" value="mysql">
 </tr>
 <tr>
  <td>DB Hostname
  <td><input type="text" name="hostspec" value="localhost">
 </tr>
 <tr>
  <td>DB Database
  <td><input type="text" name="database" value="rash">(which database to use)
 </tr>
 <tr>
  <td>DB Username
  <td><input type="text" name="username" value="username">
 </tr>
 <tr>
  <td>DB Password
  <td><input type="password" name="password" value="password">
 </tr>
 <tr>
	<td>&nbsp;</td><td>&nbsp;</td>
 </tr>
 <tr>
  <td>DB table prefix
  <td><input type="text" name="db_table_prefix" value="rash">
 </tr>
 <tr>
  <td>&nbsp;</td><td>&nbsp;</td>
 </tr>
 <tr>
  <td>Admin Username
  <td><input type="text" name="adminuser" value="admin"> (Leave empty to not create one)
 </tr>
 <tr>
  <td>Admin Password
  <td><input type="password" name="adminpass" value="password">
 </tr>
 <tr>
  <td>Admin EMail
  <td><input type="text" name="admin_email" value="qdb@<?php echo $_SERVER['SERVER_NAME']; ?>">
 </tr>
 <tr>
  <td>&nbsp;</td><td>&nbsp;</td>
 </tr>
 <tr>
  <td>Site Language
  <td><select name="language"><? foreach($languages as $l) { echo '<option value="'.$l.'">'.$l; } ?></select>
 </tr>
 <tr>
  <td>&nbsp;</td><td>&nbsp;</td>
 </tr>
 <tr>
  <td>Site Short Title
  <td><input type="text" name="site_short_title" value="QMS">
 </tr>
 <tr>
  <td>Site Long Title
  <td><input type="text" name="site_long_title" value="Quote Management System" size="40">
 </tr>
 <tr>
  <td>&nbsp;</td><td>&nbsp;</td>
 </tr>
 <tr>
  <td>RSS URL
  <td><input type="text" name="rss_url" value="<?php echo mk_rss_url(); ?>" size="40">
 </tr>
 <tr>
  <td>RSS Title
  <td><input type="text" name="rss_title" value="Rash QDB">
 </tr>
 <tr>
  <td>RSS Description
  <td><input type="text" name="rss_desc" value="Quote Database for the IRC channel" size="40">
 </tr>
 <tr>
  <td>RSS Entries
  <td><input type="text" name="rss_entries" value="15" size="4"> (number of quotes shown in RSS feed)
 </tr>
 <tr>
  <td>&nbsp;</td><td>&nbsp;</td>
 </tr>
 <tr>
  <td>Quote limit
  <td><input type="text" name="quote_limit" value="10" size="4"> (number of quotes shown per page when browsing)
 </tr>
 <tr>
  <td>Page limit
  <td><input type="text" name="page_limit" value="5" size="4"> (how many page numbers shown when browsing)
 </tr>
 <tr>
  <td>Quote List limit
  <td><input type="text" name="quote_list_limit" value="50" size="4"> (how many quotes are shown in non-browse pages, eg. ?top)
 </tr>
 <tr>
  <td>Moderated
  <td><input type="checkbox" name="moderated_quotes" checked> Do quotes need to be accepted by a moderator?
 </tr>
 <tr>
  <td>Quote flagging
  <td><input type="checkbox" name="auto_flagged_quotes" checked> Can users flag quotes for admin attention?
 </tr>
 <tr>
  <td>&nbsp;</td><td>&nbsp;</td>
 </tr>
 <tr>
  <td>CAPTCHA
  <td><select name="captcha"><? foreach($captchas as $c) { echo '<option value="'.$c['name'].'">'.$c['desc']; } ?></select>
 </tr>
 <tr>
  <td>Use CAPTCHA For
  <td><? foreach ($captcha_uses as $k=>$v) { echo '<input type="checkbox" name="use_captcha[]" value="'.$k.'" checked>'.$v.'<br>'; } ?>
 </tr>
 <tr>
  <td>&nbsp;</td><td>&nbsp;</td>
 </tr>
 <tr>
  <td>Timezone
  <td><input type="text" name="timezone" value="America/New_York"> (See <a href="http://www.php.net/manual/en/timezones.php">list of supported timezones</a>)
 </tr>
 <tr>
  <td>News time format
				 <td><input type="text" name="news_time_format" value="Y-m-d"> (example: <? print date("Y-m-d"); ?>, See <a href="http://php.net/manual/en/function.date.php">list of date format characters</a>)
 </tr>
 <tr>
  <td>Quote time format
  <td><input type="text" name="quote_time_format" value="F j, Y"> (example: <? print date("F j, Y"); ?>)
 </tr>
 <tr>
  <td>&nbsp;</td><td>&nbsp;</td>
 </tr>
 <tr>
  <td>&nbsp;</td>
  <td><input type="submit" value="Submit" name="submit">
 </tr>
 </table>
 </form>
<?php
    } else {
	print "<p>settings.php already exists.";
    }
}
print '<p><a href="./">QDB main page</a></p>';
$TEMPLATE->printfooter();
