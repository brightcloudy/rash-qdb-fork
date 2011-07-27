<?php

function write_settings($fname, $data)
{
    $fp = fopen($fname,"w");
    $str = "<?php \n";
    foreach ($data as $key=>$val) {
	$str .= $key.' = '.$val.";\n";
    }
    if (fwrite($fp, $str, strlen($str)) === FALSE) {
	return FALSE;
    }
    return TRUE;
}

$def_template = './templates/bash_template/bash_template.php';

if (file_exists($def_template)) {
    require $def_template;
} else {
    function printheader($txt) {}
    function printfooter() {}
}

printheader();

If($_SERVER['QUERY_STRING'] == md5('create_file')){
    if (file_exists('settings.php')){
	die("settings.php already exists.");
    }
    $data = array('$template' => "'".$_POST['template']."'",
		  '$GET_SEPARATOR' => "ini_get('arg_separator.output')",
		  '$GET_SEPARATOR_HTML' => 'htmlspecialchars($GET_SEPARATOR, ENT_QUOTES)',
		  '$phptype' => "'".$_POST['phptype']."'",
		  '$hostspec' => "'".$_POST['hostspec']."'",
		  '$port' => "''",
		  '$socket' => "''",
		  '$database' => "'".$_POST['database']."'",
		  '$username' => "'".$_POST['username']."'",
		  '$password' => "'".$_POST['password']."'",
		  '$rss_url' => "'".$_POST['rss_url']."'",
		  '$rss_title' => "'".$_POST['rss_title']."'",
		  '$rss_desc' => "'".$_POST['rss_desc']."'",
		  '$language' => "'US-english'",
		  '$quote_limit' => '10',
		  '$page_limit' => '5');
    if (!write_settings('settings.php', $data)) {
	die("Sorry, cannot write settings.php");
    }
    header("Location: install.php?".md5('create_tables'));
}
elseif($_SERVER['QUERY_STRING'] == md5('create_tables')){
    if (!file_exists('settings.php')){
	die("settings.php does not exist.");
    }

    print '<h2>Creating database tables...</h2>';


    function mk_db_table($tablename,$fields)
    {
	include 'settings.php';
	include 'connect.php';
	print 'Create table '.$tablename.': ';
	$sql = 'CREATE TABLE '.$tablename.' ('.$fields.');';
	$res =& $db->query($sql);
	if (DB::isError($res)) {
	    die($res->getMessage());
	}
	echo "OK<br />";
    }

    mk_db_table('rash_quotes', "id int(11) NOT NULL auto_increment primary key,
							quote text NOT NULL,
							rating int(7) NOT NULL,
							flag int(1) NOT NULL,
							date int(10) NOT NULL");

    mk_db_table('rash_queue', "id int(11) NOT NULL auto_increment primary key,
							quote text NOT NULL");


    mk_db_table('rash_tracking', "id int(11) NOT NULL auto_increment primary key,
							ip varchar(15) NOT NULL,
							quote_id text NOT NULL,
							vote text NOT NULL,
							flag text NOT NULL");

    mk_db_table('rash_users', "user varchar(20) NOT NULL,
							`password` varchar(255) NOT NULL,
							level int(1) NOT NULL,
							salt text");

    mk_db_table('rash_news', "id int(11) NOT NULL auto_increment primary key,
							news text NOT NULL,
							date int(10) NOT NULL");

    echo 'Everything should now be OK.';
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

  RSS URL:            <input type="text" name="rss_url" value="<?php echo 'http://'.$_SERVER['SERVER_NAME'];?>">
  RSS Title:          <input type="text" name="rss_title" value="Rash QDB">
  RSS Description:    <input type="text" name="rss_desc" value="Quote Database for the IRC channel">

  <input type="submit" value="Submit">
 </form>
</pre>
<?php
    } else {
	die("settings.php already exists.");
    }
}
printfooter();

?>