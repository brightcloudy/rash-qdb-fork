<?php
/*
error_reporting(E_ALL);
ini_set('display_errors','On');
*/

define('USER_SUPERUSER', 1);
define('USER_ADMIN', 2);
define('USER_MOD', 3);
define('USER_NORMAL', 4);


if (!file_exists('settings.php')) {
    header("Location: install.php");
    exit;
}

session_start();

require_once 'DB.php';

require('settings.php');

if (!isset($CONFIG['quote_list_limit']) || !is_int($CONFIG['quote_list_limit'])) $CONFIG['quote_list_limit'] = 50;
if (!isset($CONFIG['rss_entries']) || ($CONFIG['rss_entries'] < 1)) $CONFIG['rss_entries'] = 15;

require('util_funcs.php');

require("language/{$CONFIG['language']}.lng");

require('basecaptcha.php');
require("captcha/{$CONFIG['captcha']}.php");

$CAPTCHA->init_settings($CONFIG['use_captcha']);

require('basetemplate.php');
require($CONFIG['template']);

date_default_timezone_set($CONFIG['timezone']);

if (isset($_COOKIE['lastvisit']) && !isset($_SESSION['lastvisit'])) {
    $_SESSION['lastvisit'] = $_COOKIE['lastvisit'];
}
mk_cookie('lastvisit', mktime());

set_voteip($CONFIG['secret_salt']);

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
    $TEMPLATE->printheader();
    print $db->getMessage();
    $TEMPLATE->printfooter();
    exit;
}

autologin();

$mainmenu = array(array('url' => './', 'id' => 'site_nav_home', 'txt' => 'menu_home'),
		  array('url' => '?latest', 'id' => 'site_nav_latest', 'txt' => 'menu_latest'),
		  array('url' => '?browse', 'id' => 'site_nav_browse', 'txt' => 'menu_browse'),
		  array('url' => '?random', 'id' => 'site_nav_random', 'txt' => 'menu_random'),
		  array('url' => '?random2', 'id' => 'site_nav_random2', 'txt' => 'menu_random2'),
		  array('url' => '?bottom', 'id' => 'site_nav_bottom', 'txt' => 'menu_bottom'),
		  array('url' => '?top', 'id' => 'site_nav_top', 'txt' => 'menu_top'));

if (isset($CONFIG['public_queue']) && ($CONFIG['public_queue'] == 1) &&
    isset($CONFIG['moderated_quotes']) && ($CONFIG['moderated_quotes'] == 1)) {
    $mainmenu[] = array('url' => '?queue', 'id' => 'site_nav_queue', 'txt' => 'menu_queue');
}

$mainmenu[] = array('url' => '?search', 'id' => 'site_nav_search', 'txt' => 'menu_search');

if ((isset($CONFIG['login_required']) && ($CONFIG['login_required'] == 1) && isset($_SESSION['logged_in']))
    || !isset($CONFIG['login_required']) || ($CONFIG['login_required'] == 0))
    $mainmenu[] = array('url' => '?add', 'id' => 'site_nav_add', 'txt' => 'menu_contribute');

if (isset($CONFIG['login_required']) && ($CONFIG['login_required'] == 1)) {
    if (!isset($_SESSION['logged_in'])) {
	$mainmenu[] = array('url' => '?login', 'id' => 'site_nav_login', 'txt' => 'menu_login');
    } else {
	$mainmenu[] = array('url' => '?logout', 'id' => 'site_nav_logout', 'txt' => 'menu_logout');
    }
}


if (isset($_SESSION['logged_in'])) {
    $adminmenu = array();
    if ($_SESSION['level'] < USER_NORMAL) {
	$adminmenu[] = array('url' => '?queue', 'id' => 'site_admin_nav_queue', 'txt' => 'menu_queue');
	$adminmenu[] = array('url' => '?flag_queue', 'id' => 'site_admin_nav_flagged', 'txt' => 'menu_flagged');
    }
    if ($_SESSION['level'] <= USER_ADMIN) {
	$adminmenu[] = array('url' => '?add_news', 'id' => 'site_admin_nav_add-news', 'txt' => 'menu_addnews');
	$adminmenu[] = array('url' => '?edit_news', 'id' => 'site_admin_nav_edit-news', 'txt' => 'menu_editnews');
    }
    if ($_SESSION['level'] <= USER_SUPERUSER) {
	$adminmenu[] = array('url' => '?users', 'id' => 'site_admin_nav_users', 'txt' => 'menu_users');
	$adminmenu[] = array('url' => '?add_user', 'id' => 'site_admin_nav_add-user', 'txt' => 'menu_adduser');
    }
    $adminmenu[] = array('url' => '?change_pw', 'id' => 'site_admin_nav_change-password', 'txt' => 'menu_changepass');
    $adminmenu[] = array('url' => '?logout', 'id' => 'site_admin_nav_logout', 'txt' => 'menu_logout');
} else $adminmenu = null;

$TEMPLATE->set_menu(0, $mainmenu);
$TEMPLATE->set_menu(1, $adminmenu);


function get_db_stats()
{
    global $db;

    $ret['pending_quotes'] = $db->getOne('select count(id) from '.db_tablename('quotes').' where queue=1');
    $ret['approved_quotes'] = $db->getOne('SELECT COUNT(id) FROM '.db_tablename('quotes').' where queue=0');

    return $ret;
}

function handle_captcha($type, $func, &$param=null)
{
    global $CAPTCHA, $TEMPLATE;
    switch ($CAPTCHA->check_CAPTCHA($type)) {
    case 0:
	if (is_callable($func)) return call_user_func($func, $param);
	break;
    case 1: $TEMPLATE->add_message(lang('captcha_wronganswer'));
	break;
    case 2: $TEMPLATE->add_message(lang('captcha_wrongid'));
	break;
    default: break;
    }
    return FALSE;
}

function rash_rss()
{
    global $db, $CONFIG, $TEMPLATE;
    $query = "SELECT * FROM ".db_tablename('quotes')." WHERE queue=0 ORDER BY id DESC LIMIT ".$CONFIG['rss_entries'];
    $res =& $db->query($query);
    $items = '';
    while($row=$res->fetchRow(DB_FETCHMODE_ASSOC)) {
	$title = $CONFIG['rss_url']."/?".$row['id'];
	$desc = mangle_quote_text(htmlspecialchars($row['quote']));
	$items .= $TEMPLATE->rss_feed_item($title, $desc, $title);
    }
    print $TEMPLATE->rss_feed($CONFIG['rss_title'], $CONFIG['rss_desc'], $CONFIG['rss_url'], $items);
}

function flag_do_inner($row)
{
    global $TEMPLATE, $db;
    if($row['flag'] == 2){
	$TEMPLATE->add_message(lang('flag_previously_flagged'));
    }
    elseif($row['flag'] == 1){
	$TEMPLATE->add_message(lang('flag_currently_flagged'));
    }
    else{
	$TEMPLATE->add_message(lang('flag_quote_flagged'));
	$db->query("UPDATE ".db_tablename('quotes')." SET flag = 1 WHERE id = ".$db->quote((int)$row['id']));
	$row['flag'] = 1;
    }
    return $row;
}

function flag($quote_num, $method)
{
    global $CONFIG, $TEMPLATE, $CAPTCHA, $db;

    $res =& $db->query("SELECT id,flag,quote FROM ".db_tablename('quotes')." WHERE id = ".$db->quote((int)$quote_num)." LIMIT 1");
    $row = $res->fetchRow(DB_FETCHMODE_ASSOC);

    if ($method == 'verdict') {
	$row = handle_captcha('flag', 'flag_do_inner', $row);
    } else {
	if($row['flag'] == 2){
	    $TEMPLATE->add_message(lang('flag_previously_flagged'));
	}
	elseif($row['flag'] == 1){
	    $TEMPLATE->add_message(lang('flag_currently_flagged'));
	}
    }
    print $TEMPLATE->flag_page($quote_num, mangle_quote_text($row['quote']), $row['flag']);
}

// function vote($quote_num, $method)
// This function increments or decrements the rating of the quote in quotes.
//
function vote($quote_num, $method)
{
    global $db, $TEMPLATE;

    $qid = $db->getOne("SELECT quote_id FROM ".db_tablename('tracking')." WHERE user_ip=".$db->quote($_SESSION['voteip']).' AND quote_id='.$db->quote((int)$quote_num));
    if (isset($qid) && $qid == $quote_num) {
	$TEMPLATE->add_message(lang('tracking_check_2'));
	return;
    }

    $vote = 0;
    if ($method == "plus") {
	$vote = 1;
	$db->query("UPDATE ".db_tablename('quotes')." SET rating = rating+1 WHERE id = ".$db->quote((int)$quote_num));
    } elseif ($method == "minus") {
	$vote = -1;
	$db->query("UPDATE ".db_tablename('quotes')." SET rating = rating-1 WHERE id = ".$db->quote((int)$quote_num));
    }
    if ($vote != 0) {
	$res = $db->query("INSERT INTO ".db_tablename('tracking')." (user_ip, quote_id, vote) VALUES(".$db->quote($_SESSION['voteip']).", ".$db->quote($quote_num).", ".$vote.")");
	$TEMPLATE->add_message(lang('tracking_check_1'));
    }
}



// home_generation()
//
// Generates the page that shows up when there are none or invalid URL arguments,
// the default page, can be used to show the general idea of the site, and/or
// used for news updates, either can be turned off in rash_settings.php
// in the rash/templates/rash_template folder.
//
// The greeting div has a variable named $home_greeting in it, this variable
// should be assigned to a greeting, although anything you want can do.
//
function home_generation()
{
    global $db, $TEMPLATE, $CONFIG;

    $res =& $db->query("SELECT * FROM ".db_tablename('news')." ORDER BY date desc LIMIT 5");
    if(DB::isError($res)){
	die($res->getMessage());
    }

    $news = '';

    while ($row=$res->fetchRow(DB_FETCHMODE_ASSOC)) {
	$news .= $TEMPLATE->news_item($row['news'], date($CONFIG['news_time_format'], $row['date']));
    }

    print $TEMPLATE->main_page($news);
}

/************************************************************************
************************************************************************/

function page_numbers($origin, $quote_limit, $page_default, $page_limit)
{
    global $CONFIG, $db;
    $numrows = $db->getOne("SELECT COUNT(id) FROM ".db_tablename('quotes').' WHERE queue=0');
    $testrows = $numrows;

    $pagenum = 0;

    $ret = '';

    do {
	$pagenum++;
        $testrows -= $quote_limit;
    } while ($testrows > 0);

    if(!($page_limit % 2))
	$page_limit += 1;

    if(($page_limit == 1) || ($page_limit < 0) || (!$page_limit))
	$page_limit = 5;

    $page_base = 0;
    do {
	$page_base++;
	$page_limit -= 2;
    } while ($page_limit > 1);
    $ret .= "<div class=\"quote_pagenums\">";
    $ret .= "<a href=\"?".urlargs(strtolower($origin),'1')."\">".lang('page_first')."</a>&nbsp;&nbsp;";
    $ret .= "<a href=\"?".urlargs(strtolower($origin),
					     ((($page_default-10) > 1) ? ($page_default-10) : (1)))
		."\">-10</a>&nbsp;&nbsp;";

    if (($page_default - $page_base) > 1) {
	$ret .= "&nbsp;...&nbsp;";
    }
    $x = ($page_default - $page_base);

    do {
	if($x > 0)
	    $ret .= "&nbsp;<a href=\"?".urlargs(strtolower($origin),$x)."\">${x}</a>&nbsp;";
	$x++;
    } while ($x < $page_default);

    $ret .= "&nbsp;${page_default}&nbsp;";

    $x = ($page_default + 1);

    do {
	if($x <= $pagenum)
	    $ret .= "&nbsp;<a href=\"?".urlargs(strtolower($origin),$x)."\">${x}</a>&nbsp;";
	$x++;
    } while ($x < ($page_default + $page_base + 1));

    if (($page_default + $page_base) < $pagenum) {
	$ret .= "&nbsp;...&nbsp;";
    }

    $ret .= "&nbsp;&nbsp;<a href=\"?".urlargs(strtolower($origin),
						   ((($page_default+10) < $pagenum) ? ($page_default+10) : ($pagenum)))
		."\">+10</a>&nbsp;&nbsp;";

    $ret .= "&nbsp;&nbsp;<a href=\"?".urlargs(strtolower($origin),$pagenum)."\">".lang('page_last')."</a>";
    $ret .= "</div>\n";
    return $ret;
}


function edit_quote_button($quoteid)
{
    global $TEMPLATE;
    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] <= USER_ADMIN)) {
	return $TEMPLATE->edit_quote_button($quoteid);
    }
    return '';
}

function user_can_vote_quote($quoteid)
{
    global $CONFIG, $db;

    $res =& $db->query('select vote from '.db_tablename('tracking').' where user_ip='.$db->quote($_SESSION['voteip']).' AND quote_id='.$db->quote((int)$quoteid));
    if (DB::isError($res)) {
	die('user_can_vote_quote():'.$res->getMessage());
    }
    $row = $res->fetchRow(DB_FETCHMODE_ASSOC);

    if (isset($CONFIG['login_required']) && ($CONFIG['login_required'] == 1) && !isset($_SESSION['logged_in']))
	return 2;

    if (isset($row['vote']) && $row['vote']) return 1;
    return 0;
}


/************************************************************************
************************************************************************/

// quote_generation()
//
// This is the rugged function that pulls quotes out of the quotes table
// on the database and presents them to the viewer.
//
// The $query variable is usually gotten from index.php (anyplace can call this
// function) and is a string containing the database query to be used to retrieve
// information from the database.
//
// Keep in mind that this query should be able to be used in a numerous amount of
// databases because of PEAR::DB.
//
function quote_generation($query, $origin, $page = 1, $quote_limit = 50, $page_limit = 10)
{
    global $CONFIG, $TEMPLATE, $db;
    $pagenums = '';
    if ($page != -1) {
	if(!$page)
	    $page = 1;
	$pagenums = page_numbers($origin, $quote_limit, $page, $page_limit);
    }
    $up_lim = ($quote_limit * $page);
    $low_lim = $up_lim - $quote_limit;
    if($page != -1){
	$query .= "LIMIT $low_lim,$quote_limit";
    }

    $res =& $db->query($query);
    if (DB::isError($res)) {
	die($res->getMessage());
    }

    $nquotes = 0;
    $inner = '';
    while($row=$res->fetchRow(DB_FETCHMODE_ASSOC)){
	$nquotes++;
	$canvote = user_can_vote_quote($row['id']);
	$datefmt = date($CONFIG['quote_time_format'], $row['date']);
	$inner .= $TEMPLATE->quote_iter($row['id'], $row['rating'], mangle_quote_text($row['quote']), ($row['flag'] == 0), $canvote, $datefmt);
    }

    if (!$nquotes)
	$TEMPLATE->add_message(lang('no_quote'));

    print $TEMPLATE->quote_list($origin, $pagenums, $inner);
}


function edit_news($method, $id)
{
    global $CONFIG, $TEMPLATE, $db;
    $news = '';

    if ($method == 'edit') {
	$res =& $db->query("SELECT * FROM ".db_tablename('news')." where id=".$db->quote((int)$id));
	$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
	$newstxt = preg_replace('/\<br \/\>/', '', $row['news']);
	$news = $TEMPLATE->edit_news_form($row['id'], $newstxt);
    } else if ($method == 'update') {
	if (isset($_POST['preview'])) {
	    $newstxt = nl2br(trim($_POST['news']));
	    $news = $TEMPLATE->news_item($newstxt, date($CONFIG['news_time_format'], mktime()));
	    $newstxt = preg_replace('/\<br \/\>/', '', $newstxt);
	    $news .= $TEMPLATE->edit_news_form($id, $newstxt);
	} else if (isset($_POST['delete'])) {
	    if (isset($_POST['verify_delete'])) {
		$res =& $db->query("DELETE FROM ".db_tablename('news')." where id=".$db->quote((int)$id));
		$TEMPLATE->add_message(lang('news_item_deleted'));
	    } else {
		$newstxt = trim($_POST['news']);
		$news .= $TEMPLATE->edit_news_form($id, $newstxt);
		$TEMPLATE->add_message(lang('news_item_delete_no_verify'));
	    }
	} else {
	    $newstxt = nl2br(trim($_POST['news']));
	    $db->query("UPDATE ".db_tablename('news')." SET news=".$db->quote($newstxt)." WHERE id=".$db->quote((int)$id));
	    $TEMPLATE->add_message(lang('news_item_saved'));
	    $id = null;
	}
    }

    $res =& $db->query("SELECT * FROM ".db_tablename('news')." ORDER BY date DESC");
    while ($row=$res->fetchRow(DB_FETCHMODE_ASSOC)) {
	$mode = 1;
	if ($row['id'] == $id) $mode = 2;
	$news .= $TEMPLATE->news_item($row['news'], date($CONFIG['news_time_format'], $row['date']), $row['id'], $mode);
    }

    print $TEMPLATE->edit_news_page($news);
}


function add_news($method)
{
    global $CONFIG, $TEMPLATE, $db;
    $innerhtml = null;
    $rawnews = '';
    if($method == 'submit') {
	$rawnews = trim($_POST['news']);
	$news = nl2br($rawnews);
	if (isset($_POST['preview'])) {
	    $innerhtml = $TEMPLATE->news_item($news, date($CONFIG['news_time_format'], mktime()));
	} else {
	    $db->query("INSERT INTO ".db_tablename('news')." (news,date) VALUES(".$db->quote($news).", '".mktime()."');");
	    $TEMPLATE->add_message(lang('news_added'));
	    $rawnews = '';
	}
    }

    print $TEMPLATE->add_news_page($innerhtml, htmlspecialchars($rawnews));
}

function user_level_select($selected=USER_MOD, $id='admin_add-user_level')
{
    $lvls = array(USER_SUPERUSER => 'superuser',
		  USER_ADMIN => 'administrator',
		  USER_MOD => 'moderator',
		  USER_NORMAL => 'normal user');

    $str = '<select name="level" size="1" id="'.$id.'">';

    foreach ($lvls as $key => $val) {
	$str .= '<option value="'.$key.'"';
	if ($key == $selected) $str .= ' selected';
	$str .= '>'.$key.' - '.$val.'</option>';
    }

    $str .= '</select>';
    return $str;
}

function username_exists($name)
{
    global $db;
    $name = strtolower($name);
    $ret = $db->getOne('select count(1) from '.db_tablename('users').' where LOWER(user)='.$db->quote($name));
    if ($ret > 0) return TRUE;
    return FALSE;
}

function check_username($username)
{
    global $TEMPLATE;
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
	$TEMPLATE->add_message(lang('username_illegal_chars'));
    } else if (strlen($username) < 2) {
	$TEMPLATE->add_message(lang('username_too_short'));
    } else if (strlen($username) > 20) {
	$TEMPLATE->add_message(lang('username_too_long'));
    } else if (username_exists($username)) {
	$TEMPLATE->add_message(lang('username_exists'));
    } else {
	return TRUE;
    }
    return FALSE;
}

function register_user_do_inner($row)
{
    global $db, $TEMPLATE;
    $username = $row['username'];
    $password = $row['password'];
    $salt = str_rand();
    $level = USER_NORMAL;
    $res =& $db->query("INSERT INTO ".db_tablename('users')." (user, password, level, salt) VALUES(".$db->quote($username).", '".crypt($password, "\$1\$".substr($salt, 0, 8)."\$")."', ".$db->quote((int)$level).", '\$1\$".$salt."\$');");
    if (DB::isError($res)) {
	$TEMPLATE->add_message($res->getMessage());
    } else $TEMPLATE->add_message(sprintf(lang('user_added'), htmlspecialchars($username)));

    $res =& $db->query("SELECT * FROM ".db_tablename('users')." WHERE user=".$db->quote($username));
    $row = $res->fetchRow(DB_FETCHMODE_ASSOC);
    set_user_logged($row);
    return $row;
}

function register_user($method)
{
    global $CONFIG, $TEMPLATE, $db;
    if ($method == 'update') {
	$username = trim($_POST['username']);
	if (check_username($username)) {
	    if ($_POST['verifypassword'] == $_POST['password']) {
		$row = array('username' => $username, 'password' => $_POST['password']);
		$row = handle_captcha('register_user', 'register_user_do_inner', $row);
	    } else $TEMPLATE->add_message(lang('password_verification_mismatch'));
	}
    }
    print $TEMPLATE->register_user_page();
}

function add_user($method)
{
    global $CONFIG, $TEMPLATE, $db;
    if ($method == 'update') {
	$username = trim($_POST['username']);
	if (check_username($username)) {
	    $res =& $db->query("INSERT INTO ".db_tablename('users')." (user, password, level, salt) VALUES(".$db->quote($username).", '".crypt($_POST['password'], "\$1\$".substr($_POST['salt'], 0, 8)."\$")."', ".$db->quote((int)$_POST['level']).", '\$1\$".$_POST['salt']."\$');");
	    if (DB::isError($res)) {
		$TEMPLATE->add_message($res->getMessage());
	    } else $TEMPLATE->add_message(sprintf(lang('user_added'), htmlspecialchars($username)));
	}
    }

    print $TEMPLATE->add_user_page();
}

function change_pw($method, $who)
{
    global $CONFIG, $TEMPLATE, $db;
    if ($method == 'update') {
	// created to keep errors at a minimum
	$row['salt'] = 0;

	$res =& $db->query("SELECT `password`, salt FROM ".db_tablename('users')." WHERE id=".$db->quote((int)$who));
	$row = $res->fetchRow(DB_FETCHMODE_ASSOC);

	$salt = "\$1\$".str_rand()."\$";
	if ($_POST['new_password'] == '') {
	    $TEMPLATE->add_message(lang('password_empty'));
	} else {
	    if((md5($_POST['old_password']) == $row['password']) || (crypt($_POST['old_password'], $row['salt']) == $row['password'])){
		if($_POST['verify_password'] == $_POST['new_password']){
		    $db->query("UPDATE ".db_tablename('users')." SET `password`='".crypt($_POST['new_password'], $salt)."', salt='".$salt."' WHERE id=".$db->quote((int)$who));
		    $TEMPLATE->add_message(lang('password_updated'));
		} else $TEMPLATE->add_message(lang('password_verification_mismatch'));
	    } else $TEMPLATE->add_message(lang('password_old_mismatch'));
	}
    };

    print $TEMPLATE->change_password_page();
}

function edit_users($method, $who)
{
    global $CONFIG, $TEMPLATE, $db;
    if ($method == 'delete') {	// delete a user from users
	if (isset($_POST['verify'])) {
	    $res =& $db->query("SELECT * FROM ".db_tablename('users'));
	    while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		if(isset($_POST['d'.$row['id']])){
		    $db->query("DELETE FROM ".db_tablename('users')." WHERE id='{$_POST['d'.$row['id']]}'");
		    $TEMPLATE->add_message(sprintf(lang('user_removed'), htmlspecialchars($row['user'])));
		}
	    }
	}
    } else if ($method == 'update') {	// parse the info from $method == 'edit' into the database
	$user = trim($_POST['user']);
	if (check_username($user)) {
	    $db->query("UPDATE ".db_tablename('users')." SET user=".$db->quote($user).", level=".$db->quote((int)$_POST['level'])." WHERE id=".$db->quote((int)$who));
	    if($_POST['password']) {
		$salt = "\$1\$".str_rand()."\$";
		$db->query("UPDATE ".db_tablename('users')." SET `password`='".crypt($_POST['password'], $salt)."', salt='".$salt."' WHERE id=".$db->quote((int)$who));
	    }
	}
    } else if ($method == 'edit') {
	$res =& $db->query("SELECT * FROM ".db_tablename('users')." WHERE id=".$db->quote((int)$who));
	$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
	if (isset($row['user']))
	    print $TEMPLATE->edit_user_page_form($row['id'], $who, htmlspecialchars($row['user']), $row['level']);
    }

    $innerhtml = '';

    $res =& $db->query("SELECT * FROM ".db_tablename('users')." ORDER BY level asc, user desc");
    while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	$innerhtml .= $TEMPLATE->edit_user_page_table_row($row['id'], htmlspecialchars($row['user']), htmlspecialchars($row['password']), $row['level']);
    }
    print $TEMPLATE->edit_user_page_table($innerhtml);
}

function userlogin($method)
{
    global $CONFIG, $TEMPLATE, $db;
    if ($method == 'login') {
	$res =& $db->query("SELECT salt FROM ".db_tablename('users')." WHERE LOWER(user)=".$db->quote(strtolower($_POST['rash_username'])));
	$salt = $res->fetchRow(DB_FETCHMODE_ASSOC);

	// if there is no presence of a salt, it is probably md5 since old rash used plain md5
	if(!$salt['salt']){
	    $res =& $db->query("SELECT * FROM ".db_tablename('users')." WHERE LOWER(user)=".$db->quote(strtolower($_POST['rash_username']))." AND `password` ='".md5($_POST['rash_password'])."'");
	    $row = $res->fetchRow(DB_FETCHMODE_ASSOC);
	}
	// if there is presense of a salt, it is probably new rash passwords, so it is salted md5
	else{
	    $res =& $db->query("SELECT * FROM ".db_tablename('users')." WHERE LOWER(user)=".$db->quote(strtolower($_POST['rash_username']))." AND `password` ='".crypt($_POST['rash_password'], $salt['salt'])."'");
	    $row = $res->fetchRow(DB_FETCHMODE_ASSOC);
	}

	// if there is no row returned for the user, the password is expected to be false because of the AND conditional in the query
	if(!$row['user']){
	    $TEMPLATE->add_message(lang('login_error'));
	} else {
	    set_user_logged($row);
	}
    }
    print $TEMPLATE->user_login_page();
}

function adminlogin($method)
{
    global $CONFIG, $TEMPLATE, $db;
    if ($method == 'login') {
	$res =& $db->query("SELECT salt FROM ".db_tablename('users')." WHERE LOWER(user)=".$db->quote(strtolower($_POST['rash_username'])));
	$salt = $res->fetchRow(DB_FETCHMODE_ASSOC);

	// if there is no presence of a salt, it is probably md5 since old rash used plain md5
	if(!$salt['salt']){
	    $res =& $db->query("SELECT * FROM ".db_tablename('users')." WHERE LOWER(user)=".$db->quote(strtolower($_POST['rash_username']))." AND `password` ='".md5($_POST['rash_password'])."'");
	    $row = $res->fetchRow(DB_FETCHMODE_ASSOC);
	}
	// if there is presense of a salt, it is probably new rash passwords, so it is salted md5
	else{
	    $res =& $db->query("SELECT * FROM ".db_tablename('users')." WHERE LOWER(user)=".$db->quote(strtolower($_POST['rash_username']))." AND `password` ='".crypt($_POST['rash_password'], $salt['salt'])."'");
	    $row = $res->fetchRow(DB_FETCHMODE_ASSOC);
	}

	// if there is no row returned for the user, the password is expected to be false because of the AND conditional in the query
	if(!$row['user']){
	    $TEMPLATE->add_message(lang('login_error'));
	} else {
	    set_user_logged($row);
	}
    }
    print $TEMPLATE->admin_login_page();
}


function quote_queue($method)
{
    global $CONFIG, $TEMPLATE, $db;
    if ($method == 'judgement') {
	$res =& $db->query("SELECT * FROM ".db_tablename('quotes').' where queue=1');
	$x = 0;
	while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
	    if ($_POST['q'.$row['id']]) {
		$judgement_array[$x] = $_POST['q'.$row['id']];
		$x++;
	    }
	}
	$x = 0;
	while ($judgement_array[$x]) {
	    if(substr($judgement_array[$x], 0, 1) == 'y'){
		$db->query("UPDATE ".db_tablename('quotes')." SET queue=0 WHERE id =".$db->quote((int)substr($judgement_array[$x], 1)));
		$TEMPLATE->add_message(sprintf(lang('quote_accepted'), substr($judgement_array[$x], 1)));
	    } else {
		$db->query("DELETE FROM ".db_tablename('quotes')." WHERE queue=1 AND id =".$db->quote((int)substr($judgement_array[$x], 1)));
		$TEMPLATE->add_message(sprintf(lang('quote_deleted'), substr($judgement_array[$x], 1)));
	    }
	    $x++;
	}
    }

    $res =& $db->query("SELECT * FROM ".db_tablename('quotes')." WHERE queue=1 order by id asc");
    if (DB::isError($res)){
	die($res->getMessage());
    }

    $innerhtml = '';
    $x = 0;
    while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	$innerhtml .= $TEMPLATE->quote_queue_page_iter($row['id'], mangle_quote_text($row['quote']));
	$x++;
    }

    print $TEMPLATE->quote_queue_page($innerhtml);
}


// flag_queue($method)
//
//

function flag_queue($method)
{
    global $CONFIG, $TEMPLATE, $db;
	if($method == 'judgement'){

	    if (isset($_POST['do_all']) && ($_POST['do_all'] == 'on')) {
		if (isset($_POST['unflag_all'])) {
		    $db->query("UPDATE ".db_tablename('quotes')." SET flag=2 WHERE flag=1");
		    $TEMPLATE->add_message(lang('unflagged_all'));
		} else if (isset($_POST['delete_all'])) {
		    $db->query("DELETE FROM ".db_tablename('quotes')." WHERE flag=1");
		    $TEMPLATE->add_message(lang('deleted_all'));
		}
	    }

	    $res =& $db->query("SELECT * FROM ".db_tablename('quotes')." WHERE flag = 1");

	    $x = 0;
	    while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
		if (isset($_POST['q'.$row['id']])) {
		    $judgement_array[$x] = $_POST['q'.$row['id']];
		    $x++;
		}
	    }

	    $x = 0;
	    while (isset($judgement_array[$x])) {
		if(substr($judgement_array[$x], 0, 1) == 'u'){
		    $db->query("UPDATE ".db_tablename('quotes')." SET flag = 2 WHERE id =".$db->quote((int)substr($judgement_array[$x], 1)));
		    $TEMPLATE->add_message(sprintf(lang('quote_unflagged'), substr($judgement_array[$x], 1)));
		}
		if(substr($judgement_array[$x], 0, 1) == 'd'){
		    $db->query("DELETE FROM ".db_tablename('quotes')." WHERE id=".$db->quote((int)substr($judgement_array[$x], 1)));
		    $TEMPLATE->add_message(sprintf(lang('quote_deleted'), substr($judgement_array[$x], 1)));
		}
		$x++;
	    }
	}

	$res =& $db->query("SELECT * FROM ".db_tablename('quotes')." WHERE flag = 1 ORDER BY id ASC");

	$innerhtml = '';

	$x = 0;
	while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	    $innerhtml .= $TEMPLATE->flag_queue_page_iter($row['id'], mangle_quote_text($row['quote']));
	    $x++;
	}

	print $TEMPLATE->flag_queue_page($innerhtml);
}


// search($method)
// This takes a user to the page where they can put words in to search for
// quotes with those words in it. Pretty simple.
//

function search($method, $searchparam=null)
{
    global $CONFIG, $TEMPLATE, $db;
    if ($method == 'fetch' || isset($searchparam)) {
	$method = 'fetch';

	$search = (isset($_POST['search']) ? $_POST['search'] : $searchparam);

	if (preg_match('/^#[0-9]+$/', trim($search))) {
	    $exactmatch = ' or id='.substr(trim($search), 1);
	} else {
	    $exactmatch = '';
	}

	$sortby = (isset($_POST['sortby']) ? $_POST['sortby'] : 'rating');
	$sortby = preg_replace('/[^a-zA-Z0-9]+/', '', $sortby);

	if ($sortby == 'rating')
	    $how = 'desc';
	else
	    $how = 'asc';

	$limit = (isset($_POST['number']) ? $_POST['number'] : 10);

	$searchx = '%'.$search.'%';

	$query = "SELECT * FROM ".db_tablename('quotes')." WHERE queue=0 and (quote LIKE ".$db->quote($searchx).$exactmatch.") ORDER BY ".$sortby." $how LIMIT ".$db->quote((int)$limit);

	quote_generation($query, lang('search_results_title'), -1);
    }

    print $TEMPLATE->search_quotes_page(($method == 'fetch'), htmlspecialchars($search));
}

function edit_quote($method, $quoteid)
{
    global $CONFIG, $TEMPLATE, $db;

    if (!isset($_SESSION['logged_in']) || ($_SESSION['level'] > USER_ADMIN)) return;

    $innerhtml = '';

    if ($method == 'submit') {

	$quotxt = htmlspecialchars(trim($_POST["rash_quote"]));

	$innerhtml = $TEMPLATE->edit_quote_outputmsg(mangle_quote_text($quotxt));

	$res =& $db->query("UPDATE ".db_tablename('quotes')." SET quote=".$db->quote($quotxt)." WHERE id=".$db->quote($quoteid));
	if(DB::isError($res)){
	    die($res->getMessage());
	}
    } else {
	$quotxt = $db->getOne("SELECT quote FROM ".db_tablename('quotes')." WHERE id=".$db->quote($quoteid));
    }

    print $TEMPLATE->edit_quote_page($quoteid, $quotxt, $innerhtml);
}


function add_quote_do_inner()
{
    global $CONFIG, $TEMPLATE, $db;
    $flag = (isset($CONFIG['auto_flagged_quotes']) && ($CONFIG['auto_flagged_quotes'] == 1)) ? 2 : 0;
    $quotxt = htmlspecialchars(trim($_POST["rash_quote"]));
    $innerhtml = $TEMPLATE->add_quote_outputmsg(mangle_quote_text($quotxt));
    $res =& $db->query("INSERT INTO ".db_tablename('quotes')." (quote, rating, flag, queue, date) VALUES(".$db->quote($quotxt).", 0, ".$flag.", ".$CONFIG['moderated_quotes'].", '".mktime()."')");
    if(DB::isError($res)){
	die($res->getMessage());
    }
    return $innerhtml;
}

function add_quote($method)
{
    global $CONFIG, $TEMPLATE, $CAPTCHA, $db;

    $innerhtml = '';
    $quotxt = '';

    if ($method == 'submit') {
	$quotxt = htmlspecialchars(trim($_POST["rash_quote"]));
	if (strlen($quotxt) < 3) {
	    $TEMPLATE->add_message(lang('add_quote_short'));
	} else {
	    if (isset($_POST['preview'])) {
		$innerhtml = $TEMPLATE->add_quote_preview(mangle_quote_text($quotxt));
	    } else {
		$innerhtml = handle_captcha('add_quote', 'add_quote_do_inner');
		$added = 1;
	    }
	}
    }

    print $TEMPLATE->add_quote_page($quotxt, $innerhtml, $added);
}



$page[1] = 0;
$page[2] = 0;
$page = explode($CONFIG['GET_SEPARATOR'], $_SERVER['QUERY_STRING']);


if(!($page[0] == 'rss'))
    $TEMPLATE->printheader(title($page[0]), $CONFIG['site_short_title'], $CONFIG['site_long_title']); // templates/x_template/x_template.php

$page[1] = (isset($page[1]) ? $page[1] : null);
$page[2] = (isset($page[2]) ? $page[2] : null);

if (preg_match('/=/', $page[0])) {
    $tmppage = split("=", $page[0], 2);
    $page[0] = trim($tmppage[0]);
    $pageparam = trim($tmppage[1]);
}

$limit = get_number_limit($pageparam, 1, $CONFIG['quote_list_limit']);

switch($page[0])
{
	case 'add':
	    if (isset($CONFIG['login_required']) && ($CONFIG['login_required'] == 1) && !isset($_SESSION['logged_in']))
		break;
	    add_quote($page[1]);
	    break;
	case 'edit_news':
	    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] <= USER_ADMIN)) {
		edit_news($page[1], $page[2]);
	    }
	    break;
	case 'add_news':
	    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] <= USER_ADMIN)) {
		add_news($page[1]);
	    }
	    break;
	case 'add_user':
	    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] <= USER_SUPERUSER)) {
		add_user($page[1]);
	    }
	    break;
	case 'register':
	    if (isset($CONFIG['login_required']) && ($CONFIG['login_required'] == 1)) {
		register_user($page[1]);
	    }
	    break;
	case 'login':
	    if (isset($CONFIG['login_required']) && ($CONFIG['login_required'] == 1)) {
		if (isset($_SESSION['logged_in'])) {
		    header('Location: http://' . $_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']));
		} else {
		    userlogin($page[1]);
		}
	    } else {
		header('Location: http://' . $_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']));
	    }
	    break;
	case 'admin':
		if (isset($_SESSION['logged_in'])) {
		    /* already logged in */
		} else {
		    adminlogin($page[1]);
		}
		break;
	case 'bottom':
	    $query = "SELECT * FROM ".db_tablename('quotes')." WHERE queue=0 and rating < 0 ORDER BY rating ASC LIMIT ".$limit;
	    quote_generation($query, lang('bottom_title'), -1);
	    break;
	case 'browse':
		$query = "SELECT * FROM ".db_tablename('quotes')." WHERE queue=0 ORDER BY id ASC ";
		quote_generation($query, lang('browse_title'), $page[1], $CONFIG['quote_limit'], $CONFIG['page_limit']);
		break;
	case 'change_pw':
	    if (isset($_SESSION['logged_in']))
		change_pw($page[1], $page[2]);
	    break;
	case 'flag':
	    if (isset($CONFIG['login_required']) && ($CONFIG['login_required'] == 1) && !isset($_SESSION['logged_in']))
		break;
	    flag($page[1], $page[2]);
	    break;
	case 'flag_queue':
	    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] < USER_NORMAL))
		flag_queue($page[1]);
	    break;
	case 'latest':
	    $query = "SELECT * FROM ".db_tablename('quotes')." WHERE queue=0 ORDER BY id DESC LIMIT ".$limit;
	    if (isset($_SESSION['lastvisit'])) {
		$nlatest = $db->getOne("SELECT count(1) FROM ".db_tablename('quotes')." WHERE queue=0 AND date>=".$_SESSION['lastvisit']);
		if (($nlatest >= 3) && ($nlatest <= $CONFIG['quote_list_limit'])) {
		    $query = "SELECT * FROM ".db_tablename('quotes')." WHERE queue=0 AND date>=".$_SESSION['lastvisit']." ORDER BY id DESC";
		}
	    }
	    quote_generation($query, lang('latest_title'), -1);
	    break;
	case 'logout':
	    set_user_logout();
	    break;
	case 'queue':
	    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] < USER_NORMAL))
		quote_queue($page[1]);
	    else if (isset($CONFIG['public_queue']) && ($CONFIG['public_queue'] == 1)) {
		$query = "SELECT * FROM ".db_tablename('quotes')." WHERE queue=1 ORDER BY rand() LIMIT ".$limit;
		quote_generation($query, lang('quote_queue_title'), -1);
	    }
	    break;
	case 'random':
	    $query = "SELECT * FROM ".db_tablename('quotes')." WHERE queue=0 ORDER BY rand() LIMIT ".$limit;
	    quote_generation($query, lang('random_title'), -1);
	    break;
	case 'random2':
	case 'randomplus':
	    $query = "SELECT * FROM ".db_tablename('quotes')." WHERE queue=0 and rating>0 ORDER BY rand() LIMIT ".$limit;
	    quote_generation($query, lang('random2_title'), -1);
	    break;
	case 'random3':
	case 'random0':
	    $query = "SELECT * FROM ".db_tablename('quotes')." WHERE queue=0 and rating=0 ORDER BY rand() LIMIT ".$limit;
	    quote_generation($query, lang('random3_title'), -1);
	    break;
	case 'random4':
	case 'randomminus':
	    $query = "SELECT * FROM ".db_tablename('quotes')." WHERE queue=0 and rating<0 ORDER BY rand() LIMIT ".$limit;
	    quote_generation($query, lang('random4_title'), -1);
	    break;
	case 'rss':
	    rash_rss();
	    break;
	case 'search':
	    search($page[1], $pageparam);
	    break;
	case 'top':
	    $query = "SELECT * FROM ".db_tablename('quotes')." WHERE queue=0 and rating > 0 ORDER BY rating DESC LIMIT ".$limit;
	    quote_generation($query, lang('top_title'), -1);
	    break;
	case 'edit':
	    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] <= USER_ADMIN))
		edit_quote($page[1], $page[2]);
	    break;
	case 'users':
	    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] <= USER_SUPERUSER))
		edit_users($page[1], $page[2]);
	    break;
	case 'vote':
	    if (isset($CONFIG['login_required']) && ($CONFIG['login_required'] == 1) && !isset($_SESSION['logged_in']))
		break;
	    vote($page[1], $page[2]);
	    break;
	default:
	    if (preg_match('/^[0-9]+(&[0-9]+)*$/', $_SERVER['QUERY_STRING'])) {
		$idlist = explode('&', $_SERVER['QUERY_STRING']);
		if (count($idlist) < 11) {
		    $ids = array();
		    $order = array();
		    $idx = 0;
		    foreach ($idlist as $id) {
			$ids[] = 'id='.$db->quote((int)$id);
			$order[] = 'WHEN '.$db->quote((int)$id).' THEN '.$idx.' ';
			$idx++;
		    }
		    $query = "SELECT * FROM ".db_tablename('quotes')." WHERE queue=0 and (".implode(' or ', $ids).") ORDER BY CASE id ".implode($order)." END";
		    if ($idx > 1) $title = 'Selected Quotes';
		    else $title = "#${_SERVER['QUERY_STRING']}";
		} else {
		    $query = "SELECT * FROM ".db_tablename('quotes')." WHERE queue=0 and id =".$db->quote((int)$idlist[0]);
		    $title = "#${idlist[0]}";
		}
		quote_generation($query, $title, -1);
	    } else {
		home_generation();
	    }

}
if(!($page[0] == 'rss'))
    $TEMPLATE->printfooter(get_db_stats());	// templates/x_template/x_template.php

$db->disconnect();
