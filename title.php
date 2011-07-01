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
			return 'Rash Quote Management System';
			break;
		case true:
			if(is_int($_SERVER['QUERY_STRING']) || $_SERVER['QUERY_STRING'] != false)
			{
				if(verify_int($_SERVER['QUERY_STRING']))
				{
					return 'Quote #'.$title;
				}
				else{
					return 'Rash Quote Management System';
				}
			}
			else{
				return 'Rash Quote Management System';
			}
			break;
	}
}
?>