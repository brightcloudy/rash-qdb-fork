<?php

abstract class BaseTemplate {

    private $messages = array();
    private $mainmenu = array();
    private $adminmenu = array();
    private $menu_join_str = ' | ';
    private $menu_wrap_str = '<div id="site_nav_lower"><div id="site_nav_lower_linkbar">%s</div></div>';

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

    function set_menu_join_str($str) { $this->menu_join_str = $str; }
    function set_menu_wrap_str($str) { $this->menu_wrap_str = $str; }

    function get_menu($admin = 0)
    {
	if ($admin) { $menudata = $this->adminmenu; } else { $menudata = $this->mainmenu; }
	$str = '';
	if ($menudata) {
	    $arr = array();
	    foreach ($menudata as $m) {
		$arr[] = '<a href="'.$m['url'].'" id="'.$m['id'].'">'.lang($m['txt']).'</a>';
	    }
	    $str = join($this->menu_join_str, $arr);
	}
	return sprintf($this->menu_wrap_str, $str);
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

    function news_item($news, $date, $id=null, $mode=0)
    {
	if ($mode == 0) {
	    return '<div class="news_entry"><div class="news_date">'.$date.'</div>'.
	    '<div class="news_news">'.$news.'</div></div>';
	} else {
	    $str = '<div class="news_entry"><div class="news_date"><a href="?'.urlargs('edit_news','edit',$id).'">'.$date.'</a></div>'.
		'<div class="news_news">'.$news.'</div></div>';

	    if ($mode == 2) $str = '<div class="hilight_news_entry">'.$str.'</div>';
	    return $str;
	}
    }

    function news_page($news)
    {
	$str = '<div id="news_all">';
	$str .= '<h1 id="news_title">'.lang('news_title').'</h1>';
	$str .= $news;
	$str .= '</div>';
	return $str;
    }

    function edit_news_form($id, $news)
    {
	return '<div class="edit_news_form"><form action="?'.urlargs('edit_news','update',$id).'" method="post">
  <textarea cols="80" rows="5" name="news" id="edit_news_news">'.$news.'</textarea><br />
  <input type="submit" value="'.lang('preview_news_btn').'" id="edit_preview" name="preview" />
  <input type="submit" value="'.lang('save_news_btn').'" id="edit_news" name="submit" />
  <input type="submit" value="'.lang('delete_news_btn').'" id="delete_news" name="delete" />
  '.lang('delete_news_verify').'<input type="checkbox" name="verify_delete">
  </form></div><p>';
    }

    function edit_news_page($news)
    {
	$str = '<div id="editnews_all">';
	$str .= '<h1 id="editnews_title">'.lang('editnews_title').'</h1>';
	$str .= $news;
	$str .= '</div>';
	return $str;
    }

    function main_page($news)
    {
	return '<div id="home_all"><div id="news">'.$news.
        '<div class="show_all_news"><a href="?'.urlargs('news').'">'.lang('news_show_all').'</a></div></div>
        <div id="home_greeting">'.lang('home_greeting').'</div></div>';
    }

    function add_quote_preview($quotetxt)
    {
	$str = '<div id="add_outputmsg">';
	$str .= '<div id="addw_outputmsg_top">'.lang('preview_outputmsg_top').'</div>';
	$str .= '<div id="add_outputmsg_quote">'.$quotetxt.'</div>';
	$str .= '<div id="add_outputmsg_bottom">'.lang('preview_outputmsg_bottom').'</div>';
	$str .= '</div>';
	return $str;
    }

    function add_quote_outputmsg($quotetxt)
    {
	$str = '<div id="add_outputmsg">';
	$str .= '<div id="add_outputmsg_top">'.lang('add_outputmsg_top').'</div>';
	$str .= '<div id="add_outputmsg_quote">'.$quotetxt.'</div>';
	$str .= '<div id="add_outputmsg_bottom">'.lang('add_outputmsg_bottom').'</div>';
	$str .= '</div>';
	return $str;
    }

    function add_quote_page($quotetxt='', $added_quote_html='', $wasadded=null)
    {
	global $CAPTCHA;
	$str = '<div id="add_all">';

	$str .= '<h1 id="add_title">'.lang('add_title').'</h1>';

	$str .= $added_quote_html;

	$str .= '<form action="?'.urlargs('add','submit').'" method="post">
        <textarea cols="80" rows="5" name="rash_quote" id="add_quote">'.($wasadded ? '' : $quotetxt).'</textarea><br />';
	$str .= $CAPTCHA->get_CAPTCHA('add_quote');
        $str .= '
        <input type="submit" value="'.lang('preview_quote_btn').'" id="add_preview" name="preview" />
        <input type="submit" value="'.lang('add_quote_btn').'" id="add_submit" name="submit" />
        <input type="reset" value="'.lang('add_reset_btn').'" id="add_reset" />
        </form>';

	$str .= '</div>';
	return $str;
    }


    function edit_quote_outputmsg($quotetxt)
    {
	$str = '<div id="editquote_outputmsg">';

	$str .= '<div id="editquote_outputmsg_top">'.lang('editquote_outputmsg_top').'</div>';
	$str .= '<div id="editquote_outputmsg_quote">'.$quotetxt.'</div>';
	$str .= '<div id="editquote_outputmsg_bottom">'.lang('editquote_outputmsg_bottom').'</div>';

	$str .= '</div>';
	return $str;
    }

    function edit_quote_button($quoteid)
    {
	return '<a href="?'.urlargs('edit','edit',$quoteid).'" class="quote_edit" title="'.lang('editquote').'">[E]</a>';
    }

    function edit_quote_page($quoteid, $quotetxt, $edited_quote_html='')
    {
	$str = '<div id="editquote_all">';

	$str .= '<h1 id="editquote_title">'.lang('editquote_title').'</h1>';

	$str .= $edited_quote_html;

	$str .= '<form action="?'.urlargs('edit','submit', $quoteid).'" method="post">
        <textarea cols="80" rows="5" name="rash_quote" id="edit_quote">'.$quotetxt.'</textarea><br />
        <input type="submit" value="'.lang('edit_quote_btn').'" id="edit_submit" />
        <input type="reset" value="'.lang('edit_reset_btn').'" id="edit_reset" />
        </form>';

	$str .= '</div>';

	return $str;
    }

    function search_quotes_page($fetched, $searchstr)
    {
	$str = '<div class="search_all">';

	if (!$fetched) {
	    $str .= '<h1 id="search_title">'.lang('search_title').'</h1>';
	}

	$str .= '<form method="post" action="?'.urlargs('search','fetch').'">';
	if ($fetched) { $str .= '<input type="submit" name="submit" value="'.lang('search_btn').'" id="search_submit-button">&nbsp;'; }
	$str .= '<input type="text" name="search" size="28" id="search_query-box" value="'.$searchstr.'">&nbsp;';
	if (!$fetched) { $str .= '<input type="submit" name="submit" value="'.lang('search_btn').'" id="search_submit-button">&nbsp;<br />'; }
	$str .= lang('search_sort').': <select name="sortby" size="1" id="search_sortby-dropdown">';
	$str .= '<option selected>'.lang('search_opt_rating');
	$str .= '<option>'.lang('search_opt_id');
	$str .= '</select>';

	$str .= '&nbsp;';

	$str .= lang('search_howmany').': <select name="number" size="1" id="search_limit-dropdown">
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
	<label>'.lang('flag_quote_delete').'<input type="radio" name="q'.$quoteid.'" value="d'.$quoteid.'"></label>
</td>
<td>
<div class="quote_quote">'.$quotetxt.'

</div>
</td>
<td class="quote_unflag">
	<label><input type="radio" name="q'.$quoteid.'" value="u'.$quoteid.'">'.lang('flag_quote_unflag').'</label>
</td>
</tr>';
    }

    function flag_queue_page($inner_html)
    {
	$str = '<h1 id="admin_flag_title">'.lang('flag_quote_adminpage_title').'</h1>';

	$str .= '<form action="?'.urlargs('flag_queue','judgement').'" method="post">
<table width="100%" class="admin_queue">';

	$str .= $inner_html;

	$str .= '</table>
<input type="submit" value="'.lang('flag_quote_adminpage_submit_btn').'" />
<input type="reset" value="Reset" />
&nbsp;&nbsp;&nbsp;&nbsp;
<input type="submit" value="'.lang('flag_quote_adminpage_unflag_all_btn').'" name="unflag_all">
<input type="submit" value="'.lang('flag_quote_adminpage_delete_all_btn').'" name="delete_all">
'.lang('flag_quote_adminpage_verify').'<input type="checkbox" name="do_all">
</form>';

	return $str;
    }

    function add_news_page($previewnews, $newstxt)
    {
	$str = '  <div id="admin_add-news_all">
   <h1 id="admin_add-news_title">'.lang('add_news_title').'</h1>';
	if (isset($previewnews)) $str .= '<div class="admin_preview_news">'.$previewnews.'</div>';
	$str .= '<p>'.lang('add_news_help').'
   <form method="post" action="?'.urlargs('add_news','submit').'">
	<textarea cols="80" rows="5" name="news" id="add_news_news">'.$newstxt.'</textarea><br />
        <input type="submit" value="'.lang('preview_news_btn').'" id="add_preview" name="preview" />
        <input type="submit" value="'.lang('add_news_btn').'" id="add_news" name="submit" />
   </form>
  </div>
';
	return $str;
    }

    function register_user_page()
    {
	global $CAPTCHA;
	$str = '  <div id="register-user_all">
   <h1 id="register-user_title">'.lang('register_user_title').'</h1>
   <form method="post" action="?'.urlargs('register','update').'">
   <table>
   <tr><td>'.lang('register_user_username_label').'</td><td><input type="text" name="username" id="register-user_username" /></td></tr>
   <tr><td>'.lang('register_user_password_label').'</td><td><input type="password" name="password" /></td></tr>
   <tr><td>'.lang('register_user_verifypassword_label').'</td><td><input type="password" name="verifypassword" /></td></tr>
   <tr><td></td><td><input type="submit" value="'.lang('register_user_btn').'" id="register-user_submit" /></td></tr>
   </table>'.$CAPTCHA->get_CAPTCHA('register_user').'
   </form>
  </div>
';
	return $str;
    }

    function add_user_page()
    {
	return '  <div id="admin_add-user_all">
   <h1 id="admin_add-user_title">'.lang('add_user_title').'</h1>
   <form method="post" action="?'.urlargs('add_user','update').'">
   <table>
   <tr><td>'.lang('add_user_username_label').'</td><td><input type="text" name="username" id="admin_add-user_username" /></td></tr>
   <tr><td>'.lang('add_user_randomsalt_label').'</td><td><input type="text" name="salt" value="'.str_rand(8,'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789').'" id="admin_add-user_salt" /></td></tr>
   <tr><td>'.lang('add_user_password_label').'</td><td><input type="text" name="password" /></td></tr>
   <tr><td>'.lang('add_user_level_label').'</td><td>'.user_level_select().'</td></tr>
   <tr><td></td><td><input type="submit" value="'.lang('add_user_btn').'" id="admin_add-user_submit" /></td></tr>
   </table>
   </form>
  </div>
';
    }

    function change_password_page()
    {
	return '  <h1 id="admin_change-pw_title">'.lang('change_password_title').'</h1>
  <form action="?'.urlargs('change_pw','update',$_SESSION['userid']).'" method="post">
  <table>
  <tr><td>'.lang('change_password_oldpass').'</td><td><input type="password" name="old_password"></td></tr>
  <tr><td>'.lang('change_password_newpass').'</td><td><input type="password" name="new_password"></td></tr>
  <tr><td>'.lang('change_password_verify').'</td><td><input type="password" name="verify_password"></td></tr>
  <tr><td></td><td><input type="submit" value="'.lang('change_password_submit_btn').'"></td></tr>
  </table>
  </form>';
    }

    function edit_user_page_form($id, $who, $username, $level)
    {
	return '<h1 id="edit_user-title">'.lang('edit_user_title').' '.$username.'</h1>
  <form action="?'.urlargs('users','update',$id).'" method="post">
  <table>
  <tr><td>'.lang('edit_user_newname').'</td><td><input type="text" value="'.$username.'" name="user"></td></tr>
  <tr><td>'.lang('edit_user_newpass').'</td><td><input type="text" name="password"> '.lang('edit_user_newpass_help').'</td></tr>
  <tr><td>'.lang('edit_user_newlevel').'</td><td>'.user_level_select($level).'</td></tr>
  <tr><td></td><td><input type="submit" value="'.lang('edit_user_submit_btn').'"></td></tr>
  </table>
  </form>';

    }

    function edit_user_page_table_row($id, $user, $password, $level)
    {
	return '    <tr>
     <td>
      <a href="?'.urlargs('users','edit',$id).'">'.$id.'</a>
     </td>
     <td>
      <a href="?'.urlargs('users','edit',$id).'">'.$user.'</a>
     </td>
     <td>
      <a href="?'.urlargs('users','edit',$id).'">'.$password.'</a>
     </td>
     <td>
      <a href="?'.urlargs('users','edit',$id).'">'.$level.'</a>
     </td>
     <td>
      <input type="checkbox" name="d'.$id.'" value="'.$id.'" />
    </tr>
';
    }

    function edit_user_page_table($innerhtml)
    {
	$str = '  <h1 id="admin_users_title">'.lang('users_list_title').'</h1>
  <form action="?'.urlargs('users','delete').'" method="post">
   <table border="1" cellpadding="1" cellspacing="0" style="border-style: solid;border-color: #125443">
    <tr>
     <td>
      &nbsp;'.lang('users_list_id').'&nbsp;
     </td>
     <td>
      &nbsp;'.lang('users_list_username').'&nbsp;
     </td>
     <td>
      &nbsp;'.lang('users_list_pwhash').'&nbsp;
     </td>
     <td>
      &nbsp;'.lang('users_list_level').'&nbsp;
     </td>
     <td>
      &nbsp;'.lang('users_list_delete').'&nbsp;
     </td>
    </tr>
';

	$str .= $innerhtml;

	$str .= '  </table>
  <input type="submit" value="'.lang('users_list_submit_btn').'" />&nbsp;'.lang('users_list_verify').' <input type="checkbox" name="verify" value="1" />
 </form>
';

	return $str;
    }

    function user_login_page()
    {
    return '<h1 id="login_title">'.lang('login_title').'</h1>'.
	'<div id="admin_all"><p>'.lang('user_login_greeting').'</p>
    <form action="?'.urlargs('login','login').'" method="post">
    <table>
    <tr><td>'.lang('login_username').'</td><td><input type="text" name="rash_username" size="8" id="user_login_username-box" /></td></tr>
    <tr><td>'.lang('login_password').'</td><td><input type="password" name="rash_password" size="8" id="user_login_password-box" /></td></tr>
    <tr><td>'.lang('login_remember').'</td><td><input type="checkbox" name="remember_login"></td></tr>
    <tr><td></td><td><input type="submit" value="'.lang('login_submit_btn').'" id="user_login_submit-button" /></td></tr>
    </table>
    </form></div>';
    }

    function admin_login_page()
    {
    return '<h1 id="login_title">'.lang('login_title').'</h1>'.
	'<div id="admin_all"><p>'.lang('admin_login_greeting').'</p>
    <form action="?'.urlargs('admin','login').'" method="post">
    <table>
    <tr><td>'.lang('login_username').'</td><td><input type="text" name="rash_username" size="8" id="admin_login_username-box" /></td></tr>
    <tr><td>'.lang('login_password').'</td><td><input type="password" name="rash_password" size="8" id="admin_login_password-box" /></td></tr>
    <tr><td>'.lang('login_remember').'</td><td><input type="checkbox" name="remember_login"></td></tr>
    <tr><td></td><td><input type="submit" value="'.lang('login_submit_btn').'" id="admin_login_submit-button" /></td></tr>
    </table>
    </form></div>';
    }

    function quote_queue_page_iter($quoteid, $quotetxt)
    {
	return '     <tr>
      <td class="quote_no">
       <label>'.lang('quote_queue_no').'<input type="radio" name="q'.$quoteid.'" value="n'.$quoteid.'"></label>
      </td>
      <td>
        <div class="quote_quote">
		'.$quotetxt.'
        </div>
      </td>
	  <td class="quote_yes">
       <label><input type="radio" name="q'.$quoteid.'" value="y'.$quoteid.'" style="text-align: right">'.lang('quote_queue_yes').'</label>
	  </td>
     </tr>
';

    }

    function quote_queue_page($innerhtml)
    {
	$str = '<h1 id="admin_queue_title">'.lang('quote_queue_admin_title').'</h1>';

	$str .= '  <form action="?'.urlargs('queue','judgement').'" method="post">
   <table width="100%" cellspacing="0" class="admin_queue">';

	$str .= $innerhtml;

	$str .= '   </table>
   <input type="submit" value="'.lang('quote_queue_submit_btn').'" />
   <input type="reset" value="'.lang('quote_queue_reset_btn').'" />
  </form>
';

	return $str;
    }

    function quote_upvote_button($quoteid, $canvote)
    {
	if (!$canvote)
	    return '<a href="?'.urlargs('vote',$quoteid,'plus').'" class="quote_plus" title="'.lang('upvote').'">+</a>';
	return '<span class="quote_plus" title="'.lang('cannot_vote_'.$canvote).'">+</span>';
    }

    function quote_downvote_button($quoteid, $canvote)
    {
	if (!$canvote)
	    return '<a href="?'.urlargs('vote',$quoteid,'minus').'" class="quote_minus" title="'.lang('downvote').'">-</a>';
	return '<span class="quote_minus" title="'.lang('cannot_vote_'.$canvote).'">-</span>';
    }

    function quote_flag_button($quoteid, $canflag)
    {
	global $CONFIG;
	if (isset($CONFIG['login_required']) && ($CONFIG['login_required'] == 1) && !isset($_SESSION['logged_in'])) return '';
	if ($CONFIG['auto_flagged_quotes'] == 1) return '';
	if ($canflag)
	    return '<a href="?'.urlargs('flag',$quoteid).'" class="quote_flag" title="'.lang('flagquote').'">X</a>';
	return '<span class="quote_flag" title="'.lang('quote_already_flagged').'">X</span>';
    }

    function quote_iter($quoteid, $rating, $quotetxt, $canflag, $canvote, $date=null)
    {
	$str = '<div class="quote_whole" id="'.$quoteid.'">
    <div class="quote_option-bar">
     <a href="?'.urlargs($quoteid).'" class="quote_number">#'.$quoteid.'</a>'
	    .' '.$this->quote_upvote_button($quoteid, $canvote)
	    .' '.'<span class="quote_rating">('.$rating.')</span>'
	    .' '.$this->quote_downvote_button($quoteid, $canvote)
	    .' '.$this->quote_flag_button($quoteid, $canflag)
	    .' '.edit_quote_button($quoteid);

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
	    $str .= '<h1 id="quote_origin-name">'.$title.'</h1>';
	$str .= $pagenumbers;
	$str .= '<div id="quote_list">'.$quotes.'</div>';
	$str .= $pagenumbers;
	return $str;
    }

    function flag_page($quoteid, $quotetxt, $flag)
    {
	global $CAPTCHA;

	$str = '';

	$str .= '<h1>'.lang('flag_quote_title').'</h1>';

	$str .= $this->get_messages();
	if ($flag == 0)
	    $str .= '<p>'.lang('flag_quote_explanation');

	$str .= '<div class="quote_quote">'.$quotetxt.'</div>';

	if ($flag == 0) {
	    $str .= '<form action="?'.urlargs('flag',$quoteid, 'verdict').'" method="post">';
	    $str .= $CAPTCHA->get_CAPTCHA('flag');
	    $str .= '<input type="submit" value="'.lang('flag_quote_submit_btn').'" />
   <input type="reset" value="'.lang('flag_quote_reset_btn').'" />
  </form>';
	}
	return $str;
    }

}