<?php

// function user_quote_status($where, $quote_num)
// This function checks the user's ip address against the stores entries to ensure
// that multiple voting doesn't occur (it does this with the ip_track() function.
// It returns a number for either flag or vote to tell them if you're able to 
// modify the quote.
// 

function user_quote_status($where, $quote_num)
{
	require('settings.php');
	require("language/{$language}.lng");
	$tracking_verdict = ip_track($where, $quote_num);
	if($where != 'flag'){
		switch($tracking_verdict){
			case 1:
				echo $lang['tracking_check_1'];
				break;
			case 2:
				echo $lang['tracking_check_2'];
				break;
			case 3:
				echo $lang['tracking_check_3'];
				break;
		}
	}
	return $tracking_verdict;
}


// flag()
// User clicks on the (default) [X] link and it takes that quote and changes 
// a cell in the approved quote table. This change is shown in the administation 
// section to warn you that the quote is either bad or offensive. The admin can 
// do whatever is needed at the time. Times allowed to do it limited by a cookie.
//

function flag($quote_num)
{
	require('settings.php');
	require('connect.php');
	require("language/{$language}.lng");
	$tracking_verdict = user_quote_status('flag', $quote_num);
	if($tracking_verdict == 1 || 2){
		$res =& $db->query("SELECT flag FROM rash_quotes WHERE id = {$quote_num} LIMIT 1");
		$row = $res->fetchRow(DB_FETCHMODE_ORDERED);
		if($row[0] == 2){
			echo $lang['flag_previously_flagged'];
		}
		elseif($row[0] == 1){
			echo $lang['flag_currently_flagged'];
		}
		else{
			echo $lang['flag_quote_flagged'];
			$db->query("UPDATE rash_quotes SET flag = 1 WHERE id = {$quote_num}");
		}
	}
}

// function vote($quote_num, $method)
// This function increments or decrements the rating of the quote in rash_quotes.
//

function vote($quote_num, $method)
{
	require('settings.php');
	require('connect.php');
	$tracking_verdict = user_quote_status('vote', $quote_num);
	if($tracking_verdict == 3){
		printfooter();
		exit();
	}
	if($tracking_verdict == 1 || 2){
		if($method == "plus")
			$db->query("UPDATE rash_quotes SET rating = rating+1 WHERE id = {$quote_num}");
		elseif($method == "minus")
			$db->query("UPDATE rash_quotes SET rating = rating-1 WHERE id = {$quote_num}");
	}
}
?>