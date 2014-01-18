<?
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
// Required vars:
//  year    YYYY
//  month   MM
//
//init vars
$calendar = array();
$calendar["CALENDAR_DAYS"] = "";
(isset($_GET["year"]) && is_numeric($_GET["year"]) ) ?  $year = $_GET["year"] : $year = 0;
(isset($_GET["month"]) && is_numeric($_GET["month"]) ) ?  $month  = $_GET["month"] : $month = 0;
  //year
if( (is_numeric($year)) && ($year >1970 ) && ( $year < 2050) )
  $calendar["CALENDAR_YEAR"] = $year;
else
  $calendar["CALENDAR_YEAR"] = date("Y");
  
// set link vars  
$calendar["CALENDAR_YEAR_PREV"] = $calendar["CALENDAR_YEAR"] -1;
$calendar["CALENDAR_YEAR_NEXT"] = $calendar["CALENDAR_YEAR"] +1;

  //month
if( (is_numeric($month)) && ($month >0 ) && ( $month < 13) )
  $calendar["CALENDAR_MONTH"] = $month;
else
  $calendar["CALENDAR_MONTH"] = date("m");
  
// set link vars
$calendar["CALENDAR_MONTH_PREV"] = $calendar["CALENDAR_MONTH"] -1;
$calendar["CALENDAR_MONTH_NEXT"] = $calendar["CALENDAR_MONTH"] +1;  

// check month
if($calendar["CALENDAR_MONTH_PREV"] < 1)
  $calendar["CALENDAR_MONTH_PREV"] = 12;
if($calendar["CALENDAR_MONTH_NEXT"] > 12)
  $calendar["CALENDAR_MONTH_NEXT"] = 1;  

  
// add date [Bug #1]
$link = "";
if( (isset($_GET["date"])) &&(preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $_GET["date"])))  
  $link .= "&amp;date=".$_GET["date"];
  
if((isset($_GET["page"])) && (is_numeric($_GET["page"])))
  $link .= "&amp;page=".$_GET["page"];
  
// Create Links
$calendar["CALENDAR_LINK_YEAR_NEXT"] = "&amp;year=".$calendar["CALENDAR_YEAR_NEXT"]
                                    ."&amp;month=".$calendar["CALENDAR_MONTH"]
                                    .$link;
                                    
$calendar["CALENDAR_LINK_YEAR_PREV"] = "&amp;year=".$calendar["CALENDAR_YEAR_PREV"]
                                    ."&amp;month=".$calendar["CALENDAR_MONTH"]
                                    .$link;     
                                   
$calendar["CALENDAR_LINK_MONTH_NEXT"] = "&amp;year=".$calendar["CALENDAR_YEAR"]
                                    ."&amp;month=".$calendar["CALENDAR_MONTH_NEXT"]
                                    .$link;     
                                                                     
$calendar["CALENDAR_LINK_MONTH_PREV"] = "&amp;year=".$calendar["CALENDAR_YEAR"]
                                    ."&amp;month=".$calendar["CALENDAR_MONTH_PREV"]
                                    .$link;                                                                    
  
// now add days  
// what day [Mo-So] , we have to skip this cols So=0, Mo=1 -> -1
$first_day = date("w", mktime(0, 0, 0, $calendar["CALENDAR_MONTH"], 1, $calendar["CALENDAR_YEAR"]))-1;
if($first_day < 0)
  $first_day = 6;
$num_days =date("t", mktime(0, 0, 0, $calendar["CALENDAR_MONTH"], 1, $calendar["CALENDAR_YEAR"]));
$day_today = date("j");
$month_today = date("m");

$num_cols = $first_day + $num_days;
while($num_cols%7)
  $num_cols++;

$calendar["CALENDAR_DAYS"] .= "<tr>\n";
$link = "";

//skip days
for($i=0;$i<$first_day;$i++)
{
  $calendar["CALENDAR_DAYS"] .="<td></td>\n";
}

//fill days

for($i=1;$i<=$num_days; $i++)
{
  $link = "";
  $class = "";
  $date = date("Y-m-d", mktime(0, 0, 0, $calendar["CALENDAR_MONTH"], $i, $calendar["CALENDAR_YEAR"]));
  
 // check if event
  $res = mysql_query("SELECT COUNT(*) FROM `".$CONFIG["db_table_prefix"]."event_list` WHERE `date` = '$date'") or die(mysql_error());
  $num = mysql_result($res,0,"COUNT(*)");
  if($num > 0)
    $class = "evenc_calendar_event";
  
  //check if date is today
  if(($i == $day_today) && ($calendar["CALENDAR_MONTH"] == $month_today) && ($calendar["CALENDAR_YEAR"] == date("Y")))
    $class .= " evenc_calendar_today";    
  
  $link = "<a class='$class' href='./?date=".$date."&amp;month=".$calendar["CALENDAR_MONTH"]."&amp;year=".$calendar["CALENDAR_YEAR"]."'>$i</a>";
  $calendar["CALENDAR_DAYS"] .="<td>$link</td>\n";
  if((($i+$first_day)%7)==0)
    $calendar["CALENDAR_DAYS"] .= "</tr><tr>\n";    
}

// fill table
while( ($num_days+$first_day)%7)
{
  $calendar["CALENDAR_DAYS"] .="<td></td>\n";
  $first_day++;
}

$calendar["CALENDAR_DAYS"] .= "</tr>\n";

// set month name instead of number
$calendar["CALENDAR_MONTH_NAME"] = date("F", mktime(0, 0, 0, $calendar["CALENDAR_MONTH"], 1, $calendar["CALENDAR_YEAR"]));
// set calendar days
$calendar_days = explode("|", $lang["calendar"]["days"]);
for($i=0; $i < 7; $i++)
  $calendar["CALENDAR_DAY_$i"] = $calendar_days[$i];

$content["CALENDAR"] = dmts_echos("./html/calendar.html", $calendar);
?>