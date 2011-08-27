<?php

/* Change urls to clickable links, and change newlines to br-tags. */
function mangle_quote_text($txt)
{
    $txt = preg_replace('/((http|ftp):\/\/([\w\d\-]+)(\.[\w\d\-]+){1,})([\/\?\w\d\.=&+%~_\-]+(#[\w\d_]+)?)?/', '<A href="\\1\\5">\\1\\5</A>', $txt);
    $txt = nl2br($txt);
    return $txt;
}

function db_tablename($name)
{
    include 'settings.php';
    return $CONFIG['db_table_prefix'].'_'.$name;
}

function urlargs($ar1, $ar2 = null, $ar3 = null)
{
    include 'settings.php';
    if ($ar2 === null) return $ar1;
    if ($ar3 === null) return implode($CONFIG['GET_SEPARATOR_HTML'], array($ar1, $ar2));
    return implode($CONFIG['GET_SEPARATOR_HTML'], array($ar1, $ar2, $ar3));
}


function write_settings($fname, $data)
{
    $fp = fopen($fname,"w");
    $str = "<?php\n";
    foreach ($data as $key=>$val) {
	$str .= '$CONFIG[\''.$key.'\'] = '.$val.";\n";
    }
    if (fwrite($fp, $str, strlen($str)) === FALSE) {
	return FALSE;
    }
    return TRUE;
}

function mk_cookie($name, $data = null)
{
    if ($data) {
        setcookie($name, $data, time()+3600*24*365, '/');
        $_COOKIE[$name] = $data;
    } else {
        setcookie($name, '', time()-3600, '/');
        unset($_COOKIE[$name]);
    }
}




/**
 * Return a random string
 *
 * @author       Aidan Lister <aidan@php.net>
 * @version      2.0
 * @param        int     $length  Length of the string you want generated
 * @param        string  $seeds   The seeds you want the string to be generated from
 */
function str_rand($length = 8, $seeds = 'abcdefghijklmnopqrstuvwxyz0123456789')
{
    $str = '';
    $seeds_count = strlen($seeds);

    // Seed
    list($usec, $sec) = explode(' ', microtime());
    $seed = (float) $sec + ((float) $usec * 100000);
    mt_srand($seed);

    // Generate
    for ($i = 0; $length > $i; $i++) {
        $str .= $seeds{mt_rand(0, $seeds_count - 1)};
    }

    return $str;
}


function title($title)
{
    include 'settings.php';
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
		case true:
		    if (preg_match('/^[0-9]+$/', $_SERVER['QUERY_STRING'])) {
			return 'Quote #'.$title;
		    } else {
			return $CONFIG['site_long_title'];
		    }
		    break;
	default:
	    return $CONFIG['site_long_title'];
	    break;
	}
}

