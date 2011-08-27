<?php

abstract class baseCAPTCHA {

    private $settings = null;

    function init_settings($settings)
    {
	$this->settings = $settings;
    }

    function check_passthru($type)
    {
	if (!isset($this->settings) || !isset($this->settings[$type]) || ($this->settings[$type] == 0)) return 1;
	return 0;
    }

    function get_CAPTCHA($type)
    {
	if ($this->check_passthru($type)) return '';
	/* should return a string containing the CAPTCHA html, maybe a form, or whatever */
	return '';
    }
    function check_CAPTCHA($type)
    {
	if ($this->check_passthru($type)) return 0;
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
