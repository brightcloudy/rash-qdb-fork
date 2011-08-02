<?php
require('title.php');


// printheader()
// Top of the document!
//
function printheader($title)
{
ob_start();
// begin editing after this line ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
 <title>nhqdb: <?=title($title)?></title>
 <meta name="robots" content="noarchive,nofollow" />
 <link rel="alternative" type="text/xml" title="RSS" href="?rss" />
 <style type="text/css" media="all">
  @import "./templates/bash_template/style.css";
 </style>
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-24280817-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
</head>
<body>
 <div id="site_all">
  <div id="site_nav">
   <div id="site_nav_upper">
    <div id="site_nav_upper_qms-long">
     #nethack QDB
    </div>
    <div id="site_nav_upper_qms">
     nhqdb
    </div>&nbsp;
<?

	if(!$_SESSION['logged_in']){

?>
    <a href="?admin" id="site_nav_admin">Admin</a>
<?php

	}
?>
   </div>
   <div id="site_nav_lower">
    <div id="site_nav_lower_linkbar">
     <a href="?" id="site_nav_home">Home</a> |
     <a href="?latest" id="site_nav_latest">Latest</a> |
     <a href="?browse" id="site_nav_browse">Browse</a> |
     <a href="?random" id="site_nav_random">Random</a> | 
     <a href="?random2" id="site_nav_random2">Random>0</a> |
     <a href="?bottom" id="site_nav_bottom">Bottom</a> |
     <a href="?top" id="site_nav_top">Top</a> |
     <a href="?search" id="site_nav_search">Search</a> |
     <a href="?add" id="site_nav_add">Contribute</a>
    </div>
   </div>
  </div>
<?
}


// printfooter()
// Bottom of the document!
//
function printfooter()
{
	require('settings.php');
	require('connect.php');
?>
  <div id="site_admin_nav">
   <div id="site_admin_nav_upper">
    <div id="site_admin_nav_upper_linkbar">
<?
	if(isset($_SESSION['logged_in'])){
?>
     <a href="?queue" id="site_admin_nav_upper_linkbar_queue">Quote Queue</a> |
     <a href="?flag_queue" id="site_admin_nav_upper_linkbar_flagged">Flagged Quotes</a> |
<?

		if($_SESSION['level'] < 3){

?>
     <a href="?add_news" id="site_admin_nav_upper_linkbar_add-news">Add News</a> |
<?

		}
		if($_SESSION['level'] == 1){

?>
     <a href="?users" id="site_admin_nav_upper_linkbar_users">Users</a> | 
     <a href="?add_user" id="site_admin_nav_upper_linkbar_add-user">Add User</a> |
<?

		}

?>
     <a href="?change_pw" id="site_admin_nav_upper_linkbar_change-password">Change Password</a> |
     <a href="?logout" id="site_admin_nav_upper_linkbar_logout">Log Out</a>
<?

	}

?>
    </div>
   </div>
   <div id="site_admin_nav_lower">
    <div id="site_admin_nav_lower_infobar">
     <span id="site_admin_nav_lower_infobar_pending">
<?

		$numrows = $db->getOne('select count(id) from rash_queue');
		echo "      Pending quotes: $numrows;\n";

?>
     </span>
     <span id="site_admin_nav_lower_infobar_approved">
<?

	$numrows = $db->getOne('SELECT COUNT(id) FROM rash_quotes');
	echo "      Approved quotes: $numrows\n";

?>
     </span>
    </div>
   </div>
  </div>
 </div>
</body>
</html>
<?

}

?>
