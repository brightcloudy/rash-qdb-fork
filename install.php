<?php

$def_template = './templates/bash_template/bash_template.php';

if (file_exists($def_template)) {
    require $def_template;
} else {
    function printheader($txt) {}
    function printfooter() {}
}

if (file_exists('settings.php')){
    die("settings.php already exists.");
}

printheader();

If($_SERVER['QUERY_STRING'] == md5('create_file')){
	$fp = fopen("settings.php","w");
	$string = "<?php \n"
				." // Server information\n"
				."//\n"
				."\$template			='".$_POST["template"]."';\n"
				."\$GET_SEPARATOR		= ini_get('arg_separator.output');\n"
				."\$GET_SEPARATOR_HTML	= htmlspecialchars(\$GET_SEPARATOR, ENT_QUOTES);\n\n"
				."// Database Information\n"
				."//\n"
				."\$phptype	= '".$_POST['phptype']."';\n"
				."\$hostspec	= '".$_POST['hostspec']."';\n"
				."\$port		= '';\n"
				."\$socket		= '';\n"
				."\$database	= '".$_POST['database']."';\n"
				."\$username	= '".$_POST['username']."';\n"
				."\$password	= '".$_POST['password']."';\n\n"
				."// Other Information\n"
				."//\n"
//				."\$language	= '".$_POST['language']."';\n"
				."?>";
	if (fwrite($fp, $string, strlen($string)) === FALSE) {
	    die("Sorry, cannot write settings.php");
	}
	header("Location: install.php?".md5('create_tables'));
}
elseif($_SERVER['QUERY_STRING'] == md5('create_tables')){
	$res =& $db->query("CREATE TABLE rash_quotes (
							id int(11) NOT NULL auto_increment primary key,
							quote text NOT NULL,
							rating int(7) NOT NULL,
							flag int(1) NOT NULL,
							date int(10) NOT NULL); ");
	if (DB::isError($res)) {
	    die($res->getMessage());
	}
	echo "Table rash_quotes has been created successfully!<br />";
	$res =& $db->query("CREATE TABLE rash_queue (
							id int(11) NOT NULL auto_increment primary key,
							quote text NOT NULL); ");
	if (DB::isError($res)) {
	    die($res->getMessage());
	}
	echo "Table rash_queue has been created successfully!<br />";
	$res =& $db->query("CREATE TABLE rash_tracking (
							id int(11) NOT NULL auto_increment primary key,
							ip varchar(15) NOT NULL,
							quote_id text NOT NULL,
							vote text NOT NULL,
							flag text NOT NULL); ");
	if (DB::isError($res)) {
	    die($res->getMessage());
	}
	echo "Table rash_tracking has been created successfully!<br />";
	$res =& $db->query("CREATE TABLE rash_users (
							user varchar(20) NOT NULL,
							`password` varchar(255) NOT NULL,
							level int(1) NOT NULL,
							salt text); ");
	if (DB::isError($res)) {
	    die($res->getMessage());
	}
	echo "Table rash_users has been created successfully!<br />";
	$res =& $db->query("CREATE TABLE rash_news (
							id int(11) NOT NULL auto_increment primary key,
							news text NOT NULL,
							date int(10) NOT NULL); ");
	if (DB::isError($res)) {
	    die($res->getMessage());
	}
	echo "Table rash_news has been created successfully!<br />";
	echo 'Everything should now be OK.';
}
elseif(!file_exists('settings.php')){
?>
<h2>Install</h2>
<pre><form action="?<?=md5('create_file')?>" method="post">
  Template File Path: <input type="text" name="template" value="<?php echo $def_template; ?>" style="width: 215pt">
  DB Type:	      <input type="text" name="phptype" value="mysql">
  DB Hostname:	      <input type="text" name="hostspec" value="localhost">
  DB Database:	      <input type="text" name="database" value="rash">(which database to use)
  DB Username:	      <input type="text" name="username" value="username">
  DB Password:	      <input type="password" name="password" value="password">

<!--  Language: <select name="language">
   <option value="US-English">US-English
  </select><br />-->
  <input type="submit" value="Submit">
 </form>
</pre>
<?php
}

printfooter();

?>