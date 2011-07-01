<?
session_start();
require_once 'DB.php';
require('settings.php');
require('connect.php');
require($template);
require('contgen.php');
require('admin_funcs.php');
require('user_funcs.php');
require('qmod.php');
require('qfind.php');
require('ip_tracking.php');
require('verify_int.php');
require('rss.php');



$page[1] = 0;
$page[2] = 0;
$page = explode($GET_SEPARATOR, $_SERVER['QUERY_STRING']);

if(!($page[0] == 'rss'))
    printheader($page[0]); // templates/x_template/x_template.php
switch($page[0])
{
	case 'add':
		add_quote($page[1]); // user_funcs.php
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
		login($page[1]); // admin_funcs.php
		echo "  </div>\n";
		break;
	case 'bottom':
		$query = "SELECT id, quote, rating, flag FROM rash_quotes WHERE rating < 0 ORDER BY rating ASC LIMIT 50";
		quote_generation($query, "Bottom", -1); // contgen.php
		break;
	case 'browse':
		$query = "SELECT id, quote, rating, flag FROM rash_quotes ORDER BY id ASC ";
		quote_generation($query, "Browse", $page[1], $quote_limit, $page_limit); // contgen.php
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
		$query = "SELECT id, quote, rating, flag FROM rash_quotes ORDER BY id DESC LIMIT 50";
		quote_generation($query, "Latest", -1); // contgen.php
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
		$query = "SELECT id, quote, rating, flag FROM rash_quotes ORDER BY rand() LIMIT 50";
		quote_generation($query, "Random", -1); // contgen.php
		break;
	case 'random2':
		$query = "SELECT id, quote, rating, flag FROM rash_quotes WHERE rating > 1 ORDER BY rand() LIMIT 50";
		quote_generation($query, "Random2", -1); // contgen.php
		break;
    case 'rss':
        rash_rss();
        break; 
	case 'search':
		search($page[1]);
		break;
	case 'top':
		$query = "SELECT id, quote, rating, flag FROM rash_quotes WHERE rating > 0 ORDER BY rating DESC LIMIT 50";
		quote_generation($query, "Top", -1); // contgen.php
		break;
	case 'users':
		if($_SESSION['logged_in'])
			edit_users($page[1], $page[2]); // admin_funcs.php
		break;
	case 'vote':
		vote($page[1], $page[2]);
		break;
	default:
		if(is_int($_SERVER['QUERY_STRING']) || $_SERVER['QUERY_STRING'] != false)
		{
			if(verify_int($_SERVER['QUERY_STRING']))
			{
				$query = "SELECT id, quote, rating, flag FROM rash_quotes WHERE id ='${_SERVER['QUERY_STRING']}' ";
				quote_generation($query, "#${_SERVER['QUERY_STRING']}", -1); // contgen.php
			}
			else
			{
				home_generation(); // contgen.php
			}
		}
		else
		{
			home_generation(); // contgen.php
		}
		
}
if(!($page[0] == 'rss'))
    printfooter();	// templates/x_template/x_template.php
$db->disconnect();	// kill database connection
?>
