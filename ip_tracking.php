<?php
function ip_track($where, $quote_num)
{
	require('settings.php');
	require('connect.php');

	switch($where){
		case 'flag':
			$where2 = 'vote';
			break;
		case 'vote':
			$where2 = 'flag';
			break;
	}

	
	$res =& $db->query("SELECT ip FROM rash_tracking WHERE ip='".getenv("REMOTE_ADDR")."'");
	if (DB::isError($res)) {
		die($res->getMessage());
	}

	if($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){ // if ip is in database
		$res->free();
		$res =& $db->query("SELECT quote_id FROM rash_tracking WHERE ip='".getenv("REMOTE_ADDR")."'");
		if (DB::isError($res)) {
			die($res->getMessage());
		}
		$quote_array = $res->fetchRow(DB_FETCHMODE_ORDERED);
		$quote_array = explode(",", $quote_array[0]);
		$quote_place = array_search($quote_num, $quote_array);
		if(in_array($quote_num, $quote_array)){
			$res2 =& $db->query("SELECT $where FROM rash_tracking WHERE ip='".getenv("REMOTE_ADDR")."'");
			if (DB::isError($res)) {
				die($res->getMessage());
			}
			$where_result = $res2->fetchRow(DB_FETCHMODE_ORDERED);
			$where_result = explode(",", $where_result[0]);
			if(!$where_result[$quote_place]){
				$where_result[$quote_place] = 1;
				$where_result = implode(",", $where_result);
				$db->query("UPDATE rash_tracking SET $where='$where_result' WHERE ip='".getenv("REMOTE_ADDR")."'");
				if (DB::isError($res)) {
					die($res->getMessage());
				}

				return 1;
			}
			else{
				return 3;
			}
		}
		else{	// if the quote doesn't exist in the array based on ip, the quote and relevent vote and flag
				// entries are concatenated to the end of the current entries

			// mysql_query("UPDATE $trackingtable SET $where=CONCAT($where,',1'), 
			// $where2=CONCAT($where2,',0'), $where3=CONCAT($where3,',0'), 
			// quote=CONCAT(quote,'," . $quote_num . "') WHERE ip ='" . getenv("REMOTE_ADDR") . "';");
			// Oh how I miss thee mysql :(

			// Update the quote_id
			$res =& $db->query("SELECT quote_id FROM rash_tracking WHERE ip='".getenv("REMOTE_ADDR")."'");
			if (DB::isError($res)) {
				die($res->getMessage());
			}
			$row = $res->fetchRow(DB_FETCHMODE_ORDERED);
			$row[] = $quote_num;
			$db->query("UPDATE rash_tracking SET quote_id = '".implode(",", $row)."' WHERE ip='".getenv("REMOTE_ADDR")."'");
			if (DB::isError($res)) {
				die($res->getMessage());
			}
			$res->free();

			// Update $where
			$res =& $db->query("SELECT $where FROM rash_tracking WHERE ip='".getenv("REMOTE_ADDR")."'");
			if (DB::isError($res)) {
				die($res->getMessage());
			}
			$row = $res->fetchRow(DB_FETCHMODE_ORDERED);
			$row[] = '1';
			$db->query("UPDATE rash_tracking SET $where = '".implode(",", $row)."'");
			if (DB::isError($res)) {
				die($res->getMessage());
			}
			$res->free();

			// Update $where2
			$res =& $db->query("SELECT $where2 FROM rash_tracking WHERE ip='".getenv("REMOTE_ADDR")."'");
			if (DB::isError($res)) {
				die($res->getMessage());
			}
			$row = $res->fetchRow(DB_FETCHMODE_ORDERED);
			$row[] = '0';
			$db->query("UPDATE rash_tracking SET $where2 = '".implode(",", $row)."'");
			if (DB::isError($res)) {
				die($res->getMessage());
			}
			$res->free();

			return 1;
		}
	}
	else{ // if ip isn't in database, add it and appropriate quote action
		$res = $db->query("INSERT INTO rash_tracking (ip, quote_id, $where, $where2) VALUES('".getenv("REMOTE_ADDR")."', ".$quote_num.", 1, 0);");
		if (DB::isError($res)) {
			die($res->getMessage());
		}
		return 2;
	}
}
?>