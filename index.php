<?php
/*
error_reporting(E_ALL);
ini_set('display_errors','On');
*/

if (!file_exists('settings.php')) {
    header("Location: install.php");
    exit;
}

session_start();

require_once 'DB.php';

require('settings.php');
require('util_funcs.php');
require("language/{$CONFIG['language']}.lng");

require('basetemplate.php');
require($CONFIG['template']);

$mainmenu = array(array('url' => './', 'id' => 'site_nav_home', 'txt' => 'menu_home'),
		  array('url' => '?latest', 'id' => 'site_nav_latest', 'txt' => 'menu_latest'),
		  array('url' => '?browse', 'id' => 'site_nav_browse', 'txt' => 'menu_browse'),
		  array('url' => '?random', 'id' => 'site_nav_random', 'txt' => 'menu_random'),
		  array('url' => '?random2', 'id' => 'site_nav_random2', 'txt' => 'menu_random2'),
		  array('url' => '?bottom', 'id' => 'site_nav_bottom', 'txt' => 'menu_bottom'),
		  array('url' => '?top', 'id' => 'site_nav_top', 'txt' => 'menu_top'),
		  array('url' => '?search', 'id' => 'site_nav_search', 'txt' => 'menu_search'),
		  array('url' => '?add', 'id' => 'site_nav_add', 'txt' => 'menu_contribute')
);
if (isset($_SESSION['logged_in'])) {
    $adminmenu = array(array('url' => '?queue', 'id' => 'site_admin_nav_queue', 'txt' => 'menu_queue'),
		      array('url' => '?flag_queue', 'id' => 'site_admin_nav_flagged', 'txt' => 'menu_flagged'));
    if ($_SESSION['level'] < 3)
	$adminmenu[] = array('url' => '?add_news', 'id' => 'site_admin_nav_add-news', 'txt' => 'menu_addnews');
    if ($_SESSION['level'] == 1) {
	$adminmenu[] = array('url' => '?add_users', 'id' => 'site_admin_nav_users', 'txt' => 'menu_users');
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

    if (DB::isError($db)) { return null; }

    $ret['pending_quotes'] = $db->getOne('select count(id) from '.db_tablename('queue'));
    $ret['approved_quotes'] = $db->getOne('SELECT COUNT(id) FROM '.db_tablename('quotes'));

    return $ret;
}


function rash_rss()
{
    global $db, $CONFIG;
    $query = "SELECT id, quote, rating, flag FROM ".db_tablename('quotes')." ORDER BY id DESC LIMIT 15";

    $res =& $db->query($query);
    print "<?xml version=\"1.0\" ?>\n";
    print "<rss version=\"0.92\">\n";
    print "<channel>\n";
    print "<title>".$CONFIG['rss_title']."</title>\n";
    print "<description>".$CONFIG['rss_desc']."</description>\n";
    print "<link>".$CONFIG['rss_url']."</link>\n";

    while($row=$res->fetchRow(DB_FETCHMODE_ASSOC)) {
	print "<item>\n";
	print "<title>".$CONFIG['rss_url']."/?".$row['id']."</title>\n";
	print "<description>".mangle_quote_text($row['quote'])."</description>\n";
	print "<link>".$CONFIG['rss_url']."/?".$row['id']."</link>\n";
	print "</item>\n\n";
    }
    print "</channel></rss>";
}

// function user_quote_status($where, $quote_num)
// This function checks the user's ip address against the stores entries to ensure
// that multiple voting doesn't occur (it does this with the ip_track() function.
// It returns a number for either flag or vote to tell them if you're able to
// modify the quote.
//
function user_quote_status($where, $quote_num)
{
    global $TEMPLATE, $lang;
	$tracking_verdict = ip_track($where, $quote_num);
	if($where != 'flag'){
		switch($tracking_verdict){
			case 1:
			    $TEMPLATE->add_message($lang['tracking_check_1']);
			    break;
			case 2:
			    $TEMPLATE->add_message($lang['tracking_check_2']);
			    break;
			case 3:
			    $TEMPLATE->add_message($lang['tracking_check_3']);
			    break;
		}
	}
	return $tracking_verdict;
}


// flag()
// User clicks on the (default) [X] link and it takes that quote and changes
// a cell in the approved quote table. This change is shown in the administation
// section to warn you that the quote is either bad or offensive. The admin can
// do whatever is needed at the time. Times allowed to do it limited by a cookie.
//
function flag($quote_num)
{
    global $TEMPLATE, $lang, $db;
	$tracking_verdict = user_quote_status('flag', $quote_num);
	if($tracking_verdict == 1 || 2){
	    global $db;
	    $res =& $db->query("SELECT flag FROM ".db_tablename('quotes')." WHERE id = ".$db->quote((int)$quote_num)." LIMIT 1");
		$row = $res->fetchRow(DB_FETCHMODE_ORDERED);
		if($row[0] == 2){
		    $TEMPLATE->add_message($lang['flag_previously_flagged']);
		}
		elseif($row[0] == 1){
		    $TEMPLATE->add_message($lang['flag_currently_flagged']);
		}
		else{
		    $TEMPLATE->add_message($lang['flag_quote_flagged']);
		    $db->query("UPDATE ".db_tablename('quotes')." SET flag = 1 WHERE id = ".$db->quote((int)$quote_num));
		}
	}
}

// function vote($quote_num, $method)
// This function increments or decrements the rating of the quote in quotes.
//
function vote($quote_num, $method)
{
    global $db, $TEMPLATE;
	$tracking_verdict = user_quote_status('vote', $quote_num);
	if($tracking_verdict == 3){
		$TEMPLATE->printfooter();
		exit();
	}
	if($tracking_verdict == 1 || 2){
		if($method == "plus")
		    $db->query("UPDATE ".db_tablename('quotes')." SET rating = rating+1 WHERE id = ".$db->quote((int)$quote_num));
		elseif($method == "minus")
		    $db->query("UPDATE ".db_tablename('quotes')." SET rating = rating-1 WHERE id = ".$db->quote((int)$quote_num));
	}
}


function ip_track($where, $quote_num)
{
    global $db;
	switch($where){
		case 'flag':
			$where2 = 'vote';
			break;
		case 'vote':
			$where2 = 'flag';
			break;
		default:
		        die('illegal tracking where.');
	}


	$res =& $db->query("SELECT ip FROM ".db_tablename('tracking')." WHERE ip=".$db->quote(getenv("REMOTE_ADDR")));
	if (DB::isError($res)) {
		die('ip_track(1):'.$res->getMessage());
	}

	if($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){ // if ip is in database
		$res->free();
		$res =& $db->query("SELECT quote_id FROM ".db_tablename('tracking')." WHERE ip=".$db->quote(getenv("REMOTE_ADDR")));
		if (DB::isError($res)) {
			die('ip_track(2):'.$res->getMessage());
		}
		$quote_array = $res->fetchRow(DB_FETCHMODE_ORDERED);
		$quote_array = explode(",", $quote_array[0]);
		$quote_place = array_search($quote_num, $quote_array);
		if(in_array($quote_num, $quote_array)){
		    $res2 =& $db->query("SELECT $where FROM ".db_tablename('tracking')." WHERE ip=".$db->quote(getenv("REMOTE_ADDR")));
			if (DB::isError($res)) {
				die('ip_track(3):'.$res->getMessage());
			}
			$where_result = $res2->fetchRow(DB_FETCHMODE_ORDERED);
			$where_result = explode(",", $where_result[0]);
			if(!$where_result[$quote_place]){
				$where_result[$quote_place] = 1;
				$where_result = implode(",", $where_result);
				$db->query("UPDATE ".db_tablename('tracking')." SET $where = ".$db->quote($where_result)." WHERE ip=".$db->quote(getenv("REMOTE_ADDR")));
				if (DB::isError($res)) {
					die('ip_track(4):'.$res->getMessage());
				}

				return 1;
			}
			else{
				return 3;
			}
		}
		else{	// if the quote doesn't exist in the array based on ip, the quote and relevent vote and flag
				// entries are concatenated to the end of the current entries

			// mysql_query("UPDATE $trackingtable SET $where=CONCAT($where,',1'),
			// $where2=CONCAT($where2,',0'), $where3=CONCAT($where3,',0'),
			// quote=CONCAT(quote,'," . $quote_num . "') WHERE ip ='" . getenv("REMOTE_ADDR") . "';");
			// Oh how I miss thee mysql :(

			// Update the quote_id
		    $res =& $db->query("SELECT quote_id FROM ".db_tablename('tracking')." WHERE ip=".$db->quote(getenv("REMOTE_ADDR")));
			if (DB::isError($res)) {
				die('ip_track(5):'.$res->getMessage());
			}
			$row = $res->fetchRow(DB_FETCHMODE_ORDERED);
			$row[] = $quote_num;
			$db->query("UPDATE ".db_tablename('tracking')." SET quote_id = ".$db->quote(implode(",", $row))." WHERE ip=".$db->quote(getenv("REMOTE_ADDR")));
			if (DB::isError($res)) {
				die('ip_track(6):'.$res->getMessage());
			}
			$res->free();

			// Update $where
			$res =& $db->query("SELECT $where FROM ".db_tablename('tracking')." WHERE ip=".$db->quote(getenv("REMOTE_ADDR")));
			if (DB::isError($res)) {
				die('ip_track(7):'.$res->getMessage());
			}
			$row = $res->fetchRow(DB_FETCHMODE_ORDERED);
			$row[] = '1';
			$db->query("UPDATE ".db_tablename('tracking')." SET $where = ".$db->quote(implode(",", $row)));
			if (DB::isError($res)) {
				die('ip_track(8):'.$res->getMessage());
			}
			$res->free();

			// Update $where2
			$res =& $db->query("SELECT $where2 FROM ".db_tablename('tracking')." WHERE ip=".$db->quote(getenv("REMOTE_ADDR")));
			if (DB::isError($res)) {
				die('ip_track(9):'.$res->getMessage());
			}
			$row = $res->fetchRow(DB_FETCHMODE_ORDERED);
			$row[] = '0';
			$db->query("UPDATE ".db_tablename('tracking')." SET $where2 = ".$db->quote(implode(",", $row)));
			if (DB::isError($res)) {
				die('ip_track(10):'.$res->getMessage());
			}
			$res->free();

			return 1;
		}
	}
	else{ // if ip isn't in database, add it and appropriate quote action
	    $res = $db->query("INSERT INTO ".db_tablename('tracking')." (ip, quote_id, $where, $where2) VALUES(".$db->quote(getenv("REMOTE_ADDR")).", ".$db->quote($quote_num).", 1, 0);");
		if (DB::isError($res)) {
			die('ip_track(11):'.$res->getMessage());
		}
		return 2;
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

// page_numbers()
// This functino deals with all the page numbers in the (by default)
// browse section. It first gets its variables in order, figured out
// how many pages there ought to be based on the limit of quotes per page
// then
function page_numbers($origin, $quote_limit, $page_default, $page_limit)
{
    global $CONFIG, $db, $lang;
    $numrows = $db->getOne("SELECT COUNT(id) FROM ".db_tablename('quotes'));
    $testrows = $numrows;

	$pagenum = 0;

    do{
		$pagenum++;
        $testrows -= $quote_limit;
    }while($testrows > 0);

	// ensures $page_limit is an odd number so the algorithm output looks decent,
	// as in the current page is in the middle of a number line containing the
	// pages rather than a little left or right of the middle, which works as long
	// as the pages are being viewed from the middle of the number set rather than
	// either end, heh
	if(!($page_limit % 2))
		$page_limit += 1;

	// if $page_limit is 1, 0, or negative, it is automatically set to 5
	if(($page_limit == 1) || ($page_limit < 0) || (!$page_limit))
		$page_limit = 5;

	// determines how many pages to show based on limit of pages ($page_limit)
	// which is set in settings.php, $page_base is how many in EACH DIRECTION
	// on a number line from $page_default to go

	$page_base = 0;
	do{	// determine how many pages to the left and right of the current page to
		// show in the page numbers bar
		$page_base++;
		$page_limit -= 2;
	}while($page_limit > 1);
	echo "   <div class=\"quote_pagenums\">\n";
	echo "    <a href=\"?".urlargs(strtolower($origin),'1')."\">".$lang['page_first']."</a>&nbsp;&nbsp;\n";
	// this line is responsible for the -10 link in browse (by default), and the weird part in the middle
	// is a conditional that checks to see if the current page - 10 is going to be 0 or negative, if it is,
	// the -10 link defaults to page 1, if it turns out it's > 0, it links to the current page - 10 pages
	//
	echo "    <a href=\"?".urlargs(strtolower($origin),
					     ((($page_default-10) > 1) ? ($page_default-10) : (1)))
		."\">-10</a>&nbsp;&nbsp; \n";

	if(($page_default - $page_base) > 1)
	{	// an ellipse is echoed when there exist pages beyond the current sight of the user
		echo "    ... \n";
	}
	$x = ($page_default - $page_base);

	do{	// echo the page numbers before the current page, but only $page_limit many
		if($x > 0) // keeps page numbers from going to zero or below
		    echo "    <a href=\"?".urlargs(strtolower($origin),$x)."\">${x}</a> \n";
		$x++;
	}while($x < $page_default);

	// echo the current page, no link
	echo "    ${page_default} \n";

	$x = ($page_default + 1);

	do{	// echo the page numbers after the current page, but only $page_limit many
		if($x <= $pagenum) // keeps page numbers from going higher than ones that have quotes
		    echo "    <a href=\"?".urlargs(strtolower($origin),$x)."\">${x}</a> \n";
		$x++;
	}while($x < ($page_default + $page_base + 1));

	if(($page_default + $page_base) < $pagenum)
	{	// an ellipse is echoed when there exist pages beyond the current sight of the user
		echo "    ... \n";
	}

	// this line is responsible for the -10 link in browse (by default), and the weird part in the middle
	// checks to see if the current page + 10 will end up being less than the highet actual possible page,
	// if it turns out that's true, then it links to the current page + 10, if current page + 10 is higher
	// than the highest possible page, then it just links to the highest possible page
	//
	echo "    &nbsp;&nbsp;<a href=\"?".urlargs(strtolower($origin),
						   ((($page_default+10) < $pagenum) ? ($page_default+10) : ($pagenum)))
		."\">+10</a>&nbsp;&nbsp;\n";

	echo "    &nbsp;&nbsp;<a href=\"?".urlargs(strtolower($origin),$pagenum)."\">".$lang['page_last']."</a>\n";
	echo "   </div>\n";
}


function edit_quote_button($quoteid)
{
    global $TEMPLATE;
    if ($_SESSION['logged_in'] && ($_SESSION['level'] >= 1) && ($_SESSION['level'] <= 2)) {
	return $TEMPLATE->edit_quote_button($quoteid);
    }
    return '';
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
    if ($page != -1) {
	if(!$page)
	    $page = 1;

	print '<div class="quote_pagenums">';
	page_numbers($origin, $quote_limit, $page, $page_limit);
	print '</div>';

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

    if (isset($origin)) {
	print '<div id="quote_origin-name">'.$origin.'</div>';
    }
    while($row=$res->fetchRow(DB_FETCHMODE_ASSOC)){
	print $TEMPLATE->quote_iter($row['id'], $row['rating'], mangle_quote_text($row['quote']), date($CONFIG['quote_time_format'], $row['date']));
    }
    if($page != -1){
	print '<div class="quote_pagenums">';
	page_numbers($origin, $quote_limit, $page, $page);
	print '</div>';
    }
}



function add_news($method)
{
    global $CONFIG, $TEMPLATE, $db;
	if($method == 'submit')
	{
	    $news = nl2br($_POST['news']);
	    $db->query("INSERT INTO ".db_tablename('news')." (news,date) VALUES(".$db->quote($news).", '".mktime()."');");
	}

	print $TEMPLATE->add_news_page();
}

function user_level_select($selected=3, $id='admin_add-user_level')
{
    $lvls = array('1' => 'superuser',
		  '2' => 'administrator',
		  '3' => 'moderator');

    $str = '<select name="level" size="1" id="'.$id.'">';

    foreach ($lvls as $key => $val) {
	$str .= '<option value="'.$key.'"';
	if ($key == $selected) $str .= ' selected';
	$str .= '>'.$key.' - '.$val.'</option>';
    }

    $str .= '</select>';
    return $str;
}

function add_user($method)
{
    global $CONFIG, $TEMPLATE, $db;
    if ($method == 'update') {
	$db->query("INSERT INTO ".db_tablename('users')." (user, password, level, salt) VALUES(".$db->quote($_POST['username']).", '".crypt($_POST['password'], "\$1\$".substr($_POST['salt'], 0, 8)."\$")."', ".$db->quote((int)$_POST['level']).", '\$1\$".$_POST['salt']."\$');");
		if (DB::isError($res)) {
		    die($res-> getMessage());
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

		$res =& $db->query("SELECT `password`, salt FROM ".db_tablename('users')." WHERE user=".$db->quote($who));
		$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
		$salt = "\$1\$".str_rand(8,'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890')."\$";

		if((md5($_POST['old_password']) == $row['password']) || (crypt($_POST['old_password'], $row['salt']) == $row['password'])){
			if($_POST['verify_password'] == $_POST['new_password']){
				$db->query("UPDATE ".db_tablename('users')." SET `password`='".crypt($_POST['new_password'], $salt)."', salt='$salt' WHERE user='$who'");
				$TEMPLATE->add_message('Password updated!');
			}
		}
    }

    print $TEMPLATE->change_password_page();
}

function edit_users($method, $who)
{
    global $CONFIG, $TEMPLATE, $db;
	if($method == 'delete'){	// delete a user from users
		if($_POST['verify']){
		    $res =& $db->query("SELECT * FROM ".db_tablename('users'));
			while($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
			{
				if(isset($_POST['d'.$row['user']])){
					$db->query("DELETE FROM ".db_tablename('users')." WHERE user='{$_POST['d'.$row['user']]}'");
					$TEMPLATE->add_message($row['user'].' has been removed from the userlist!');
				}
			}
		}
	}
	if($method == 'update'){	// parse the info from $method == 'edit' into the database
	    $db->query("UPDATE ".db_tablename('users')." SET user=".$db->quote(strtolower($_POST['user'])).", level=".$db->quote((int)$_POST['level'])." WHERE user=".$db->quote($who));
		if($_POST['password'])
		    $db->query("UPDATE ".db_tablename('users')." SET `password`='".md5($_POST['password'])."' WHERE user=".$db->quote($who));
	}
	if($method == 'edit'){		// take input from a superuser about how to change all users
								// can change username, password, or user level
	    $res =& $db->query("SELECT * FROM ".db_tablename('users')." WHERE user=".$db->quote($who));
		$row = $res->fetchRow(DB_FETCHMODE_ASSOC);

		print $TEMPLATE->edit_user_page_form($who, $row['user'], $row['level']);
	}

	$innerhtml = '';

	$res =& $db->query("SELECT * FROM ".db_tablename('users'));
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
	{
	    $innerhtml .= $TEMPLATE->edit_user_page_table_row($row['user'], $row['password'], $row['level']);

	}

	print $TEMPLATE->edit_user_page_table($innerhtml);
}

// login($method)
//
function login($method)
{
    global $CONFIG, $TEMPLATE, $db, $lang;
	if(!$method){
	    print $TEMPLATE->login_page();
	}
	elseif($method == 'login'){
	    $res =& $db->query("SELECT salt FROM ".db_tablename('users')." WHERE user=".$db->quote(strtolower($_POST['rash_username'])));
		$salt = $res->fetchRow(DB_FETCHMODE_ASSOC);

		// if there is no presence of a salt, it is probably md5 since old rash used plain md5
		if(!$salt['salt']){
		    $res =& $db->query("SELECT user, password, level FROM ".db_tablename('users')." WHERE user=".$db->quote(strtolower($_POST['rash_username']))." AND `password` ='".md5($_POST['rash_password'])."'");
			$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
		}
		// if there is presense of a salt, it is probably new rash passwords, so it is salted md5
		else{
		    $res =& $db->query("SELECT user, password, level FROM ".db_tablename('users')." WHERE user=".$db->quote(strtolower($_POST['rash_username']))." AND `password` ='".crypt($_POST['rash_password'], $salt['salt'])."'");
			$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
		}

		// if there is no row returned for the user, the password is expected to be false because of the AND conditional in the query
		if(!$row['user']){
		    $TEMPLATE->add_message($lang['login_error']);
		}
		else{
			$_SESSION['user'] = $row['user'];		// site-wide accessible username
			$_SESSION['level'] = $row['level'];		// site-wide accessible level
			$_SESSION['logged_in'] = 1;				// site-wide accessible login variable
			// Go to the main page after being logged in
			header("Location: http://"	. $_SERVER['HTTP_HOST']
										. dirname($_SERVER['PHP_SELF']));
		}
	}
}
// End of login()




// quote_queue($method)
// This function displays the queue of quotes in the table queue, input from users is sent
// to queue and an administrator has the privileges to send that quote into the main quote
// database to be viewed by the public, or purge it from the system.
//

function quote_queue($method)
{
    global $CONFIG, $TEMPLATE, $db;
	if($method == 'judgement'){ // $method is a variable that is passed to the function to tell it how to act
								// setting it to judgement tells the program to take moderator radio button input
								// and either let the quotes into quotes or purge them
	    $res =& $db->query("SELECT * FROM ".db_tablename('queue'));
		$x = 0;
		while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
			if($_POST['q'.$row['id']]){ // sets up an array that can be looped through containing the ids of all the
										// quotes that have been voted yes or no on
				$judgement_array[$x] = $_POST['q'.$row['id']];
				$x++;
			}
		}
		$x = 0;
		while($judgement_array[$x]){	// itinerates through $judgement_array, stops when it gets to the end of the quote list
			if(substr($judgement_array[$x], 0, 1) == 'y'){	// checks to see if the first letter of
															// the entry of a quote in the array is y
															// a 'y' in there signifies it should be inserted
															// into quotes
			    $quote =& $db->query("SELECT quote FROM ".db_tablename('queue')." WHERE id =".$db->quote((int)substr($judgement_array[$x], 1))." LIMIT 1");
										// query to grab the quote in question straight from queue
				if (DB::isError($res)) {
					die($res->getMessage());
				}
				$row = $quote->fetchRow(DB_FETCHMODE_ASSOC);	// fetches the quote from the database
				$db->query("INSERT INTO ".db_tablename('quotes')." (quote, rating, flag, date) VALUES (".$db->quote($row['quote']).", 0, 0, '".mktime()."');");
				$TEMPLATE->add_message('Quote '.substr($judgement_array[$x], 1).' added to quote database!');
															// inserts the quote into quotes and gives a confirmation message
			}
			$db->query("DELETE FROM ".db_tablename('queue')." WHERE id =".$db->quote((int)substr($judgement_array[$x], 1)).";");
															// the quote is deleted from queue regardless if it is
															// submitted into quotes or not, since there's no reason
															// for it to be there if it is checked as no or as yes
			$TEMPLATE->add_message('Quote '.substr($judgement_array[$x], 1).' deleted from temporary database!');
			$x++;	// increments x so the judgement_array goes to the next item
		}
	}

	$res =& $db->query("SELECT * FROM ".db_tablename('queue')." order by id asc");
					// query to grab all of the queued quotes to display
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
// End quote_queue()


// flag_queue($method)
//
//

function flag_queue($method)
{
    global $CONFIG, $TEMPLATE, $db;
	if($method == 'judgement'){

	    if ($_POST['do_all'] == 'on') {
		if (isset($_POST['unflag_all'])) {
		    $db->query("UPDATE ".db_tablename('quotes')." SET flag=2 WHERE flag=1");
		    $TEMPLATE->add_message('Unflagged all.');
		} else if (isset($_POST['delete_all'])) {
		    $db->query("DELETE FROM ".db_tablename('quotes')." WHERE flag=1");
		    $TEMPLATE->add_message('Deleted all.');
		}
	    }

		$res =& $db->query("SELECT * FROM ".db_tablename('quotes')." WHERE flag = 1");

		$x = 0;
		while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
			if($_POST['q'.$row['id']]){
				$judgement_array[$x] = $_POST['q'.$row['id']];
				$x++;
			}
		}

		$x = 0;
		while($judgement_array[$x]){
			if(substr($judgement_array[$x], 0, 1) == 'u'){
			    $db->query("UPDATE ".db_tablename('quotes')." SET flag = 2 WHERE id =".$db->quote((int)substr($judgement_array[$x], 1)));
			    $TEMPLATE->add_message('Quote '.substr($judgement_array[$x], 1).' has been unflagged!');
			}
			if(substr($judgement_array[$x], 0, 1) == 'd'){
			    $db->query("DELETE FROM ".db_tablename('quotes')." WHERE id=".$db->quote((int)substr($judgement_array[$x], 1)));
			    $TEMPLATE->add_message('Quote '.substr($judgement_array[$x], 1).' deleted from database!');
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

function search($method)
{
    global $CONFIG, $TEMPLATE, $lang, $db;
    if ($method == 'fetch') {
	if($_POST['sortby'] == 'rating')
	    $how = 'desc';
	else
	    $how = 'asc';

	$search = $_POST['search'];

	if (preg_match('/^#[0-9]+$/', trim($search))) {
	    $exactmatch = ' or id='.substr(trim($search), 1);
	} else {
	    $exactmatch = '';
	}

	$search = '%'.$search.'%';

	$query = "SELECT id, quote, rating, flag, date FROM ".db_tablename('quotes')." WHERE (quote LIKE ".$db->quote($search).$exactmatch.") ORDER BY ".$db->quote($_POST['sortby'])." $how LIMIT ".$db->quote((int)$_POST['number']);

	quote_generation($query, $lang['search_results_title'], -1);
    }

    print $TEMPLATE->search_quotes_page(($method == 'fetch'));
}

function edit_quote($method, $quoteid)
{
    global $CONFIG, $TEMPLATE, $db;

    if (!($_SESSION['logged_in'] && ($_SESSION['level'] >= 1) && ($_SESSION['level'] <= 2))) return;

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


// add_quote()
// This function serves as the page catering to ?add, it can receive input
// from an HTML form that will be inserted into queue for viewing when
// logged in as an administrator.

function add_quote($method)
{
    global $CONFIG, $TEMPLATE, $db;

    $innerhtml = '';

    if ($method == 'submit') {

	$quotxt = htmlspecialchars(trim($_POST["rash_quote"]));

	$innerhtml = $TEMPLATE->add_quote_outputmsg(mangle_quote_text($quotxt));

	$res =& $db->query("INSERT INTO ".db_tablename('queue')." (quote) VALUES(".$db->quote($quotxt).")");
	if(DB::isError($res)){
	    die($res->getMessage());
	}
    }

    print $TEMPLATE->add_quote_page($innerhtml);
}



$page[1] = 0;
$page[2] = 0;
$page = explode($CONFIG['GET_SEPARATOR'], $_SERVER['QUERY_STRING']);

date_default_timezone_set($CONFIG['timezone']);

if(!($page[0] == 'rss'))
    $TEMPLATE->printheader(title($page[0]), $CONFIG['site_short_title'], $CONFIG['site_long_title']); // templates/x_template/x_template.php

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
    print $db->getMessage();
    if (!($page[0] == 'rss')) $TEMPLATE->printfooter();
    exit;
}


switch($page[0])
{
	case 'add':
		add_quote($page[1]);
		break;
	case 'add_news':
		if($_SESSION['logged_in'])
		{
			add_news($page[1]);
		}
		break;
	case 'add_user':
		if($_SESSION['logged_in']){
			if($_SESSION['level'] == 1){
				add_user($page[1]);
			}
		}
		break;
	case 'admin':
		login($page[1]);
		break;
	case 'bottom':
		$query = "SELECT id, quote, rating, flag, date FROM ".db_tablename('quotes')." WHERE rating < 0 ORDER BY rating ASC LIMIT 50";
		quote_generation($query, $lang['bottom_title'], -1);
		break;
	case 'browse':
		$query = "SELECT id, quote, rating, flag, date FROM ".db_tablename('quotes')." ORDER BY id ASC ";
		quote_generation($query, $lang['browse_title'], $page[1], $CONFIG['quote_limit'], $CONFIG['page_limit']);
		break;
	case 'change_pw':
		if($_SESSION['logged_in'])
			change_pw($page[1], $page[2]);
		break;
	case 'flag':
		flag($page[1]);
		break;
	case 'flag_queue':
		if($_SESSION['logged_in'])
			flag_queue($page[1]);
		break;
	case 'latest':
		$query = "SELECT id, quote, rating, flag, date FROM ".db_tablename('quotes')." ORDER BY id DESC LIMIT 50";
		quote_generation($query, $lang['latest_title'], -1);
		break;
	case 'logout':
		session_unset($_SESSION['user']);
		session_unset($_SESSION['logged_in']);
		session_unset($_SESSION['level']);
		header("Location: http://" . $_SERVER['HTTP_HOST']
			             . dirname($_SERVER['PHP_SELF'])
				         . "/" . $relative_url);
	case 'queue':
		if($_SESSION['logged_in'])
			quote_queue($page[1]);
		break;
	case 'random':
		$query = "SELECT id, quote, rating, flag, date FROM ".db_tablename('quotes')." ORDER BY rand() LIMIT 50";
		quote_generation($query, $lang['random_title'], -1);
		break;
	case 'random2':
		$query = "SELECT id, quote, rating, flag, date FROM ".db_tablename('quotes')." WHERE rating > 1 ORDER BY rand() LIMIT 50";
		quote_generation($query, $lang['random2_title'], -1);
		break;
	case 'rss':
	    rash_rss();
	    break;
	case 'search':
		search($page[1]);
		break;
	case 'top':
		$query = "SELECT id, quote, rating, flag, date FROM ".db_tablename('quotes')." WHERE rating > 0 ORDER BY rating DESC LIMIT 50";
		quote_generation($query, $lang['top_title'], -1);
		break;
	case 'edit':
	    if ($_SESSION['logged_in'] && ($_SESSION['level'] >= 1) && ($_SESSION['level'] <= 2))
		edit_quote($page[1], $page[2]);
	    break;
	case 'users':
		if($_SESSION['logged_in'])
			edit_users($page[1], $page[2]);
		break;
	case 'vote':
		vote($page[1], $page[2]);
		break;
	default:
	    if (preg_match('/^[0-9]+$/', $_SERVER['QUERY_STRING'])) {
		$query = "SELECT id, quote, rating, flag, date FROM ".db_tablename('quotes')." WHERE id =".$db->quote((int)$_SERVER['QUERY_STRING']);
		quote_generation($query, "#${_SERVER['QUERY_STRING']}", -1);
	    } else {
		home_generation();
	    }

}
if(!($page[0] == 'rss'))
    $TEMPLATE->printfooter(get_db_stats());	// templates/x_template/x_template.php

$db->disconnect();
