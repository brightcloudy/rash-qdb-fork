<?php

if (!file_exists('settings.php')) {
    header("Location: install.php");
    exit;
}

session_start();

require_once 'DB.php';

require('settings.php');
require('util_funcs.php');
require("language/{$CONFIG['language']}.lng");

require($CONFIG['template']);


function rash_rss()
{
    global $db, $CONFIG;
    $query = "SELECT id, quote, rating, flag FROM rash_quotes ORDER BY id DESC LIMIT 15";

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
	print "<description>".nl2br($row['quote'])."</description>\n";
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
    global $lang;
	$tracking_verdict = ip_track($where, $quote_num);
	if($where != 'flag'){
		switch($tracking_verdict){
			case 1:
				echo $lang['tracking_check_1'];
				break;
			case 2:
				echo $lang['tracking_check_2'];
				break;
			case 3:
				echo $lang['tracking_check_3'];
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
    global $lang;
	$tracking_verdict = user_quote_status('flag', $quote_num);
	if($tracking_verdict == 1 || 2){
	    global $db;
		$res =& $db->query("SELECT flag FROM rash_quotes WHERE id = {$quote_num} LIMIT 1");
		$row = $res->fetchRow(DB_FETCHMODE_ORDERED);
		if($row[0] == 2){
			echo $lang['flag_previously_flagged'];
		}
		elseif($row[0] == 1){
			echo $lang['flag_currently_flagged'];
		}
		else{
			echo $lang['flag_quote_flagged'];
			$db->query("UPDATE rash_quotes SET flag = 1 WHERE id = {$quote_num}");
		}
	}
}

// function vote($quote_num, $method)
// This function increments or decrements the rating of the quote in rash_quotes.
//
function vote($quote_num, $method)
{
    global $db;
	$tracking_verdict = user_quote_status('vote', $quote_num);
	if($tracking_verdict == 3){
		printfooter();
		exit();
	}
	if($tracking_verdict == 1 || 2){
		if($method == "plus")
			$db->query("UPDATE rash_quotes SET rating = rating+1 WHERE id = {$quote_num}");
		elseif($method == "minus")
			$db->query("UPDATE rash_quotes SET rating = rating-1 WHERE id = {$quote_num}");
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
	}


	$res =& $db->query("SELECT ip FROM rash_tracking WHERE ip='".getenv("REMOTE_ADDR")."'");
	if (DB::isError($res)) {
		die($res->getMessage());
	}

	if($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){ // if ip is in database
		$res->free();
		$res =& $db->query("SELECT quote_id FROM rash_tracking WHERE ip='".getenv("REMOTE_ADDR")."'");
		if (DB::isError($res)) {
			die($res->getMessage());
		}
		$quote_array = $res->fetchRow(DB_FETCHMODE_ORDERED);
		$quote_array = explode(",", $quote_array[0]);
		$quote_place = array_search($quote_num, $quote_array);
		if(in_array($quote_num, $quote_array)){
			$res2 =& $db->query("SELECT $where FROM rash_tracking WHERE ip='".getenv("REMOTE_ADDR")."'");
			if (DB::isError($res)) {
				die($res->getMessage());
			}
			$where_result = $res2->fetchRow(DB_FETCHMODE_ORDERED);
			$where_result = explode(",", $where_result[0]);
			if(!$where_result[$quote_place]){
				$where_result[$quote_place] = 1;
				$where_result = implode(",", $where_result);
				$db->query("UPDATE rash_tracking SET $where='$where_result' WHERE ip='".getenv("REMOTE_ADDR")."'");
				if (DB::isError($res)) {
					die($res->getMessage());
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
			$res =& $db->query("SELECT quote_id FROM rash_tracking WHERE ip='".getenv("REMOTE_ADDR")."'");
			if (DB::isError($res)) {
				die($res->getMessage());
			}
			$row = $res->fetchRow(DB_FETCHMODE_ORDERED);
			$row[] = $quote_num;
			$db->query("UPDATE rash_tracking SET quote_id = '".implode(",", $row)."' WHERE ip='".getenv("REMOTE_ADDR")."'");
			if (DB::isError($res)) {
				die($res->getMessage());
			}
			$res->free();

			// Update $where
			$res =& $db->query("SELECT $where FROM rash_tracking WHERE ip='".getenv("REMOTE_ADDR")."'");
			if (DB::isError($res)) {
				die($res->getMessage());
			}
			$row = $res->fetchRow(DB_FETCHMODE_ORDERED);
			$row[] = '1';
			$db->query("UPDATE rash_tracking SET $where = '".implode(",", $row)."'");
			if (DB::isError($res)) {
				die($res->getMessage());
			}
			$res->free();

			// Update $where2
			$res =& $db->query("SELECT $where2 FROM rash_tracking WHERE ip='".getenv("REMOTE_ADDR")."'");
			if (DB::isError($res)) {
				die($res->getMessage());
			}
			$row = $res->fetchRow(DB_FETCHMODE_ORDERED);
			$row[] = '0';
			$db->query("UPDATE rash_tracking SET $where2 = '".implode(",", $row)."'");
			if (DB::isError($res)) {
				die($res->getMessage());
			}
			$res->free();

			return 1;
		}
	}
	else{ // if ip isn't in database, add it and appropriate quote action
		$res = $db->query("INSERT INTO rash_tracking (ip, quote_id, $where, $where2) VALUES('".getenv("REMOTE_ADDR")."', ".$quote_num.", 1, 0);");
		if (DB::isError($res)) {
			die($res->getMessage());
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
    global $db, $lang;

    $res =& $db->query("SELECT * FROM rash_news ORDER BY date desc LIMIT 5");
    if(DB::isError($res)){
	die($res->getMessage());
    }

    $news = '';

    while ($row=$res->fetchRow(DB_FETCHMODE_ASSOC)) {
	$news .= '<div class="home_news_date">'.date('Ymd', $row['date']).'</div>';
	$news .= '<div class="home_news_news">'.$row['news'].'</div>';
    }

?>
  <div id="home_all">
   <div id="home_news"><?=$news?></div>
   <div id="home_greeting"><?=$lang['home_greeting']?></div>
  </div>
<?
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
	$numrows = $db->getOne("SELECT COUNT(id) FROM rash_quotes");
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

	echo "    &nbsp;&nbsp;<a href=\"?".urlargs(strtolower($origin),$pagenum)."\">".$lang['page_first']."</a>\n";
	echo "   </div>\n";
}

/************************************************************************
************************************************************************/

// quote_generation()
//
// This is the rugged function that pulls quotes out of the rash_quotes table
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
    global $CONFIG, $db, $lang;
    if ($page != -1) {
	print '<div id="quote_all">';
	if(!$page)
	    $page = 1;

	page_numbers($origin, $quote_limit, $page, $page_limit);

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
?>
   <div class="quote_whole">
    <div class="quote_option-bar">
     <a href="?<?=$row['id']?>" class="quote_number">#<?=$row['id']?></a>
     <a href="?<?=urlargs('vote',$row['id'],'plus')?>" class="quote_plus" title="<?=$lang['upvote']?>">+</a>
     <span class="quote_rating">(<?=$row['rating']?>)</span>
     <a href="?<?=urlargs('vote',$row['id'],'minus')?>" class="quote_minus" title="<?=$lang['downvote']?>">-</a>
     <a href="?<?=urlargs('flag',$row['id'])?>" class="quote_flag" title="<?=$lang['flagquote']?>">[X]</a>
<?
	// if a date is requested in the query (ie. SELECT * FROM or SELECT quote, date, flag, ect. FROM)
	// it will present the date, but the date isn't always wanted, so it is only echoed if it's
	// initialized by dumping the query results into an array
		if(isset($row['date'])) {
			date_default_timezone_set('America/New_York');
			echo "     <span class=\"quote_date\">" . date("F j, Y", $row['date']) . "</span>\n";
		}
?>
    </div>
    <div class="quote_quote">
     <?=nl2br($row['quote'])."\n"?>
    </div>
   </div>
<?
	}
	if($page != -1){
	    print '<div class="quote_pagenums">';
	    page_numbers($origin, $quote_limit, $page, $page);
	    print '</div>';
	}
}



function add_news($method)
{
    global $CONFIG, $db;
	date_default_timezone_set('America/New_York');
	if($method == 'submit')
	{
	    $_POST['news'] = nl2br($_POST['news']);
	    $db->query("INSERT INTO rash_news (news,date) VALUES('${_POST['news']}', '".mktime()."');");
	}
?>
  <div id="admin_add-news_all">
   <div id="admin_add-news_title">
    Add News
   </div>
   <form method="post" action="?<?=urlargs('add_news','submit')?>">
	<textarea cols="80" rows="5" name="news" id="add_news_news"></textarea><br />
	<input type="submit" value="Add News" id="add_news" />
   </form>
  </div>
<?
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
    global $CONFIG, $db;
	if($method == 'update'){
		$db->query("INSERT INTO rash_users (user, password, level, salt) VALUES('${_POST['username']}', '".crypt($_POST['password'], "\$1\$".substr($_POST['salt'], 0, 8)."\$")."', '${_POST['level']}', '\$1\$".$_POST['salt']."\$');");
		if (DB::isError($res)) {
		    die($res-> getMessage());
		}
	}
?>
  <div id="admin_add-user_all">
   <div id="admin_add-user_title">
    Add User
   </div>
   <form method="post" action="?<?=urlargs('add_user','update')?>">
    Username: <input type="text" name="username" id="admin_add-user_username" /><br />
	RANDOM Salt: <input type="text" name="salt" value="<?=str_rand(8,'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')?>" id="admin_add-user_salt" /><br />
	Default Password: <input type="text" name="password" /><br />
       Level: <?=user_level_select()?><br />
	 <input type="submit" value="Submit" id="admin_add-user_submit" />
   </form>
  </div>
<?
}

function change_pw($method, $who)
{
    global $CONFIG, $db;
	if($method == 'update'){
		// created to keep errors at a minimum
		$row['salt'] = 0;

		$res =& $db->query("SELECT `password`, salt FROM rash_users WHERE user='$who'");
		$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
		$salt = "\$1\$".str_rand(8,'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890')."\$";

		if((md5($_POST['old_password']) == $row['password']) || (crypt($_POST['old_password'], $row['salt']) == $row['password'])){
			if($_POST['verify_password'] == $_POST['new_password']){
				$db->query("UPDATE rash_users SET `password`='".crypt($_POST['new_password'], $salt)."', salt='$salt' WHERE user='$who'");
				echo "Password updated!";
			}
		}
	}
?>
  <div id="admin_change-pw_title">
   Change Password
  </div>
  <form action="?<?=urlargs('change_pw','update',$_SESSION['user'])?>" method="post">
   Old Password: <input type="password" name="old_password"><br />
   New Password: <input type="password" name="new_password"><br />
   Verify: <input type="password" name="verify_password"><br />
   <input type="submit">
  </form>
<?
}

function edit_users($method, $who)
{
    global $CONFIG, $db;
	if($method == 'delete'){	// delete a user from rash_users
		if($_POST['verify']){
			$res =& $db->query("SELECT * FROM rash_users");
			while($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
			{
				if(isset($_POST['d'.$row['user']])){
					$db->query("DELETE FROM rash_users WHERE user='{$_POST['d'.$row['user']]}'");
					echo $row['user']." has been removed from the userlist!<br />\n";
				}
			}
		}
	}
	if($method == 'update'){	// parse the info from $method == 'edit' into the database
		$db->query("UPDATE rash_users SET user='".strtolower($_POST['user'])."', level=${_POST['level']} WHERE user='$who'");
		if($_POST['password'])
			$db->query("UPDATE rash_users SET `password`='".md5($_POST['password'])."' WHERE user='$who'");
	}
	if($method == 'edit'){		// take input from a superuser about how to change all users
								// can change username, password, or user level
		$res =& $db->query("SELECT * FROM rash_users WHERE user='$who'");
		$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
?>
  <span style="font-style: underline">Editing user <?=$who?></span>
  <form action="?<?=urlargs('users','update',$who)?>" method="post">
   New Username: <input type="text" value="<?=$row['user']?>" name="user"><br />
   New Password: <input type="text" name="password"> (insert as cleartext, the program will encrypt it or leave it blank for no pw change)<br />
      New Level: <?=user_level_select($row['level'])?>
   <input type="submit">
  </form>
<?
	}
?>
  <div id="admin_users_title">
   Users
  </div>
  <form action="?<?=urlargs('users','delete')?>" method="post">
   <table border="1" cellpadding="1" cellspacing="0" style="border-style: solid;border-color: #125443">
    <tr>
     <td>
      &nbsp;Username&nbsp;
     </td>
     <td>
      &nbsp;PW_Hash&nbsp;
     </td>
     <td>
      &nbsp;Level&nbsp;
     </td>
     <td>
      &nbsp;Delete&nbsp;
     </td>
    </tr>
<?
	$res =& $db->query("SELECT * FROM rash_users");
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
	{
?>
    <tr>
     <td>
      <a href="?<?=urlargs('users','edit',$row['user'])?>"><?=$row['user']?></a>
     </td>
     <td>
      <a href="?<?=urlargs('users','edit',$row['user'])?>"><?=$row['password']?></a>
     </td>
     <td>
      <a href="?<?=urlargs('users','edit',$row['user'])?>"><?=$row['level']?></a>
     </td>
     <td>
      <input type="checkbox" name="d<?=$row['user']?>" value="<?=$row['user']?>" />
    </tr>
<?
	}
?>
  </table>
  <input type="submit" value="Submit" />&nbsp;I'm sure: <input type="checkbox" name="verify" value="1" />
 </form>
<?
}

// login($method)
//
function login($method)
{
    global $CONFIG, $db, $lang;
	if(!$method){

   print $lang['admin_login_greeting'];
   print '<form action="?'.urlargs('admin','login').'" method="post">
    Username: <input type="text" name="rash_username" size="8" id="admin_login_username-box" /><br />
    Password: <input type="password" name="rash_password" size="8" id="admin_login_password-box" /><br />
    <input type="submit" value="Log In" id="admin_login_submit-button" />
   </form>';

	}
	elseif($method == 'login'){
		$res =& $db->query("SELECT salt FROM rash_users WHERE user='".strtolower($_POST['rash_username'])."'");
		$salt = $res->fetchRow(DB_FETCHMODE_ASSOC);

		// if there is no presence of a salt, it is probably md5 since old rash used plain md5
		if(!$salt['salt']){
			$res =& $db->query("SELECT user, password, level FROM rash_users WHERE user='".strtolower($_POST['rash_username'])."' AND `password` ='".md5($_POST['rash_password'])."'");
			$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
			echo $row['user'];

		}
		// if there is presense of a salt, it is probably new rash passwords, so it is salted md5
		else{
			$res =& $db->query("SELECT user, password, level FROM rash_users WHERE user='".strtolower($_POST['rash_username'])."' AND `password` ='".crypt($_POST['rash_password'], $salt['salt'])."'");
			$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
		}

		// if there is no row returned for the user, the password is expected to be false because of the AND conditional in the query
		if(!$row['user']){
			echo $lang['login_error'];
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
// This function displays the queue of quotes in the table rash_queue, input from users is sent
// to rash_queue and an administrator has the privileges to send that quote into the main quote
// database to be viewed by the public, or purge it from the system.
//

function quote_queue($method)
{
    global $CONFIG, $db;
	if($method == 'judgement'){ // $method is a variable that is passed to the function to tell it how to act
								// setting it to judgement tells the program to take moderator radio button input
								// and either let the quotes into rash_quotes or purge them
		$res =& $db->query("SELECT * FROM rash_queue");
		$x = 0;
		while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
			if($_POST['q'.$row['id']]){ // sets up an array that can be looped through containing the ids of all the
										// quotes that have been voted yes or no on
				$judgement_array[$x] = $_POST['q'.$row['id']];
				$x++;
			}
		}
		$x = 0;
		date_default_timezone_set('America/New_York');
		while($judgement_array[$x]){	// itinerates through $judgement_array, stops when it gets to the end of the quote list
			if(substr($judgement_array[$x], 0, 1) == 'y'){	// checks to see if the first letter of
															// the entry of a quote in the array is y
															// a 'y' in there signifies it should be inserted
															// into rash_quotes
				$quote =& $db->query("SELECT quote FROM rash_queue WHERE id =".substr($judgement_array[$x], 1)." LIMIT 1");
										// query to grab the quote in question straight from rash_queue
				if (DB::isError($res)) {
					die($res->getMessage());
				}
				$row = $quote->fetchRow(DB_FETCHMODE_ASSOC);	// fetches the quote from the database
				$db->query("INSERT INTO `rash_quotes` (quote, rating, flag, date) VALUES ('".addslashes($row['quote'])."', 0, 0, '".mktime()."');");
				echo "Quote ".substr($judgement_array[$x], 1)." added to quote database! <br />";
															// inserts the quote into rash_quotes and gives a confirmation message
			}
			$db->query("DELETE FROM rash_queue WHERE id =".substr($judgement_array[$x], 1).";");
															// the quote is deleted from rash_queue regardless if it is
															// submitted into rash_quotes or not, since there's no reason
															// for it to be there if it is checked as no or as yes
			echo "Quote ".substr($judgement_array[$x], 1)." deleted from temporary database!<br />";
			$x++;	// increments x so the judgement_array goes to the next item
		}
	}
?>
  <div id="admin_queue_title">
   Queue
  </div>
<?
	$res =& $db->query("SELECT * FROM rash_queue order by id asc");
					// query to grab all of the queued quotes to display
	if (DB::isError($res)){
		die($res->getMessage());
	}
?>
  <form action="?<?=urlargs('queue','judgement')?>" method="post">
   <table width="100%" cellspacing="0" class="admin_queue">
<?
	$x = 0;
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){ // will itinerate through each entry in rash_queue
?>
     <tr>
      <td class="quote_no">
       <label>No<input type="radio" name="q<?=$row['id']?>" value="n<?=$row['id']?>"></label>
      </td>
      <td>
        <div class="quote_quote">
<?
		echo nl2br("       ".$row['quote']); // displays quote with appropriate line breaks
?>
        </div>

      </td>
	  <td class="quote_yes">
       <label><input type="radio" name="q<?=$row['id']?>" value="y<?=$row['id']?>" style="text-align: right">Yes</label>
	  </td>
     </tr>
    </div>

<?
		$x++;
	}
?>
   </table>
   <input type="submit" value="Submit Query" />
   <input type="reset" value="Reset" />
  </form>
<?
}
// End quote_queue()


// flag_queue($method)
//
//

function flag_queue($method)
{
    global $CONFIG, $db;
	if($method == 'judgement'){
		// $result = mysql_query("SELECT * FROM rash_quotes WHERE `check` =0");
		$res =& $db->query("SELECT * FROM rash_quotes WHERE flag = 1");

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
				// mysql_query("UPDATE rash_quotes SET `check` =2 WHERE id =".substr($judgement_array[$x], 1).";");
				$db->query("UPDATE rash_quotes SET flag = 2 WHERE id =".substr($judgement_array[$x], 1).";");
				echo "Quote ".substr($judgement_array[$x], 1)." has been unflagged! <br />";
			}
			if(substr($judgement_array[$x], 0, 1) == 'd'){
				//mysql_query("DELETE FROM rash_quotes WHERE id =".substr($judgement_array[$x], 1).";");
				$db->query("DELETE FROM rash_quotes WHERE id=".substr($judgement_array[$x], 1).";");
				echo "Quote ".substr($judgement_array[$x], 1)." deleted from database!<br />";
			}
			$x++;
		}
	}
?>
<div id="admin_flag_title">
 Flags
</div>
<?
//$result = mysql_query("SELECT * FROM rash_quotes WHERE `check` =0 order by id asc");
$res =& $db->query("SELECT * FROM rash_quotes WHERE flag = 1 ORDER BY id ASC");
?>
<form action="?<?=urlargs('flag_queue','judgement')?>" method="post">
<table width="100%" class="admin_queue">
<?
$x = 0;
while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
?>
<tr>
<td class="quote_delete">
	<label>Delete<input type="radio" name="q<?=$row['id']?>" value="d<?=$row['id']?>"></label>
</td>
<td>
<div class="quote_quote">
<?
	echo nl2br($row['quote']);
?>
</div>
</td>
<td class="quote_unflag">
	<label><input type="radio" name="q<?=$row['id']?>" value="u<?=$row['id']?>">Unflag</label>
</td>
</tr>
<?
	$x++;
}
?>
</table>
<input type="submit" value="Submit Query" />
<input type="reset" value="Reset" />
</form>
<?
}


// search($method)
// This takes a user to the page where they can put words in to search for
// quotes with those words in it. Pretty simple.
//

function search($method)
{
    global $CONFIG, $lang;
    if ($method == 'fetch') {
	if($_POST['sortby'] == 'rating')
	    $how = 'desc';
	else
	    $how = 'asc';
	$query = "SELECT id, quote, rating, flag FROM rash_quotes WHERE quote LIKE '%{$_POST['search']}%' ORDER BY {$_POST['sortby']} $how LIMIT {$_POST['number']}";
	quote_generation($query, $lang['search_results_title'], -1);
    }

    print '<div class="search_all">';

    if($method != 'fetch') {
	print '<div id="search_title">'.$lang['search_title'].'</div>';
    }

    print '<form method="post" action="?'.urlargs('search','fetch').'">';
    if ($method == 'fetch') { print '<input type="submit" name="submit" id="search_submit-button">&nbsp;'; }
    print '<input type="text" name="search" size="28" id="search_query-box">&nbsp;';
    if ($method != 'fetch') { print '<input type="submit" name="submit" id="search_submit-button">&nbsp;<br />'; }
    print $lang['search_sort'].': <select name="sortby" size="1" id="search_sortby-dropdown">';
    print '<option selected>'.$lang['search_opt_rating'];
    print '<option>'.$lang['search_opt_id'];
    print '</select>';

    print '&nbsp;';

    print $lang['search_howmany'].': <select name="number" size="1" id="search_limit-dropdown">
     <option selected>10
     <option>25
     <option>50
     <option>75
     <option>100
    </select>';

    print '</form>';

    print '</div>';

}


// add_quote()
// This function serves as the page catering to ?add, it can receive input
// from an HTML form that will be inserted into rash_queue for viewing when
// logged in as an administrator.

function add_quote($method)
{
    global $CONFIG, $db, $lang;

    print '<div id="add_all">';

    print '<div id="add_title">'.$lang['add_title'].'</div>';

    if ($method == 'submit') {
	// take $_POST['quote'] and echo it to the screen, then
	// run it through addslashes() and htmlspecialchars()
	// and then insert it into rash_submit mysql table

	$quotxt = addslashes(htmlspecialchars(trim($_POST["rash_quote"])));

	print '<div id="add_outputmsg">';

	print '<div id="add_outputmsg_top">'.$lang['add_outputmsg_top'].'</div>';
	print '<div id="add_outputmsg_quote">'.nl2br($quotxt).'</div>';
	print '<div id="add_outputmsg_bottom">'.$lang['add_outputmsg_bottom'].'</div>';

	print '</div>';

	$res =& $db->query("INSERT INTO rash_queue (quote) VALUES('".$quotxt."');");
	if(DB::isError($res)){
	    die($res->getMessage());
	}
    }

    print '<form action="?'.urlargs('add','submit').'" method="post">
     <textarea cols="80" rows="5" name="rash_quote" id="add_quote"></textarea><br />
     <input type="submit" value="'.$lang['add_quote_btn'].'" id="add_submit" />
     <input type="reset" value="'.$lang['add_reset_btn'].'" id="add_reset" />
    </form>';

    print '</div>';
}



$page[1] = 0;
$page[2] = 0;
$page = explode($CONFIG['GET_SEPARATOR'], $_SERVER['QUERY_STRING']);

if(!($page[0] == 'rss'))
    printheader(title($page[0]), $CONFIG['site_short_title'], $CONFIG['site_long_title']); // templates/x_template/x_template.php

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
    die($db->getMessage());
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
		echo "  <div id=\"admin_all\">\n";
		login($page[1]);
		echo "  </div>\n";
		break;
	case 'bottom':
		$query = "SELECT id, quote, rating, flag, date FROM rash_quotes WHERE rating < 0 ORDER BY rating ASC LIMIT 50";
		quote_generation($query, $lang['bottom_title'], -1);
		break;
	case 'browse':
		$query = "SELECT id, quote, rating, flag, date FROM rash_quotes ORDER BY id ASC ";
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
		$query = "SELECT id, quote, rating, flag, date FROM rash_quotes ORDER BY id DESC LIMIT 50";
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
		$query = "SELECT id, quote, rating, flag, date FROM rash_quotes ORDER BY rand() LIMIT 50";
		quote_generation($query, $lang['random_title'], -1);
		break;
	case 'random2':
		$query = "SELECT id, quote, rating, flag, date FROM rash_quotes WHERE rating > 1 ORDER BY rand() LIMIT 50";
		quote_generation($query, $lang['random2_title'], -1);
		break;
	case 'rss':
	    rash_rss();
	    break;
	case 'search':
		search($page[1]);
		break;
	case 'top':
		$query = "SELECT id, quote, rating, flag FROM rash_quotes WHERE rating > 0 ORDER BY rating DESC LIMIT 50";
		quote_generation($query, $lang['top_title'], -1);
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
		$query = "SELECT id, quote, rating, flag FROM rash_quotes WHERE id ='${_SERVER['QUERY_STRING']}' ";
		quote_generation($query, "#${_SERVER['QUERY_STRING']}", -1);
	    } else {
		home_generation();
	    }

}
if(!($page[0] == 'rss'))
    printfooter();	// templates/x_template/x_template.php

$db->disconnect();
