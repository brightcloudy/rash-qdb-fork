<?php
// title()
// Determines site title
//
function title($title)
{
	switch($title)
	{
		case 'add':
			return 'Add a Quote';
			break;
		case 'bottom':
			return 'Bottom';
			break;
		case 'browse':
			return 'Browse Quotes';
			break;
		case 'latest':
			return 'Latest Quotes';
			break;
		case 'random':
			return 'Random Quotes';
			break;
		case 'random2':
			return 'Random>0 Quotes';
			break;
		case 'search':
			return 'Search for Quotes';
			break;
		case 'top':
			return 'Top Quotes';
			break;
		case false:
			return '#nethack QDB';
			break;
		case true:
		    if (preg_match('/^[0-9]+$/', $_SERVER['QUERY_STRING'])) {
			return 'Quote #'.$title;
		    } else {
			return '#nethack QDB';
		    }
		    break;
	}
}
?>
