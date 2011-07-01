<?php
function verify_int($subject)
{
	$ymax = strlen($subject);
	$y = 0;
	while($y < $ymax)
	{
		if((is_int((int)($subject{$y})) && (int)($subject{$y})) || (int)($subject{$y}) === 0 )
		{
			$y++;
		}
		else{
			return false;
		}
	}
	return true;
}
?>