<?php

class OwnedTemplate extends BaseTemplate {

function printheader($title, $topleft='Owned', $topright='Quote Database')
{
    $this->set_menu_join_str(' / ');
ob_start();
// begin editing after this line ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
 <title><?=$title?></title>
 <meta name="robots" content="noarchive,nofollow" />
 <link rel="icon" type="image/png" href="./templates/owned/favicon.png">
 <link rel="alternate" type="application/rss+xml" href="?rss" title="RSS">
 <style type="text/css" media="all">
  @import "./templates/owned/style.css";
 </style>
</head>
<body>
 <div id="site_all">
  <div id="site_nav">
   <div id="site_nav_upper">
      <div id="site_nav_upper_qms"><?=$topleft?></div>&nbsp;
      <div id="site_nav_upper_qms-long"><?=$topright?></div>
<?php

  if(!isset($_SESSION['logged_in'])){
      print '<a href="?'.urlargs('admin').'" id="site_nav_admin">'.lang('menu_admin').'</a>';
  } else {
      print '<span id="logged_in_as">'.sprintf(lang('logged_in_as'), htmlspecialchars($_SESSION['user'])).'</span>';
  }
	print '</div>';
	print $this->get_menu();
	print '</div>';
	print '<div id="content">';
}


// printfooter()
// Bottom of the document!
//
function printfooter($dbstats=null)
{
    print $this->get_messages(); /* in case the page itself didn't already get the messages */
?>
  </div>
  <p>
  <div id="site_admin_nav">
   <div id="site_admin_nav_upper">
    <div id="site_admin_nav_upper_linkbar">
<?=$this->get_menu(1);?>
    </div>
   </div>
   <div id="site_admin_nav_lower">
    <div id="site_admin_nav_lower_infobar">
     <span id="site_admin_nav_lower_infobar_pending">
<?=lang('pending_quotes').": ".$dbstats['pending_quotes'].";\n";?>
     </span>
     <span id="site_admin_nav_lower_infobar_approved">
<?=lang('approved_quotes').": ".$dbstats['approved_quotes']."\n";?>
     </span>
    </div>
   </div>
  </div>
 </div>
</body>
</html>
<?php

}



}


$TEMPLATE = new OwnedTemplate;