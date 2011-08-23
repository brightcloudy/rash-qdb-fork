<?php

/* basic NetHack-themed CAPTCHA */

class nhCAPTCHA extends baseCAPTCHA {

    private $questions = array(
			       array('char'=>'+','text'=>'a spellbook'),
			       array('char'=>'*','text'=>'a gem or a rock'),
			       array('char'=>'?','text'=>'a scroll'),
			       array('char'=>'!','text'=>'a potion'),
			       array('char'=>'%','text'=>'something edible'),
			       array('char'=>'=','text'=>'a ring'),
			       array('char'=>'|','text'=>'a grave'),
			       array('char'=>'#','text'=>'a kitchen sink'),
			       array('char'=>'^','text'=>'a trap'),
			       array('char'=>')','text'=>'a weapon'),
			       array('char'=>'[','text'=>'a piece of armor'),
			       array('char'=>'/','text'=>'a wand'),
			       array('char'=>'_','text'=>'an altar'),
			       array('char'=>'{','text'=>'a fountain'),
			       array('char'=>'o','text'=>'a goblin'),
			       array('char'=>'h','text'=>'a dwarf'),
			       array('char'=>'e','text'=>'a floating eye'),
			       array('char'=>':','text'=>'a lizard'),
			       );

    function get_CAPTCHA()
    {
	$qn = rand(0,count($this->questions)-1);
	$qni = rand(0,32000);
	$qnix = $qni + count($this->questions) - ($qni % count($this->questions)) + $qn;
	$ret = '<p class="CAPTCHAquestion">';
	$ret .= 'You will need to answer the following question correctly: ';
	$ret .= 'What symbol represents '.$this->questions[$qn]['text'].'? ';
	$ret .= '<input type="text" name="CAPTCHAanswer" size="1" maxlength="1">';
	$ret .= '<input name="CAPTCHAquestionid" type="hidden" value="'.$qnix.'">';
	$ret .= '<input name="CAPTCHA" type="hidden" value="1">';
	$ret .= '</p>';
	return $ret;
    }

    function check_CAPTCHA()
    {
	if ($_POST['CAPTCHA']) {
	    $c_qid = $_POST['CAPTCHAquestionid'];
	    $c_ans = $_POST['CAPTCHAanswer'];
	    if (preg_match('/^[0-9]+$/', $c_qid) && ($qid >= 0)) {
		$c_qid = ($c_qid % count($this->questions));
		if ($c_ans == $this->questions[$c_qid]['char']) {
		    return 0;
		} else return 1;
	    } else return 2;
	} return 3;
    }
}

$CAPTCHA = new nhCAPTCHA;
