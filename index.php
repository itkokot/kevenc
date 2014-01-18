<?
/*
Project: kevent
Copyright (c) 2013 Christian Kokot
--------------------------------------------------
based on:
evenc v0.95a (c) 2005 Andreas Volk <mail @ 23bit.de>
--------------------------------------------------

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
error_reporting(E_ALL);
mb_internal_encoding( 'UTF-8' );
//config
require("./config.php");
if(!isset($CONFIG))
  die("try install.php first");

//php functions
require("./php/dmts_functions.php");

//vars
$content      = array();
$lang         = array();
$error_level  = 0;

//load lang file
if(!is_file("./lang/".$CONFIG["language"].".lang"))
  $CONFIG["language"] = "en"; //fall back to english
$lang = parse_ini_file("./lang/".$CONFIG["language"].".lang", TRUE);

//set static content
$content["EVENC_VERSION"]         = "0.01";
$content["DATE"]                  = date("Y-m-d");  

$content["STRING_INPUT_NOTE"]     = &$lang["strings"]["input_note"];

$content["STRING_SEARCH_DEFAULT"] = &$lang["strings"]["search_default"];

$content["STRING_ADD_COMMENT"]    = &$lang["strings"]["add_comment"];
$content["STRING_COMMENT_NAME"]   = &$lang["strings"]["comment_name"];
$content["STRING_COMMENT_TEXT"]   = &$lang["strings"]["comment_text"];
$content["STRING_COMMENT_WROTE"]  = &$lang["strings"]["comment_wrote"];

$content["STRING_ADD_EVENT"]      = &$lang["strings"]["add_event"];
$content["STRING_EVENT_NAME"]     = &$lang["strings"]["event_name"];
$content["STRING_EVENT_LOCATION"] = &$lang["strings"]["event_location"];
$content["STRING_EVENT_TIME"]     = &$lang["strings"]["event_time"];
$content["STRING_EVENT_DESCRIPTION"]  = &$lang["strings"]["event_description"];
$content["STRING_EVENT_MORE"]     = &$lang["strings"]["event_more"];
$content["STRING_EVENT_DEL"]      = &$lang["strings"]["event_delete"];
$content["STRING_EVENT_AWAY"]     = &$land["strings"]["event_away"]; 

$content["STRING_EVENT_NAVI_NEXT"]= &$lang["strings"]["event_navi_next"];
$content["STRING_EVENT_NAVI_PREV"]= &$lang["strings"]["event_navi_prev"];

$content["CONTENT"]               = "";

//check offline
if($CONFIG["offline"] == "yes")
{
  $content["CONTENT"]   = "<div class='evenc_message'>".$lang["strings"]["offline"]."</div>";
  $content["CALENDAR"]  = "";
  $content["STRING_ADD_EVENT"] = "";
  dmts_echo("./html/index.html", $content);  
  return;
}

//check if install.php is still there
if((is_file("./config.php"))&&(is_file("./install.php")))
  $content["CONTENT"] .= "<div class='evenc_message'>".$lang["strings"]["remove_install"]."</div>";

// connect DB
$db = mysql_connect($CONFIG["db_host"], $CONFIG["db_user"] ,$CONFIG["db_password"]) or die(mysql_error());;
mysql_select_db($CONFIG["db_name"]);
$uft8 = mysql_query("SET NAMES 'utf8'");
//mysql_query(SET NAMES utf8); 

// create calendar | $content["CALENDAR"] = [..]
include("./php/calendar.php");

##################
## ADD COMMENT  ##
##################
if((isset($_POST["action"])) &&($_POST["action"] == "add_comment"))
{
  //set vars
  $name  = htmlentities(mysql_escape_string(substr($_POST["comment_name"]         ,0,60)));
  $text  = htmlentities(mysql_escape_string(substr($_POST["comment_text"]         ,0,500)));
  $eid   =              mysql_escape_string(substr($_POST["comment_event"]        ,0,5));
  $time  = date('Y-m-d H:i:s');
  $info  = $_SERVER["REMOTE_ADDR"];
  //check vars
  $error_level = 0;
    //eid
  if(!is_numeric($eid))
    $error_level = 1;
    //name
  if( ($name == "") || ($name == $lang["strings"]["comment_name"]) )
    $error_level = 2;
    //text
  if( ($text == "") || ($text == $lang["strings"]["comment_text"]) )
    $error_level = 3;
  //add db
  if($error_level == 0)
    $res = mysql_query("INSERT `".$CONFIG["db_table_prefix"]."event_comments` (`name`, `time`, `text`, `eid`, `info`) VALUES ('$name', '$time', '$text', '$eid', '$info')") or die(mysql_error());
  else
    $content["CONTENT"] .= "<div class='evenc_message'>".$lang["errors"]["add_comment_".$error_level]."</div>";
}
##################
##  ADD EVENT   ##
##################
else if((isset($_POST["action"])) && ($_POST["action"] == "add_event"))
{
  //set vars
  $name         = htmlentities(mysql_escape_string(substr($_POST["event_name"]         ,0,40)));
  $date         =              mysql_escape_string(substr($_POST["event_date"]         ,0,10));
  $time         =              mysql_escape_string(substr($_POST["event_time"]         ,0,5 ));
  $location     = htmlentities(mysql_escape_string(substr($_POST["event_location"]     ,0,20)));
  $description  = htmlentities(mysql_escape_string(substr($_POST["event_description"]  ,0,160)));
  // check vars
  $error_level = 0;
    //time XX:xx
  if(!preg_match("/^[0-9]{2}:[0-9]{2}$/",$time))
    $error_level = 1;
    //date XXXX-xx-xx
  if(!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $date))
    $error_level = 2;
    //name
  if(($name == "") || ($name == $lang["strings"]["event_name"]))
    $error_level = 3;
    //location
  if(($location == "") || ($location == $lang["strings"]["event_location"]))
    $error_level = 4;
    //description
  if(($description == "") || ($description == $lang["strings"]["event_description"]))
    $error_level = 5;
    
  //add to db  
  if($error_level == 0)
    $res = mysql_query("INSERT `".$CONFIG["db_table_prefix"]."event_list` (`name`, `date`, `time`, `location`, `description`) VALUES ('$name', '$date', '$time', '$location', '$description')") or die(mysql_error());  
  else
    $content["CONTENT"] .= "<div class='evenc_message'>".$lang["errors"]["add_event_".$error_level]."</div>";
}

##################
##  ADD EVENT   ##
##################
if((isset($_GET["action"])) && ($_GET["action"] == "add_event"))
{
  // check valid date
  if( (isset($_GET["date"])) &&(preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $_GET["date"])))
    $content["DATE"] = $_GET["date"];
  // display page  
  $content["CONTENT"] .= dmts_echos("./html/add_event.html", $content);
}

##################
##    SEARCH    ##
##################
else if((isset($_GET["action"])) && ($_GET["action"] == "search"))
{
  // set vars
  $content["DATE"]  = $lang["strings"]["search_result"];  
  //check for length
  if(isset($_POST["search_string"])  && (strlen($_POST["search_string"]) > 2 ) )
    {
    $search_string    = htmlentities(mysql_real_escape_string(substr($_POST["search_string"],0,40)));  
    $content["STRING_SEARCH_DEFAULT"]   = $search_string;  
    // query db
    
    $res = mysql_query("SELECT * FROM `".$CONFIG["db_table_prefix"]."event_list` WHERE `date` LIKE '%".$search_string."%'
                                                                                       OR `location` LIKE '%".$search_string."%'
                                                                                       OR `description` LIKE '%".$search_string."%' ORDER BY `time`") or die(mysql_error());
    $num = mysql_num_rows($res);  
    
    if($num > 0)
      {  
        for($i=0; $i < $num; $i++)
        {
          $event = array();
          $event["EVENT_NAME"]        = stripslashes(mysql_result($res, $i,"name"));
          $event["EVENT_TIME"]        = substr(mysql_result($res, $i,"time"),0,5);
          $event["EVENT_LOCATION"]    = stripslashes(mysql_result($res, $i,"location"));
          $event["EVENT_DESCRIPTION"] = dmts_parse_bbcode(stripslashes(mysql_result($res, $i,"description")));
          $event["EVENT_LINK"]        = "./?event=".mysql_result($res, $i,"id");
          $event["STRING_EVENT_MORE"] = &$content["STRING_EVENT_MORE"];
          $event["STRING_EVENT_TIME"] = &$content["STRING_EVENT_TIME"];      
   
          $content["CONTENT"] .= dmts_echos("./html/event_box.html", $event);
          
          //wrap after every 2nd event
          if((($i+1)%2)==0)
            $content["CONTENT"] .="<div class='evenc_event_wrap'></div>\n";
                  
        }
        // finaly add wrap
        $content["CONTENT"] .="<div class='evenc_event_wrap'></div>\n";
      }  
      else
      {
        $content["CONTENT"] .= $lang["strings"]["search_no_result"];
      }
  }// if $_POST[]
  else
  {
    $content["CONTENT"] .= $lang["errors"]["search_too_short"];
  }
}

##################
## SHOW EVENT   ##
##################
else if(isset($_GET["event"]) && is_numeric($_GET["event"]))
{

    $res = mysql_query("SELECT * FROM `".$CONFIG["db_table_prefix"]."event_list` WHERE `id` = '".$_GET["event"]."' LIMIT 1") or die(mysql_error());
    $num = mysql_num_rows($res);
    if($num == 1)
    {
      $event = array();      
      $event["EVENT_NAME"]        = stripslashes(mysql_result($res, 0,"name"));
      $event["EVENT_TIME"]        =       substr(mysql_result($res, 0,"time"),0,5); // only XX:xx
      $event["EVENT_LOCATION"]    = stripslashes(mysql_result($res, 0,"location"));
      $event["EVENT_DESCRIPTION"] = dmts_parse_bbcode(stripslashes(mysql_result($res, 0,"description")));
      $event["EVENT_ID"]          = mysql_result($res, 0,"id");
      $event["EVENT_LINK"]        = "./?delete=".mysql_result($res, 0,"id");
      $event["STRING_EVENT_MORE"] = "";
      $event["STRING_EVENT_TIME"] = &$content["STRING_EVENT_TIME"];
      // Comments ??

      $res = mysql_query("SELECT `name`, `time`, `text` FROM `".$CONFIG["db_table_prefix"]."event_comments` WHERE `eid` = '".$_GET["event"]."' ORDER BY `time`") or die(mysql_error());
      $num = mysql_num_rows($res);
      if($num == 0)
      { 	
      $event["STRING_EVENT_DEL"]  = &$content["STRING_EVENT_DEL"];
      }
      else
      {
      $event["STRING_EVENT_DEL"]  = ""; 	
      }

      $content["CONTENT"]         .= dmts_echos("./html/event_box.html", $event);
      $content["DATE"]            = mysql_result($res, 0,"date");
      
      //add wrap
      $content["CONTENT"] .= "<div class='evenc_event_wrap'></div>";
      
      // add comments
      $res = mysql_query("SELECT `name`, `time`, `text` FROM `".$CONFIG["db_table_prefix"]."event_comments` WHERE `eid` = '".$_GET["event"]."' ORDER BY `time`") or die(mysql_error());
      $num = mysql_num_rows($res);
      if($num == 0)
      {
        $content["CONTENT"] .= "<div class='comment_box'>".$lang["strings"]["no_comment"]."</div>\n";
      }
      for($i=0; $i < $num; $i++)
      {
        $comments = array();
        $comment["COMMENT_NAME"]    = stripslashes(mysql_result($res, $i, "name"));
        $comment["COMMENT_TIME"]    = substr(mysql_result($res, $i, "time"),0,16);
        $comment["COMMENT_TEXT"]    = dmts_parse_bbcode(stripslashes(mysql_result($res, $i, "text")));
        $comment["STRING_COMMENT_WROTE"]  = &$content["STRING_COMMENT_WROTE"];
        $comment["STRING_COMMENT_TIME"]   = &$content["STRING_EVENT_TIME"];
        
        $content["CONTENT"] .= dmts_echos("./html/comment_box.html", $comment);
      }
      // add comment_add_box
      $event["STRING_ADD_COMMENT"]  = &$content["STRING_ADD_COMMENT"];
      $event["STRING_COMMENT_NAME"] = &$content["STRING_COMMENT_NAME"];
      $event["STRING_COMMENT_TEXT"] = &$content["STRING_COMMENT_TEXT"];  
      $event["STRING_INPUT_NOTE"]   = &$content["STRING_INPUT_NOTE"];    
      $content["CONTENT"] .= dmts_echos("./html/add_comment.html", $event);
    }
    else
    {
      // event not found
      $content["CONTENT"] .= "<div class='evenc_message'>".$lang["strings"]["invalid_event"]."</div>";
    } 
}

##################
## DELETE EVENT   ##
##################
else if(isset($_GET["delete"]) && is_numeric($_GET["delete"]))
{
    $res = mysql_query("SELECT * FROM `".$CONFIG["db_table_prefix"]."event_list` WHERE `id` = '".$_GET["delete"]."' LIMIT 1") or die(mysql_error());
    $num = mysql_num_rows($res);
    if($num == 1)
    {
      $number_event		  = mysql_result($res, 0,"id"); 				
      $event = array();      
      $event["EVENT_NAME"]        = stripslashes(mysql_result($res, 0,"name"));
      $event["EVENT_TIME"]        =       substr(mysql_result($res, 0,"time"),0,5); // only XX:xx
      $event["EVENT_LOCATION"]    = stripslashes(mysql_result($res, 0,"location"));
      $event["EVENT_DESCRIPTION"] = dmts_parse_bbcode(stripslashes(mysql_result($res, 0,"description")));
      $event["EVENT_ID"]          = mysql_result($res, 0,"id");
      $event["EVENT_LINK"]        = "";
      $event["STRING_EVENT_MORE"] = "";
      $event["STRING_EVENT_TIME"] = &$content["STRING_EVENT_TIME"];
      $event["STRING_EVENT_DEL"]  = "";
      $event["STRING_EVENT_AWAY"] = &$content["STRING_EVENT_AWAY"];
      $content["CONTENT"]         .= dmts_echos("./html/event_box.html", $event);
      $content["DATE"]            = mysql_result($res, 0,"date");
      
      //add wrap
      $content["CONTENT"] .= "<div class='evenc_event_wrap'></div>";
      
      $delete = mysql_query("DELETE FROM `".$CONFIG["db_table_prefix"]."event_list` WHERE `id` = '".$number_event."'") or die(mysql_error());	
//      echo $delete; 	
      // Anzeige Termin gelöscht 	 	
      $content["CONTENT"] .= "<div class='comment_box'>".$lang["strings"]["event_away"]."</div>\n";
    }
    else
    {
      // event not found
      $content["CONTENT"] .= "<div class='evenc_message'>".$lang["strings"]["invalid_event"]."</div>";
    }
}
##################
## SHOW DAY     ##
##################
else
{
  //check valid date
  if((isset($_GET["date"])) && (preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $_GET["date"])))
    $content["DATE"] = $_GET["date"];
  else
    $content["DATE"] = date("Y-m-d"); //show today
  
  //get page number
  if(isset($_GET["page"]) && (is_numeric($_GET["page"])) && ($_GET["page"] >= 0 ) && ($_GET["page"] < 100 )  )
    $page = $_GET["page"];
  else
    $page = 0;
    
    $res = mysql_query("SELECT * FROM `".$CONFIG["db_table_prefix"]."event_list` WHERE `date` = '".$content["DATE"]."' ORDER BY `time` LIMIT ".($page*$CONFIG["max_events"]).",".($CONFIG["max_events"]+1)) or die(mysql_error());
    $num = mysql_num_rows($res);  

    
    if($num > 0)
    {  
      for($i=0; $i < $num; $i++)
      {
        if($i == $CONFIG["max_events"])
          continue;
        $event = array();
        $event["EVENT_NAME"]        = stripslashes(mysql_result($res, $i,"name"));
        $event["EVENT_TIME"]        = substr(mysql_result($res, $i,"time"),0,5);
        $event["EVENT_LOCATION"]    = stripslashes(mysql_result($res, $i,"location"));
        $event["EVENT_DESCRIPTION"] = dmts_parse_bbcode(stripslashes(mysql_result($res, $i,"description")));
        $event["EVENT_LINK"]        = "./?event=".mysql_result($res, $i,"id");
        $event["STRING_EVENT_MORE"] = &$content["STRING_EVENT_MORE"];
        $event["STRING_EVENT_TIME"] = &$content["STRING_EVENT_TIME"];
        
        //create link
        $link = "";
        if((isset($_GET["month"])) && (is_numeric($_GET["month"])))
          $link     .= "&amp;month=".$_GET["month"];
        if((isset($_GET["year"])) && (is_numeric($_GET["year"])))
          $link      .= "&amp;year=".$_GET["year"];   
        if(isset($_GET["date"]))
          $link      .= "&amp;date=".$content["DATE"];       
          
        //add calendar date to link    
        $event["EVENT_LINK"]  .= $link;               

        $content["CONTENT"] .= dmts_echos("./html/event_box.html", $event);
        
        //wrap after every 2nd event
        if((($i+1)%2)==0)
          $content["CONTENT"] .="<div class='evenc_event_wrap'></div>\n";
                
      }
      // finaly add wrap
      $content["CONTENT"] .="<div class='evenc_event_wrap'></div><br />\n";
      // and navi
      if($num > $CONFIG["max_events"])
        $content["CONTENT"] .="<div class='evenc_event_navi_next'><a href='./?page=".($page+1).$link."'>".$content["STRING_EVENT_NAVI_NEXT"]."</a></div>\n";
      if($page > 0)
        $content["CONTENT"] .="<div class='evenc_event_navi_prev'><a href='./?page=".($page-1).$link."'>".$content["STRING_EVENT_NAVI_PREV"]."</a></div>\n";
      $content["CONTENT"] .="<div class='evenc_event_wrap'></div>\n";
    }
    else  // no event found
    {
      $content["CONTENT"] .= dmts_echoss($lang["strings"]["no_event"], $content);
    }
 
    
}

//print page
dmts_echo("./html/index.html", $content);   

//close DB
mysql_close($db);

?>