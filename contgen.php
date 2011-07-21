<?
// home_generation()
//
// Generates the page that shows up when there are none or invalid URL arguments,
// the default page, can be used to show the general idea of the site, and/or
// used for news updates, either can be turned off in rash_settings.php
// in the rash/templates/rash_template folder.
//
// The greeting div has a variable named $home_greeting in it, this variable
// should be assigned to a greeting, although anything you want can do.
//
function home_generation()
{
	require('settings.php');
	require('connect.php');
	require("language/{$language}.lng");
	$res =& $db->query("SELECT * FROM rash_news");
	if(DB::isError($res)){
		die($res->getMessage());
	}
?>
  <div id="home_all">
   <div id="home_news">
<?
	//
	// please note that in the code that news on left and home generation on
	// the right, that's because of how floats work with css, but in 
	// presentation it is reversed!
	//
	while($row=$res->fetchRow(DB_FETCHMODE_ASSOC)){
?>
    <div class="home_news_date">
<?= date('Ymd', $row['date']) ."\n"?>
    </div>
    <div class="home_news_news">
     <?=$row['news']?>
    </div>
<?
	}
?>
   </div>

   <div id="home_greeting">
    <?=$lang['home_greeting']."\n"// language/x.lng?>
   </div>
  </div>
<?
}

/************************************************************************
************************************************************************/

// page_numbers()
// This functino deals with all the page numbers in the (by default)
// browse section. It first gets its variables in order, figured out
// how many pages there ought to be based on the limit of quotes per page
// then 
function page_numbers($origin, $quote_limit, $page_default, $page_limit)
{
	require('settings.php');
	require('connect.php');
	$numrows = $db->getOne("SELECT COUNT(id) FROM rash_quotes");
    $testrows = $numrows;

	$pagenum = 0;

    do{
		$pagenum++;
        $testrows -= $quote_limit;
    }while($testrows > 0);

	// ensures $page_limit is an odd number so the algorithm output looks decent,
	// as in the current page is in the middle of a number line containing the 
	// pages rather than a little left or right of the middle, which works as long
	// as the pages are being viewed from the middle of the number set rather than
	// either end, heh
	if(!($page_limit % 2))
		$page_limit += 1;

	// if $page_limit is 1, 0, or negative, it is automatically set to 5
	if(($page_limit == 1) || ($page_limit < 0) || (!$page_limit))
		$page_limit = 5;

	// determines how many pages to show based on limit of pages ($page_limit)
	// which is set in settings.php, $page_base is how many in EACH DIRECTION
	// on a number line from $page_default to go

	$page_base = 0;
	do{	// determine how many pages to the left and right of the current page to 
		// show in the page numbers bar
		$page_base++;
		$page_limit -= 2;
	}while($page_limit > 1);
	echo "   <div class=\"quote_pagenums\">\n";
	echo "    <a href=\"?".strtolower($origin).$GET_SEPARATOR_HTML."1\">First</a>&nbsp;&nbsp;\n";
	// this line is responsible for the -10 link in browse (by default), and the weird part in the middle
	// is a conditional that checks to see if the current page - 10 is going to be 0 or negative, if it is,
	// the -10 link defaults to page 1, if it turns out it's > 0, it links to the current page - 10 pages
	//
	echo "    <a href=\"?".strtolower($origin).$GET_SEPARATOR_HTML
		.((($page_default-10) > 1) ? ($page_default-10) : (1))
		."\">-10</a>&nbsp;&nbsp; \n";

	if(($page_default - $page_base) > 1)
	{	// an ellipse is echoed when there exist pages beyond the current sight of the user
		echo "    ... \n";
	}
	$x = ($page_default - $page_base);

	do{	// echo the page numbers before the current page, but only $page_limit many
		if($x > 0) // keeps page numbers from going to zero or below
			echo "    <a href=\"?".strtolower($origin).$GET_SEPARATOR_HTML.$x."\">${x}</a> \n";
		$x++;
	}while($x < $page_default);

	// echo the current page, no link
	echo "    ${page_default} \n";

	$x = ($page_default + 1);

	do{	// echo the page numbers after the current page, but only $page_limit many
		if($x <= $pagenum) // keeps page numbers from going higher than ones that have quotes
			echo "    <a href=\"?".strtolower($origin).$GET_SEPARATOR_HTML.$x."\">${x}</a> \n";
		$x++;
	}while($x < ($page_default + $page_base + 1));

	if(($page_default + $page_base) < $pagenum)
	{	// an ellipse is echoed when there exist pages beyond the current sight of the user
		echo "    ... \n";
	}

	// this line is responsible for the -10 link in browse (by default), and the weird part in the middle
	// checks to see if the current page + 10 will end up being less than the highet actual possible page,
	// if it turns out that's true, then it links to the current page + 10, if current page + 10 is higher
	// than the highest possible page, then it just links to the highest possible page
	//
	echo "    &nbsp;&nbsp;<a href=\"?".strtolower($origin).$GET_SEPARATOR_HTML
		.((($page_default+10) < $pagenum) ? ($page_default+10) : ($pagenum))
		."\">+10</a>&nbsp;&nbsp;\n";

	echo "    &nbsp;&nbsp;<a href=\"?".strtolower($origin).$GET_SEPARATOR_HTML.$pagenum."\">Last</a>\n";
	echo "   </div>\n";
}

/************************************************************************
************************************************************************/

// quote_generation()
//
// This is the rugged function that pulls quotes out of the rash_quotes table
// on the database and presents them to the viewer.
//
// The $query variable is usually gotten from index.php (anyplace can call this
// function) and is a string containing the database query to be used to retrieve
// information from the database.
//
// Keep in mind that this query should be able to be used in a numerous amount of
// databases because of PEAR::DB.
//
function quote_generation($query, $origin, $page = 1, $quote_limit = 50, $page_limit = 10)
{
	require('settings.php');
	require('connect.php');
	if($page != -1){
?>
  <div id="quote_all">

<?
		if(!$page)
			$page = 1;

	page_numbers($origin, $quote_limit, $page, $page_limit);

	}
	$up_lim = ($quote_limit * $page);
	$low_lim = $up_lim - $quote_limit;
	if($page != -1){
		$query .= "LIMIT $low_lim,$quote_limit";
	}

	$res =& $db->query($query);
	if (DB::isError($res)) {
		die($res->getMessage());
	}

	if(isset($origin)){
?>
   <div id="quote_origin-name">
    <?=$origin?>
   </div>
<?
	}
	while($row=$res->fetchRow(DB_FETCHMODE_ASSOC)){	
?>
   <div class="quote_whole">
    <div class="quote_option-bar">
     <a href="?<?=$row['id']?>" class="quote_number">#<?=$row['id']?></a>
     <a href="?vote<?=$GET_SEPARATOR_HTML.$row['id'].$GET_SEPARATOR_HTML."plus"?>" class="quote_plus">+</a>
     <span class="quote_rating">(<?=$row['rating']?>)</span>
     <a href="?vote<?=$GET_SEPARATOR_HTML.$row['id'].$GET_SEPARATOR_HTML."minus"?>" class="quote_minus">-</a>
     <a href="?flag<?=$GET_SEPARATOR_HTML.$row['id']?>" class="quote_flag">[X]</a>
<?
	// if a date is requested in the query (ie. SELECT * FROM or SELECT quote, date, flag, ect. FROM)
	// it will present the date, but the date isn't always wanted, so it is only echoed if it's
	// initialized by dumping the query results into an array
		if(isset($row['date'])) {
			date_default_timezone_set('America/New_York');
			echo "     <span class=\"quote_date\">" . date("F j, Y", $row['date']) . "</span>\n";
		}
?>
    </div>
    <div class="quote_quote">
     <?=nl2br($row['quote'])."\n"?>
    </div>
   </div>
<?
	}
	if($page != -1){
?>
   <div class="quote_pagenums">
<?
	page_numbers($origin, $quote_limit, $page, $page);
?>
  </div>
<?
	}
}
?>
