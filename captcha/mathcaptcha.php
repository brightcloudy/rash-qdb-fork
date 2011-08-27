<?php

/* basic math themed CAPTCHA */

class mathCAPTCHA extends baseCAPTCHA {

    function math_gen($seed)
    {
	$a = ($seed % 16);
	$seed = ($seed / 17);
	$b = ($seed % 16);
	$seed = ($seed / 17);
	$o = ($seed % 2) ? '+' : '-';
	$seed = ($seed / 2);
	if ($seed > 2500000) {
	    $c = ($seed % 16);
	    $seed = ($seed / 17);
	    $o2 = ($seed % 2) ? '+' : '-';
	    return $a.' '.$o.' '.$b.' '.$o2.' '.$c;
	}
	return $a.' '.$o.' '.$b;
    }


    function get_CAPTCHA($type)
    {
	if (parent::check_passthru($type)) return '';

	$seed = mt_rand();
	$calc = $this->math_gen($seed);

	$ret = '<p class="CAPTCHAquestion">';
	$ret .= 'You will need to answer the following correctly: ';
	$ret .= '<b>'.$calc.' = </b>';
	$ret .= '<input type="text" name="CAPTCHAanswer" size="3" maxlength="3">';
	$ret .= '<input name="CAPTCHAquestionid" type="hidden" value="'.$seed.'">';
	$ret .= '<input name="CAPTCHA" type="hidden" value="1">';
	$ret .= '</p>';
	return $ret;
    }

    function check_CAPTCHA($type)
    {
	if (parent::check_passthru($type)) return 0;
	if ($_POST['CAPTCHA']) {
	    $seed = $_POST['CAPTCHAquestionid'];
	    $calc = $this->math_gen($seed);
	    $ans = $_POST['CAPTCHAanswer'];
	    eval('$res = '.$calc.';');
	    if ($ans == $res) {
		return 0;
	    } else return 1;
	}
	return 3;
    }
}

$CAPTCHA = new mathCAPTCHA;
