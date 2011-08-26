<?php

/* very simple CAPTCHA */

class _42CAPTCHA extends baseCAPTCHA {

    function get_CAPTCHA()
    {
	$ret = '<p class="CAPTCHAquestion">';
	$ret .= 'What is the answer to the Ultimate Question of Life, the Universe, and Everything? ';
	$ret .= '<input type="text" name="CAPTCHAanswer" size="2" maxlength="2">';
	$ret .= '<input name="CAPTCHA" type="hidden" value="1">';
	$ret .= '</p>';
	return $ret;
    }

    function check_CAPTCHA()
    {
	if ($_POST['CAPTCHA']) {
	    $a = trim($_POST['CAPTCHAanswer']);
	    if ($a == '42') return 0;
	    return 1;
	} return 3;
    }
}

$CAPTCHA = new _42CAPTCHA;
