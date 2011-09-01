<?php

/* Change urls to clickable links, and change newlines to br-tags. */
function mangle_quote_text($txt)
{
    $txt = preg_replace('/((http|ftp):\/\/([\w\d\-]+)(\.[\w\d\-]+){1,})([\/\?\w\d\.=&+%~_\-]+(#[\w\d_]+)?)?/', '<A href="\\1\\5">\\1\\5</A>', $txt);
    $txt = nl2br($txt);
    return $txt;
}

function get_number_limit($param, $min, $max)
{
    if (isset($param) && preg_match('/^[0-9]+$/', $param)) {
	$limit = (int)$param;
	if ($limit < $min) $limit = $min;
	else if ($limit > $max) $limit = $max;
    } else $limit = $max;
    return $limit;
}

function db_tablename($name)
{
    include 'settings.php';
    return $CONFIG['db_table_prefix'].'_'.$name;
}

function urlargs($ar1, $ar2 = null, $ar3 = null)
{
    include 'settings.php';
    if ($ar2 === null) return $ar1;
    if ($ar3 === null) return implode($CONFIG['GET_SEPARATOR_HTML'], array($ar1, $ar2));
    return implode($CONFIG['GET_SEPARATOR_HTML'], array($ar1, $ar2, $ar3));
}

function autologin()
{
    if (isset($_COOKIE['user']) && isset($_COOKIE['passwd']) && isset($_COOKIE['userid'])) {
	global $db;
	$pass = $_COOKIE['passwd'];
	$user = $_COOKIE['user'];
	$userid = $_COOKIE['userid'];

	$res =& $db->query("SELECT * FROM ".db_tablename('users')." WHERE id=".$db->quote((int)$userid)." AND user=".$db->quote($user));
	if (DB::isError($res)) return;
	$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
	if (!isset($row['password'])) return;
	$passchk = md5($row['password'].$row['salt']);
	if ($pass == $passchk) {
	    $_SESSION['user'] = $row['user'];
	    $_SESSION['level'] = $row['level'];
	    $_SESSION['userid'] = $row['id'];
	    $_SESSION['logged_in'] = 1;
	    mk_cookie('user', $row['user']);
	    mk_cookie('userid', $row['id']);
	    mk_cookie('passwd', $passchk);
	}
    }
}

/* $row = array with keys 'user', 'id', 'level', 'password', 'salt' */
function set_user_logged($row)
{
    $_SESSION['user'] = $row['user'];		// site-wide accessible username
    $_SESSION['level'] = $row['level'];		// site-wide accessible level
    $_SESSION['userid'] = $row['id'];
    $_SESSION['logged_in'] = 1;				// site-wide accessible login variable

    if (isset($_POST['remember_login'])) {
	mk_cookie('user', $row['user']);
	mk_cookie('userid', $row['id']);
	mk_cookie('passwd', md5($row['password'].$row['salt']));
    }

    header("Location: http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']));
}

function set_user_logout()
{
    session_unset($_SESSION['user']);
    session_unset($_SESSION['logged_in']);
    session_unset($_SESSION['level']);
    session_unset($_SESSION['userid']);
    mk_cookie('user');
    mk_cookie('userid');
    mk_cookie('passwd');
    header('Location: http://' . $_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']));
}

function set_voteip($salt)
{
    if (isset($_SESSION['voteip'])) {
	if (!isset($_COOKIE['voteip'])) {
	    $addr = $_SESSION['voteip'];
	    mk_cookie('voteip', $addr . '-' . md5($addr . $salt));
	    $_SESSION['voteip'] = $addr;
	}
    } else {
	if (isset($_COOKIE['voteip'])) {
	    $arr = explode('-', $_COOKIE['voteip'], 2);
	    $addr = $arr[0];
	    $hash = $arr[1];
	    if (preg_match("/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/", $addr)) {
		if (md5($addr . $salt) != $hash)
		    $addr = getenv("REMOTE_ADDR");
		mk_cookie('voteip', $addr . '-' . md5($addr . $salt));
		$_SESSION['voteip'] = $addr;
	    } else {
		/* illegal ip in cookie */
		$addr = getenv("REMOTE_ADDR");
		mk_cookie('voteip', $addr . '-' . md5($addr . $salt));
		$_SESSION['voteip'] = $addr;
	    }
	} else {
	    $addr = getenv("REMOTE_ADDR");
	    mk_cookie('voteip', $addr . '-' . md5($addr . $salt));
	    $_SESSION['voteip'] = $addr;
	}
    }
}

function write_settings($fname, $data)
{
    $fp = fopen($fname,"w");
    $str = "<?php\n";
    if ($data)
	foreach ($data as $key=>$val) {
	    $str .= '$CONFIG[\''.$key.'\'] = '.$val.";\n";
	}
    if (fwrite($fp, $str, strlen($str)) === FALSE) {
	return FALSE;
    }
    return TRUE;
}

function mk_cookie($name, $data = null)
{
    if ($data) {
        setcookie($name, $data, time()+3600*24*365, '/');
        $_COOKIE[$name] = $data;
    } else {
        setcookie($name, '', time()-3600, '/');
        unset($_COOKIE[$name]);
    }
}




/**
 * Return a random string
 *
 * @author       Aidan Lister <aidan@php.net>
 * @version      2.0
 * @param        int     $length  Length of the string you want generated
 * @param        string  $seeds   The seeds you want the string to be generated from
 */
function str_rand($length = 8, $seeds = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890')
{
    $str = '';
    $seeds_count = strlen($seeds);

    // Seed
    list($usec, $sec) = explode(' ', microtime());
    $seed = (float) $sec + ((float) $usec * 100000);
    mt_srand($seed);

    // Generate
    for ($i = 0; $length > $i; $i++) {
        $str .= $seeds{mt_rand(0, $seeds_count - 1)};
    }

    return $str;
}

function is_lang($str)
{
    global $lang;
    if (isset($lang[$str])) return TRUE;
    return FALSE;
}

function lang($str)
{
    global $lang;
    if (isset($lang[$str])) return $lang[$str];
    return $str;
}

function title($title)
{
    global $CONFIG, $lang;
    $str = ($CONFIG['prefix_short_title'] ? $CONFIG['site_short_title'].': ' : '');
    if (preg_match('/^[0-9]+$/', $title)) $str .= sprintf(lang('pagetitle_quotenum'), $title);
    else if (is_lang('pagetitle_'.$title)) $str .= lang('pagetitle_'.$title);
    else $str .= $CONFIG['site_long_title'];
    return $str;
}

