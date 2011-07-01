<?
// search($method)
// This takes a user to the page where they can put words in to search for 
// quotes with those words in it. Pretty simple.
//

function search($method)
{
	require('settings.php');
	require('connect.php');

	if($method == 'fetch'){
		if($_POST['sortby'] == 'rating')
			$how = 'desc';
		else
			$how = 'asc';
		//quote generation
		$query = "SELECT id, quote, rating, flag FROM rash_quotes WHERE quote LIKE '%{$_POST['search']}%' ORDER BY {$_POST['sortby']} $how LIMIT {$_POST['number']}";
		quote_generation($query, "Query Results", -1);
	}
?>
  <div class="search_all">
<?

	if($method != 'fetch'){

?>
   <div id="search_title">
    Search
   </div>
<?

	}

?>
   <form method="post" action="?search<?=$GET_SEPARATOR_HTML?>fetch">
<?if($method == 'fetch'){?>    <input type="submit" name="submit" id="search_submit-button">&nbsp;<?="\n";}?>
    <input type="text" name="search" size="28" id="search_query-box">&nbsp;
<?if($method != 'fetch'){?>    <input type="submit" name="submit" id="search_submit-button">&nbsp;<br /><?="\n";}?>
    Sort: <select name="sortby" size="1" id="search_sortby-dropdown">
    <option selected>rating
    <option>id
    </select>&nbsp;
    How many?: <select name="number" size="1" id="search_limit-dropdown">
     <option selected>10
     <option>25
     <option>50
     <option>75
     <option>100
    </select>
   </form>
  </div>
<?

}

?>
