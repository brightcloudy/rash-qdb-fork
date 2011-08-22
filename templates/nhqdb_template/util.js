userAgentLowerCase = navigator.userAgent.toLowerCase();

function resizeTextarea(t) {
    if ( !t.initialRows ) t.initialRows = t.rows;
    a = t.value.split('\n');
    b=0;
    for (x=0; x < a.length; x++) {
	if (a[x].length >= t.cols) b+= Math.floor(a[x].length / t.cols);
    }
    b += a.length;
    if (userAgentLowerCase.indexOf('opera') != -1) b += 2;
    if (b > t.rows || b < t.rows)
	t.rows = (b < t.initialRows ? t.initialRows : ((b < 50) ? b : 50));
}


function setFocus(id) {
    var e = document.getElementById(id);
    if (e) e.focus();
}


function mangle_quote(textareaid)
{
  var txtarea = document.getElementById(textareaid);
  if (!txtarea) return;

  var txt = txtarea.value;

  /* fix whitespace */
  txt = txt.replace(/^\s+/, "");
  txt = txt.replace(/\s+$/, "");
  txt = txt.replace(/ +$/m, "");

  /* try to fix different timestamp styles */
  var lines = txt.split("\n");
  var style = 0;
  var oldstyle = 0;
  for (var i = 0; i < lines.length; i++) {
      style = 0;
      if (lines[i].match(/^\[?\d\d:?\d\d(:?\d\d)?\]? +/)) { style = 1; }
      else if (lines[i].match(/^\[?(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Dec|) +[012]\d +\d\d:?\d\d(:?\d\d)?\]? +/i)) { style = 2; }

      if (style != 0) {
	  if ((oldstyle != 0) && (oldstyle != style)) {
	      style = -1;
	      break; /* different style lines, bail out */
	  } else {
	      oldstyle = style;
	  }
      }
  }

  if (style > 0) {
      for (var i = 0; i < lines.length; i++) {
	  var tmp;
	  switch (style) {
	  case 1: tmp = lines[i].replace(/^\[?\d\d:?\d\d(:?\d\d)?\]? +/, ""); break;
	  case 2: tmp = lines[i].replace(/^\[?(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Dec|) +[012]\d +\d\d:?\d\d(:?\d\d)?\]? +/i, ""); break;
	  default: tmp = lines[i]; break;
	  }
	  lines[i] = tmp;
      }
  }

  txt = lines.join("\n");

  txtarea.value = txt;
}