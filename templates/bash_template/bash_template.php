<?php

// printheader()
// Top of the document!
//
function printheader($title, $topleft='QMS', $topright='Quote Management System')
{
    global $lang;
ob_start();
// begin editing after this line ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
 <title><?=$title?></title>
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
      <div id="site_nav_upper_qms-long"><?=$topright?>
    </div>
      <div id="site_nav_upper_qms"><?=$topleft?>
    </div>&nbsp;
<?

	if(!$_SESSION['logged_in']){

?>
    <a href="?admin" id="site_nav_admin"><?=$lang['menu_admin']?></a>
<?php

	}
?>
   </div>
   <div id="site_nav_lower">
    <div id="site_nav_lower_linkbar">
     <a href="?" id="site_nav_home"><?=$lang['menu_home']?></a> |
     <a href="?latest" id="site_nav_latest"><?=$lang['menu_latest']?></a> |
     <a href="?browse" id="site_nav_browse"><?=$lang['menu_browse']?></a> |
     <a href="?random" id="site_nav_random"><?=$lang['menu_random']?></a> |
     <a href="?random2" id="site_nav_random2"><?=$lang['menu_random2']?></a> |
     <a href="?bottom" id="site_nav_bottom"><?=$lang['menu_bottom']?></a> |
     <a href="?top" id="site_nav_top"><?=$lang['menu_top']?></a> |
     <a href="?search" id="site_nav_search"><?=$lang['menu_search']?></a> |
     <a href="?add" id="site_nav_add"><?=$lang['menu_contribute']?></a>
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
    global $db, $lang;
?>
  <div id="site_admin_nav">
   <div id="site_admin_nav_upper">
    <div id="site_admin_nav_upper_linkbar">
<?
	if(isset($_SESSION['logged_in'])){
?>
     <a href="?queue" id="site_admin_nav_upper_linkbar_queue"><?=$lang['menu_queue']?></a> |
     <a href="?flag_queue" id="site_admin_nav_upper_linkbar_flagged"><?=$lang['menu_flagged']?></a> |
<?

		if($_SESSION['level'] < 3){

?>
     <a href="?add_news" id="site_admin_nav_upper_linkbar_add-news"><?=$lang['menu_addnews']?></a> |
<?

		}
		if($_SESSION['level'] == 1){

?>
     <a href="?users" id="site_admin_nav_upper_linkbar_users"><?=$lang['menu_users']?></a> |
     <a href="?add_user" id="site_admin_nav_upper_linkbar_add-user"><?=$lang['menu_adduser']?></a> |
<?

		}

?>
     <a href="?change_pw" id="site_admin_nav_upper_linkbar_change-password"><?=$lang['menu_changepass']?></a> |
     <a href="?logout" id="site_admin_nav_upper_linkbar_logout"><?=$lang['menu_logout']?></a>
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
		echo $lang['pending_quotes'].": $numrows;\n";

?>
     </span>
     <span id="site_admin_nav_lower_infobar_approved">
<?

	$numrows = $db->getOne('SELECT COUNT(id) FROM rash_quotes');
	echo $lang['approved_quotes'].": $numrows\n";

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
