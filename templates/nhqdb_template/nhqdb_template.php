<?php

class NHQDBTemplate extends BaseTemplate {

function printheader($title, $topleft='nhqdb', $topright='#NetHack Quote Database')
{
    global $lang;
ob_start();
// begin editing after this line ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
 <title><?=$title?></title>
 <meta name="robots" content="noarchive,nofollow" />
 <link rel="icon" type="image/png" href="./templates/nhqdb_template/favicon.png">
 <link rel="alternate" type="application/rss+xml" href="?rss" title="RSS">
 <style type="text/css" media="all">
  @import "./templates/nhqdb_template/style.css";
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
<script src="./templates/nhqdb_template/util.js" type="text/javascript"></script>
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
	print '</div>';
	print $this->get_menu();
	print '</div>';
}


// printfooter()
// Bottom of the document!
//
function printfooter($dbstats=null)
{
    global $lang;
    print $this->get_messages(); /* in case the page itself didn't already get the messages */
?>
  <p>
  <div id="site_admin_nav">
   <div id="site_admin_nav_upper">
    <div id="site_admin_nav_upper_linkbar">
<?
       print $this->get_menu(1);
?>
    </div>
   </div>
   <div id="site_admin_nav_lower">
    <div id="site_admin_nav_lower_infobar">
     <span id="site_admin_nav_lower_infobar_pending">
<?
	  echo $lang['pending_quotes'].": ".$dbstats['pending_quotes'].";\n";
?>
     </span>
     <span id="site_admin_nav_lower_infobar_approved">
<?
	  echo $lang['approved_quotes'].": ".$dbstats['approved_quotes']."\n";
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

function news_item($news, $date)
{
    return '<div class="home_news_whole"><div class="home_news_date">'.$date.'</div>'.
	   '<div class="home_news_news">'.$news.'</div></div>';
}

function main_page($news)
{
    global $lang;
    return $this->get_messages() . '<div id="home_all">
   <div id="home_news">'.$news.'</div>
   <div id="home_greeting">'.$lang['home_greeting'].'</div>
   </div>';
}



function add_quote_page($added_quote_html='')
{
    global $lang;
    $str = '<div id="add_all">';

    $str .= '<div id="add_title">'.$lang['add_title'].'</div>';

    $str .= $this->get_messages();

    $str .= $added_quote_html;

    $str .= '<form action="?'.urlargs('add','submit').'" method="post">
     <textarea cols="80" rows="5" name="rash_quote" id="add_quote" onkeyup="resizeTextarea(this)" onmouseup="resizeTextarea(this)" onblur="resizeTextarea(this)"></textarea><br />
     <input type="submit" value="'.$lang['add_quote_btn'].'" id="add_submit" />
     <input type="reset" value="'.$lang['add_reset_btn'].'" id="add_reset" />
    </form>';

    $str .= '<script type="text/javascript">setFocus("add_quote"); document.write("<input type=\'button\' onclick=\'javascript:mangle_quote(\"add_quote\")\' value=\''.$lang['remove_timestamps_btn'].'\'>");</script>';

    $str .= '</div>';
    return $str;
}



function edit_quote_page($quoteid, $quotetxt, $edited_quote_html='')
{
    global $lang;

    $str = '<div id="editquote_all">';

    $str .= '<div id="editquote_title">'.$lang['editquote_title'].'</div>';

    $str .= $this->get_messages();

    $str .= $edited_quote_html;

    $str .= '<form action="?'.urlargs('edit','submit', $quoteid).'" method="post">
     <textarea cols="80" rows="5" name="rash_quote" id="edit_quote" onkeyup="resizeTextarea(this)" onmouseup="resizeTextarea(this)" onblur="resizeTextarea(this)">'.$quotetxt.'</textarea><br />
     <input type="submit" value="'.$lang['edit_quote_btn'].'" id="edit_submit" />
     <input type="reset" value="'.$lang['edit_reset_btn'].'" id="edit_reset" />
    </form>';

    $str .= '<script type="text/javascript">setFocus("edit_quote"); document.write("<input type=\'button\' onclick=\'javascript:mangle_quote(\"edit_quote\")\' value=\''.$lang['remove_timestamps_btn'].'\'>");</script>';

    $str .= '</div>';

    return $str;
}

function search_quotes_page($fetched)
{
    global $lang;

    $str = '<div class="search_all">';

    if (!$fetched) {
	$str .= '<div id="search_title">'.$lang['search_title'].'</div>';
    }

    $str .= $this->get_messages();

    $str .= '<form method="post" action="?'.urlargs('search','fetch').'">';
    if ($fetched) { $str .= '<input type="submit" name="submit" id="search_submit-button">&nbsp;'; }
    $str .= '<input type="text" name="search" size="28" id="search_query-box">&nbsp;';
    if (!$fetched) { $str .= '<input type="submit" name="submit" id="search_submit-button">&nbsp;<br />'; }
    $str .= $lang['search_sort'].': <select name="sortby" size="1" id="search_sortby-dropdown">';
    $str .= '<option selected>'.$lang['search_opt_rating'];
    $str .= '<option>'.$lang['search_opt_id'];
    $str .= '</select>';

    $str .= '<script type="text/javascript">setFocus("search_query-box");</script>';

    $str .= '&nbsp;';

    $str .= $lang['search_howmany'].': <select name="number" size="1" id="search_limit-dropdown">
     <option selected>10
     <option>25
     <option>50
     <option>75
     <option>100
    </select>';

    $str .= '</form>';

    $str .= '</div>';

    return $str;
}


function flag_queue_page($inner_html)
{
    $str = '<div id="admin_flag_title">Flags</div>';

    $str .= $this->get_messages();

    $str .= '<form action="?'.urlargs('flag_queue','judgement').'" method="post">
<table width="100%" class="admin_queue">';

    $str .= $inner_html;

    $str .= '</table>
<input type="submit" value="Submit Query" />
<input type="reset" value="Reset" />
&nbsp;&nbsp;&nbsp;&nbsp;
<input type="submit" value="Unflag All" name="unflag_all">
<input type="submit" value="Delete All" name="delete_all">
Are you sure?:<input type="checkbox" name="do_all">
</form>';

    return $str;
}

function add_news_page()
{
    return '  <div id="admin_add-news_all">
   <div id="admin_add-news_title">
    Add News
   </div>' . $this->get_messages() . '
   <form method="post" action="?'.urlargs('add_news','submit').'">
	<textarea cols="80" rows="5" name="news" id="add_news_news"></textarea><br />
	<input type="submit" value="Add News" id="add_news" />
   </form>
  </div>
';

}

function add_user_page()
{
    return '  <div id="admin_add-user_all">
   <div id="admin_add-user_title">
    Add User
   </div> ' . $this->get_messages() . '
   <form method="post" action="?'.urlargs('add_user','update').'">
    Username: <input type="text" name="username" id="admin_add-user_username" /><br />
	RANDOM Salt: <input type="text" name="salt" value="'.str_rand(8,'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789').'" id="admin_add-user_salt" /><br />
	Default Password: <input type="text" name="password" /><br />
       Level: '.user_level_select().'<br />
	 <input type="submit" value="Submit" id="admin_add-user_submit" />
   </form>
  </div>
';
}

function change_password_page()
{
    return '  <div id="admin_change-pw_title">
   Change Password
  </div> ' . $this->get_messages() . '
  <form action="?'.urlargs('change_pw','update',$_SESSION['user']).'" method="post">
   Old Password: <input type="password" name="old_password"><br />
   New Password: <input type="password" name="new_password"><br />
   Verify: <input type="password" name="verify_password"><br />
   <input type="submit">
  </form>
';
}



function edit_user_page_table($innerhtml)
{
    $str = '  <div id="admin_users_title">
   Users
  </div>' . $this->get_messages() . '
  <form action="?'.urlargs('users','delete').'" method="post">
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
';

    $str .= $innerhtml;

    $str .= '  </table>
  <input type="submit" value="Submit" />&nbsp;I\'m sure: <input type="checkbox" name="verify" value="1" />
 </form>
';

    return $str;
}

function login_page()
{
    global $lang;

    return $this->get_messages() . '<div id="admin_all">'.$lang['admin_login_greeting'].
	'<form action="?'.urlargs('admin','login').'" method="post">
    Username: <input type="text" name="rash_username" size="8" id="admin_login_username-box" /><br />
    Password: <input type="password" name="rash_password" size="8" id="admin_login_password-box" /><br />
    <input type="submit" value="Log In" id="admin_login_submit-button" />
   </form></div>';
}


function quote_queue_page($innerhtml)
{
    $str = '<div id="admin_queue_title">Queue</div>';

    $str .= $this->get_messages();

    $str .= '  <form action="?'.urlargs('queue','judgement').'" method="post">
   <table width="100%" cellspacing="0" class="admin_queue">';

    $str .= $innerhtml;

    $str .= '   </table>
   <input type="submit" value="Submit Query" />
   <input type="reset" value="Reset" />
  </form>
';

    return $str;
}


function quote_iter($quoteid, $rating, $quotetxt, $date=null)
{
    global $lang;
    $str = '<div class="quote_whole">
    <div class="quote_separator">&nbsp;</div>
    <div class="quote_option-bar">
     <a href="?'.$quoteid.'" class="quote_number">#'.$quoteid.'</a>
     <a href="?'.urlargs('vote',$quoteid,'plus').'" class="quote_plus" title="'.$lang['upvote'].'">+</a>
     <span class="quote_rating">('.$rating.')</span>
     <a href="?'.urlargs('vote',$quoteid,'minus').'" class="quote_minus" title="'.$lang['downvote'].'">-</a>
     <a href="?'.urlargs('flag',$quoteid).'" class="quote_flag" title="'.$lang['flagquote'].'">[X]</a>
     '.edit_quote_button($quoteid);

    if (isset($date)) {
	$str .= "     <span class=\"quote_date\">" . $date . "</span>\n";
    }

    $str .= '
    </div>
    <div class="quote_quote">
     '.$quotetxt.'
    </div>
   </div>
';
    return $str;
}




}


$TEMPLATE = new NHQDBTemplate;