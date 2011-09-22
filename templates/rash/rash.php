<?php

class RashTemplate extends BaseTemplate {

// printheader()
// Top of the document!
//
function printheader($title, $topleft='QMS', $topright='Quote Management System')
{
ob_start();
// begin editing after this line ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
 <title><?=$title?></title>
 <link rel="alternate" type="text/xml" title="RSS" href="?rss" />
 <meta name="robots" content="noarchive,nofollow" />
 <style type="text/css" media="all">
  @import "./templates/rash/style.css";
 </style>
</head>
<body>
 <div id="rash_head_container">
 <div id="rash_head"><div id="rash_rash"><?=$topleft?></div><div id="rash_qms"><?=$topright?></div></div>
 </div>
 <div id="site_all">
  <div id="site_image">
  </div>
  <div id="site_nav">
<?php
       print $this->get_menu();
if(!isset($_SESSION['logged_in'])){
    print '<a href="?'.urlargs('admin').'" id="site_nav_admin">'.lang('menu_admin').'</a>';
} else {
    print sprintf(lang('logged_in_as'), htmlspecialchars($_SESSION['user']));
}
?>
  </div>
<?php
}


// printfooter()
// Bottom of the document!
//
function printfooter($dbstats=null)
{
?>
  <div id="site_admin_nav">
<?=$this->get_menu(1);?>
  </div>
 </div>
</body>
</html>
<?php

	}

}

$TEMPLATE = new RashTemplate;
