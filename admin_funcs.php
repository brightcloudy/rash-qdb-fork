<?

function add_news($method)
{
	require('settings.php');
	require('connect.php');
	require('str_rand.php');
	date_default_timezone_set('America/New_York');
	$today = mktime(date('G'),date('i'),date('s'),date('m'),date('d'),date('Y'));	// this timestamp is inserted to rash_quotes
	if($method == 'submit')
	{
		$db->query("INSERT INTO rash_news (news,date) VALUES('${_POST['news']}', '".$today."');");
	}
?>
  <div id="admin_add-news_all">
   <div id="admin_add-news_title">
    Add News
   </div>
   <form method="post" action="?add_news<?=$GET_SEPARATOR_HTML?>submit">
	<textarea cols="80" rows="5" name="news" id="add_news_news"></textarea><br />
	<input type="submit" value="Add News" id="add_news" />
   </form>
  </div>
<?
}

function add_user($method)
{
	require('settings.php');
	require('connect.php');
	require('str_rand.php');
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
   <form method="post" action="?add_user<?=$GET_SEPARATOR_HTML?>update">
    Username: <input type="text" name="username" id="admin_add-user_username" /><br />
	RANDOM Salt: <input type="text" name="salt" value="<?=str_rand(8,'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')?>" id="admin_add-user_salt" /><br />
	Default Password: <input type="text" name="password" /><br />
	Level: <select name="level" size="1" id="admin_add-user_level">
      <option>1 - superuser
	  <option>2 - administrator
	  <option selected>3 - moderator
	 </select><br />
	 <input type="submit" value="Submit" id="admin_add-user_submit" />
   </form>
  </div>
<?  
}

function change_pw($method, $who)
{
	require('settings.php');
	require('connect.php');
	require('str_rand.php');
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
  <form action="?change_pw<?=$GET_SEPARATOR_HTML?>update<?=$GET_SEPARATOR_HTML?><?=$_SESSION['user']?>" method="post">
   Old Password: <input type="password" name="old_password"><br />
   New Password: <input type="password" name="new_password"><br />
   Verify: <input type="password" name="verify_password"><br />
   <input type="submit">
  </form>
<?
}

function edit_users($method, $who)
{
	require('settings.php');
	require('connect.php');
	
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
  <form action="?users<?=$GET_SEPARATOR_HTML?>update<?=$GET_SEPARATOR_HTML?><?=$who?>" method="post">
   New Username: <input type="text" value="<?=$row['user']?>" name="user"><br />
   New Password: <input type="text" name="password"> (insert as cleartext, the program will encrypt it or leave it blank for no pw change)<br />
   New Level: <select name="level">
    <option value="1" <?if($row['level']==1){echo "selected=selected";}?>>1
    <option value="2" <?if($row['level']==2){echo "selected=selected";}?>>2
    <option value="3" <?if($row['level']==3){echo "selected=selected";}?>>3
   </select>
   <input type="submit">
  </form>
<?
	}
?>
  <div id="admin_users_title">
   Users
  </div>
  <form action="?users<?=$GET_SEPARATOR_HTML?>delete" method="post">  
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
      <a href="?users<?=$GET_SEPARATOR_HTML?>edit<?=$GET_SEPARATOR_HTML?><?=$row['user']?>"><?=$row['user']?></a>
     </td>
     <td>
      <a href="?users<?=$GET_SEPARATOR_HTML?>edit<?=$GET_SEPARATOR_HTML?><?=$row['user']?>"><?=$row['password']?></a>
     </td>
     <td>
      <a href="?users<?=$GET_SEPARATOR_HTML?>edit<?=$GET_SEPARATOR_HTML?><?=$row['user']?>"><?=$row['level']?></a>
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
	require('settings.php');
	require('connect.php');
	require("language/{$language}.lng");
	if(!$method){
?>
   <?=$lang['admin_login_greeting']?>
   <form action="?admin<?=$GET_SEPARATOR_HTML?>login" method="post">
    Username: <input type="text" name="rash_username" size="8" id="admin_login_username-box" /><br />
    Password: <input type="password" name="rash_password" size="8" id="admin_login_password-box" /><br />
    <input type="submit" value="Log In" id="admin_login_submit-button" />
   </form>
<?
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



// alternate_colors($x)
// Used to determine the alternate colors for the quote queue function, it serves to make the
// output easier to read by the addition of alternating colors each itineration of the output loop.
// it works by taking a variable $x and incrementing each time the loop in quote_queue() itinerates,
// then it takes that variable and uses the modulus operator with 2, if the number's remainder is 
// 0, then it is even, if the remainder is 1, it is odd. even numebrs get alt1 color, odds get alt2 color
//

function alternate_colors($x)
{
	if(!($x%2)){ 
		echo "class=\"admin_queue_alt1\"";
	}
	elseif(($x%2) == 1){
		echo "class=\"admin_queue_alt2\"";
	}
}

// quote_queue($method)
// This function displays the queue of quotes in the table rash_queue, input from users is sent
// to rash_queue and an administrator has the privileges to send that quote into the main quote
// database to be viewed by the public, or purge it from the system.
//

function quote_queue($method)
{
	require('settings.php');
	require('connect.php');
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
		$today = mktime(date('G'),date('i'),date('s'),date('m'),date('d'),date('Y'));	// this timestamp is inserted to rash_quotes
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
				$db->query("INSERT INTO `rash_quotes` (quote, rating, flag, date) VALUES ('".addslashes($row['quote'])."', 0, 0, '$today');");
				echo "Quote ".substr($judgement_array[$x], 1)." added to quote database! <br />";
															// inserts the quote into rash_quotes and gives a confirmation message
			}
			$db->query("DELETE FROM rash_queue WHERE id =".substr($judgement_array[$x], 1).";");
															// the quote is deleted from rash_queue regardless if it is
															// submitted into rash_quotes or not, since there's no reason
															// for it to be there if it is checked as no or as yes
			echo "Quote ".substr($judgement_array[$x], 1)." deleted from temporary datbase!<br />";
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
  <form action="?queue<?=$GET_SEPARATOR_HTML?>judgement" method="post">
   <table width="100%" cellspacing="0">
<?
	$x = 0;
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){ // will itinerate through each entry in rash_queue
?>
     <tr <?alternate_colors($x);?>>
      <td>
       No<input type="radio" name="q<?=$row['id']?>" value="n<?=$row['id']?>">
      </td>
      <td>
        <div class="quote_quote">
<?
		echo nl2br("       ".$row['quote']); // displays quote with appropriate line breaks
?>
        </div>

      </td>
	  <td>
       <input type="radio" name="q<?=$row['id']?>" value="y<?=$row['id']?>" style="text-align: right">Yes
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
	require('settings.php');
	require('connect.php');

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
		date_default_timezone_set('America/New_York');
		$today = mktime(date('G'),date('i'),date('s'),date('m'),date('d'),date('Y'));
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
<form action="?flag_queue<?=$GET_SEPARATOR_HTML?>judgement" method="post">
<table width="100%">
<?
$x = 0;
while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
?>
<tr <?alternate_colors($x)?>>
<td>
	Delete<input type="radio" name="q<?=$row['id']?>" value="d<?=$row['id']?>">
</td>
<td>
<div class="quote_quote">
<?
	echo nl2br($row['quote']);
?>
</div>
</td>
<td>
	<input type="radio" name="q<?=$row['id']?>" value="u<?=$row['id']?>">Unflag
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
?>
