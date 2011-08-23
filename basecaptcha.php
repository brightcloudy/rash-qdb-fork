<?php

abstract class baseCAPTCHA {
    function get_CAPTCHA()
    {
	/* should return a string containing the CAPTCHA html, maybe a form, or whatever */
	return '';
    }
    function check_CAPTCHA()
    {
	/* checks whether the CAPTCHA was correct.
	   Return values:
	     0 = CAPTCHA was correct
	     1 = CAPTCHA answer was wrong
	     2 = CAPTCHA question was wrong (spoofed?)
	     3 = There's no CAPTCHA
	 */
	return 0;
    }
}
