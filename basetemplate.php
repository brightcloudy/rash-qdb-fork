<?php

abstract class BaseTemplate {

    private $messages = array();
    private $mainmenu = array();
    private $adminmenu = array();

    function __constructor()
    {
    }

    function add_message($msg)
    {
	$this->messages[] = $msg;
    }

    function get_messages()
    {
	if (count($this->messages) > 0) {
	    $str = '<ul id="page_messages">';
	    foreach ($this->messages as $msg) {
		$str .= '<li>'.$msg;
	    }
	    $str .= '</ul>';
	    $this->messages = array();
	    return $str;
	}
	return '';
    }

    function set_menu($admin,$menudata)
    {
	if ($admin) {
	    $this->adminmenu = $menudata;
	} else {
	    $this->mainmenu = $menudata;
	}
    }

    function get_menu($admin = 0)
    {
	global $lang;
	if ($admin) { $menudata = $this->adminmenu; } else { $menudata = $this->mainmenu; }
	if ($menudata) {
	    $arr = array();
	    foreach ($menudata as $m) {
		$arr[] = '<a href="'.$m['url'].'" id="'.$m['id'].'">'.$lang[$m['txt']].'</a>';
	    }
	    $str = join(' | ', $arr);
	}
	return '<div id="site_nav_lower"><div id="site_nav_lower_linkbar">'.$str.'</div></div>';
    }

    function rss_feed_item($title, $desc, $link)
    {
	$str = "<item>\n";
	$str .= "<title>".$title."</title>\n";
	$str .= "<description>".$desc."</description>\n";
	$str .= "<link>".$link."</link>\n";
	$str .= "</item>\n\n";
	return $str;
    }

    function rss_feed($title, $desc, $link, $items)
    {
	$str = "<?xml version=\"1.0\" ?>\n";
	$str .= "<rss version=\"0.92\">\n";
	$str .= "<channel>\n";
	$str .= "<title>".$title."</title>\n";
	$str .= "<description>".$desc."</description>\n";
	$str .= "<link>".$link."</link>\n";
	$str .= $items;
	$str .= "</channel></rss>";
	return $str;
    }


    function printheader($title, $topleft='', $topright='')
    {
	return '<html><head><title>'.$title.'</title></head><body>';
    }

    function printfooter($db_stats=null)
    {
	return '</body></html>';
    }

    function news_item($news, $date)
    {
	return '<div class="home_news_date">'.$date.'</div>'.
	    '<div class="home_news_news">'.$news.'</div>';
    }

    function main_page($news)
    {
	global $lang;
	return '<div id="home_all"><div id="home_news">'.$news.'</div>
        <div id="home_greeting">'.$lang['home_greeting'].'</div></div>';
    }

    function add_quote_outputmsg($quotetxt)
    {
	global $lang;
	$str = '<div id="add_outputmsg">';
	$str .= '<div id="add_outputmsg_top">'.$lang['add_outputmsg_top'].'</div>';
	$str .= '<div id="add_outputmsg_quote">'.$quotetxt.'</div>';
	$str .= '<div id="add_outputmsg_bottom">'.$lang['add_outputmsg_bottom'].'</div>';
	$str .= '</div>';
	return $str;
    }

    function add_quote_page($added_quote_html='')
    {
	global $lang;
	$str = '<div id="add_all">';

	$str .= '<div id="add_title">'.$lang['add_title'].'</div>';

	$str .= $added_quote_html;

	$str .= '<form action="?'.urlargs('add','submit').'" method="post">
        <textarea cols="80" rows="5" name="rash_quote" id="add_quote"></textarea><br />
        <input type="submit" value="'.$lang['add_quote_btn'].'" id="add_submit" />
        <input type="reset" value="'.$lang['add_reset_btn'].'" id="add_reset" />
        </form>';

	$str .= '</div>';
	return $str;
    }


    function edit_quote_outputmsg($quotetxt)
    {
	global $lang;
	$str = '<div id="editquote_outputmsg">';

	$str .= '<div id="editquote_outputmsg_top">'.$lang['editquote_outputmsg_top'].'</div>';
	$str .= '<div id="editquote_outputmsg_quote">'.$quotetxt.'</div>';
	$str .= '<div id="editquote_outputmsg_bottom">'.$lang['editquote_outputmsg_bottom'].'</div>';

	$str .= '</div>';
	return $str;
    }

    function edit_quote_button($quoteid)
    {
	global $lang;
	return '<a href="?'.urlargs('edit','edit',$quoteid).'" class="quote_edit" title="'.$lang['editquote'].'">[E]</a>';
    }

    function edit_quote_page($quoteid, $quotetxt, $edited_quote_html='')
    {
	global $lang;

	$str = '<div id="editquote_all">';

	$str .= '<div id="editquote_title">'.$lang['editquote_title'].'</div>';

	$str .= $edited_quote_html;

	$str .= '<form action="?'.urlargs('edit','submit', $quoteid).'" method="post">
        <textarea cols="80" rows="5" name="rash_quote" id="edit_quote">'.$quotetxt.'</textarea><br />
        <input type="submit" value="'.$lang['edit_quote_btn'].'" id="edit_submit" />
        <input type="reset" value="'.$lang['edit_reset_btn'].'" id="edit_reset" />
        </form>';

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

	$str .= '<form method="post" action="?'.urlargs('search','fetch').'">';
	if ($fetched) { $str .= '<input type="submit" name="submit" id="search_submit-button">&nbsp;'; }
	$str .= '<input type="text" name="search" size="28" id="search_query-box">&nbsp;';
	if (!$fetched) { $str .= '<input type="submit" name="submit" id="search_submit-button">&nbsp;<br />'; }
	$str .= $lang['search_sort'].': <select name="sortby" size="1" id="search_sortby-dropdown">';
	$str .= '<option selected>'.$lang['search_opt_rating'];
	$str .= '<option>'.$lang['search_opt_id'];
	$str .= '</select>';

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

    function flag_queue_page_iter($quoteid, $quotetxt)
    {
	return '<tr>
<td class="quote_delete">
	<label>Delete<input type="radio" name="q'.$quoteid.'" value="d'.$quoteid.'"></label>
</td>
<td>
<div class="quote_quote">'.$quotetxt.'

</div>
</td>
<td class="quote_unflag">
	<label><input type="radio" name="q'.$quoteid.'" value="u'.$quoteid.'">Unflag</label>
</td>
</tr>';
    }

    function flag_queue_page($inner_html)
    {
	$str = '<div id="admin_flag_title">Flags</div>';

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
   </div>
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
   </div>
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
  </div>
  <form action="?'.urlargs('change_pw','update',$_SESSION['user']).'" method="post">
   Old Password: <input type="password" name="old_password"><br />
   New Password: <input type="password" name="new_password"><br />
   Verify: <input type="password" name="verify_password"><br />
   <input type="submit">
  </form>
';
    }

    function edit_user_page_form($who, $username, $level)
    {
	return '<span style="font-style: underline">Editing user '.$who.'</span>
  <form action="?'.urlargs('users','update',$who).'" method="post">
   New Username: <input type="text" value="'.$username.'" name="user"><br />
   New Password: <input type="text" name="password"> (insert as cleartext, the program will encrypt it or leave it blank for no pw change)<br />
      New Level: '.user_level_select($level).'
   <input type="submit">
  </form>';

    }

    function edit_user_page_table_row($user, $password, $level)
    {
	return '    <tr>
     <td>
      <a href="?'.urlargs('users','edit',$user).'">'.$user.'</a>
     </td>
     <td>
      <a href="?'.urlargs('users','edit',$user).'">'.$password.'</a>
     </td>
     <td>
      <a href="?'.urlargs('users','edit',$user).'">'.$level.'</a>
     </td>
     <td>
      <input type="checkbox" name="d'.$user.'" value="'.$user.'" />
    </tr>
';
    }

    function edit_user_page_table($innerhtml)
    {
	$str = '  <div id="admin_users_title">
   Users
  </div>
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

	return '<div id="admin_all">'.$lang['admin_login_greeting'].
	'<form action="?'.urlargs('admin','login').'" method="post">
    Username: <input type="text" name="rash_username" size="8" id="admin_login_username-box" /><br />
    Password: <input type="password" name="rash_password" size="8" id="admin_login_password-box" /><br />
    <input type="submit" value="Log In" id="admin_login_submit-button" />
   </form></div>';
    }

    function quote_queue_page_iter($quoteid, $quotetxt)
    {
	return '     <tr>
      <td class="quote_no">
       <label>No<input type="radio" name="q'.$quoteid.'" value="n'.$quoteid.'"></label>
      </td>
      <td>
        <div class="quote_quote">
		'.$quotetxt.'
        </div>
      </td>
	  <td class="quote_yes">
       <label><input type="radio" name="q'.$quoteid.'" value="y'.$quoteid.'" style="text-align: right">Yes</label>
	  </td>
     </tr>
';

    }

    function quote_queue_page($innerhtml)
    {
	$str = '<div id="admin_queue_title">Queue</div>';

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

    function quote_list($title, $pagenumbers, $quotes)
    {
	$str = '';
	if (isset($title))
	    $str .= '<div id="quote_origin-name">'.$title.'</div>';
	$str .= $pagenumbers;
	$str .= $quotes;
	$str .= $pagenumbers;
	return $str;
    }

}