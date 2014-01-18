<?php
/*
Project: evenc
Copyright (c) 2005 Andreas Volk

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or
sell copies of the Software, and to permit persons to whom
the Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

The copyright notice at the bottom of any evenc page may not
be removed.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY
KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/
function dmts_echo($file, &$content)
{
  $file_content = file_get_contents($file);
  while(list($key, $val) = each($content) )
  {
    $file_content = str_replace("[_".$key."_]", $val , $file_content);
  }  
  echo($file_content);
  return true;
}

function dmts_echos($file, &$content)
{
  $file_content = file_get_contents($file);
  while(list($key, $val) = each($content) )
  {
    $file_content = str_replace("[_".$key."_]", $val , $file_content);
  }  
  reset($content);
  return $file_content;
}

function dmts_echoss($string, &$content)
{
  while(list($key, $val) = each($content) )
  {
    $string = str_replace("[_".$key."_]", $val , $string);
  }  
  reset($content);
  return $string;  
}

//Parses BBcode in a string
function dmts_parse_bbcode($string)
{
/*
ToDo:
[s][/s]
[i][/i]
[u][/u]   
[b][/b] 
[quote=name][/quote]
[code][/code]
[color=color][/color]
[size=X][/size]
[font][/font]
[list][*][*][/list]
[url=url][/url] || [url][url]
*/
  $bbcode   = array();
  $replace  = array();
  
  //[s][/s]
  $bbcode[0]  = "\[s\](.*)\[\/s\]";
  $replace[0] = "<span class='bbc_strike'>$1</span>";
  //[i][/i]
  $bbcode[1]  = "\[i\](.*)\[\/i\]";
  $replace[1] = "<span class='bbc_italic'>$1</span>";  
  //[u][/u]
  $bbcode[2]  = "\[u\](.*)\[\/u\]";
  $replace[2] = "<span class='bbc_underline'>$1</span>";  
  //[b][/b]
  $bbcode[3]  = "\[b\](.*)\[\/b\]";
  $replace[3] = "<span class='bbc_bold'>$1</span>";      
  //[color=color][/color]
  $bbcode[4]  ="\[color(=([a-z0-9#]*))?\](.*)\[\/color\]";
  $replace[4] ="<span style='color: $2;'>$3</span>"; 
  //[quote=name]text[/quote]
  $bbcode[5]  ="\[quote(=([^\]]*))?\](.*)\[\/quote\]";
  $replace[5] ="<q class='bbc_quote' cite='$2'>$3</q>";  
  //[code]text[/code]
  $bbcode[6]  ="\[code\](.*)\[\/code\]";
  $replace[6] ="<code class='bbc_quote'>$1</code>";  
  //[size=X][/size] // size 0-19
  $bbcode[7]  ="\[size(=([1]?[0-9]{1})){1}\](.*)\[\/size\]";
  $replace[7] ="<span style='font-size: $2"."ex;'>$3</span>";  
  //[size=X][/size] // size 0-19
  $bbcode[8]  ="\[font(=([\s\w]*)){1}\](.*)\[\/font\]";
  $replace[8] ="<span style=\"font-family: '$2';\">$3</span>";   
    
  //[url]addy[/url]
  $bbcode[9]  ="\[url\](.*)\[\/url\]";
  $replace[9] ="<a href='$1'>$1</a>";    
  //[url=addy]text[/url]
  $bbcode[10]  ="\[url(=(.*)){1}\](.*)\[\/url\]";
  $replace[10] ="<a href='$2'>$3</a>";      
  
  // add parameter to bbcode
  for($i=(count($bbcode)-1); $i >= 0; $i--)
    $bbcode[$i] = "/".$bbcode[$i]."/isU";
    
  //[list][*][*][/list] ... wow - it works :)
  $bbcode[11]  ="/\[list\](.*)\[\/list\]/eisU";
  $replace[11] ='\'<ul class="bbc_list">\'.preg_replace(\'/(\[\*\]([^[]*))/is\',\'<li>\$2</li>\',\'$1\').\'</ul>\'';    
  
  return preg_replace($bbcode, $replace, $string);
}

?>