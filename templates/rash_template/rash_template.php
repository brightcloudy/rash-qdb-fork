<?php // DO NOT edit until specified unless you know what you're doing :)

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
 <title>Rash Quote Management System</title>
 <link rel="alternative" type="text/xml" title="RSS" href="?rss" />
 <meta name="robots" content="noarchive,nofollow" />
 <style type="text/css" media="all">
  @import "./templates/rash_template/style.css";
 </style>
</head>
<body>
 <div id="site_all">
  <div id="site_image">  
   <img src="./templates/rash_template/rash.png" alt="Rash Quote Management System" />
  </div>
  <div id="site_nav">
   <a href="?" id="site_nav_home">Home</a> |
   <a href="?latest" id="site_nav_latest">Latest</a> |
   <a href="?browse" id="site_nav_browse">Browse</a> |
   <a href="?random" id="site_nav_random">Random</a> | 
   <a href="?random2" id="site_nav_random2">Random>0</a> |
   <a href="?bottom" id="site_nav_bottom">Bottom</a> |
   <a href="?top" id="site_nav_top">Top</a> |
   <a href="?search" id="site_nav_search">Search</a> |
   <a href="?add" id="site_nav_add">Contribute</a>
<?php	// STOP editing until specified

	if(!$_SESSION['logged_in']){

// begin editing after this line ?>
   <a href="?admin" id="site_nav_admin">Admin</a>
<?php	// STOP editing until specified

	}
// begin editing after this line ?>
  </div>
<?php // STOP editing until specified
}


// printfooter()
// Bottom of the document!
//
function printfooter()
{
	if(isset($_SESSION['logged_in'])){
//begin editing after this line ?>
  <div id="site_admin_nav">
   <a href="?queue" id="site_admin_nav_queue">Quote Queue</a> |
   <a href="?flag_queue" id="site_admin_nav_flagged">Flagged Quotes</a> |
<?php
		if($_SESSION['level'] < 3){
?>
   <a href="?add_news" id="site_admin_nav_news">Add News</a> |
<?php
		}
		if($_SESSION['level'] == 1){
?>
   <a href="?users" id="site_admin_nav_users">Users</a> | 
   <a href="?add_user" id="site_admin_nav_add-user">Add User</a> |
<?php
		}
?>
   <a href="?change_pw" id="site_admin_nav_changepw">Change Password</a> |
   <a href="?logout" id="site_admin_nav_logout">Log Out</a>
  </div>
<?php // STOP editing

	}

//begin editing after this line ?>
 </div>
</body>
</html>
<?php // STOP editing for the rest of the document

}

?>
