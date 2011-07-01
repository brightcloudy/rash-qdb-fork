<?

// add_quote()
// This function serves as the page catering to ?add, it can receive input
// from an HTML form that will be inserted into rash_queue for viewing when
// logged in as an administrator.

function add_quote($method)
{
	require('settings.php');
	require('connect.php');
	require("language/{$language}.lng");
?>
   <div id="add_all">
    <div id="add_title">
	 Contribute
	</div>
<?
	if($method == 'submit'){
			// take $_POST['quote'] and echo it to the screen, then 
	        // run it through addslashes() and htmlspecialchars()
	        // and then insert it into rash_submit mysql table
?>
    <div id="add_outputmsg">
     <div id="add_outputmsg_top">
      <?=$add_outputmsg_top."\n"?>
     </div>
     <div id="add_outputmsg_quote">
      <?=nl2br(htmlspecialchars($_POST["rash_quote"]))."\n"?>
     </div>
     <div id="add_outputmsg_bottom">
      <?=$lang['add_outputmsg_bottom']."\n"?>
     </div>
    </div> 
<?
		$res =& $db->query("INSERT INTO rash_queue (quote) VALUES('".addslashes(htmlspecialchars($_POST["rash_quote"]))."');");
		if(DB::isError($res)){
			die($res->getMessage());
		}
	}
?>
    <form action="?add<?=$GET_SEPARATOR_HTML?>submit" method="post">
     <textarea cols="80" rows="5" name="rash_quote" id="add_quote"></textarea><br />
     <input type="submit" value="Add Quote" id="add_submit" />
     <input type="reset" value="Reset" id="add_reset" />
    </form>
   </div>
<?
}
?>
