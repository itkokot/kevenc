<?
/* evenc - a simple event calendar
 * Copyright (c) 2010 Andreas Volk
 * Licensed under the MIT License
 */

/**
 * Wrapper file for evenc
 * @todo: Template: add day to calendar year/month switch
 * /year/month/day/id/action - only for _GET
 * /2007/12/24/12 - Event #12 on 2007-12-24 - empty action
 * /2007/11/24/rss - Date 2007-12-24 as rss feed - empty id
 * /2007/12/24/ical - Date 2007-12-24 is ical file - empty id
 * /2007/12/24/12/ical - Event #12 on 2007-12-24 as ical-file
 */
require_once("./php/class.evenc.php");

require_once("./config.php");
// prepare vars
(!isset($_GET["day"]))?$_GET["day"] = 0:"";
(!isset($_GET["month"]))?$_GET["month"] = 0:"";
(!isset($_GET["year"]))?$_GET["year"] = 0:"";

// Start evenc
$evenc = new Evenc();
$login = ($CONFIG['multiuser'] && isset($_GET['login']))?$_GET['login']:$CONFIG['login'];
$evenc->StartUp($CONFIG, $login);

// Search
if(isset($_POST["action"]) && ($_POST["action"] == "search"))
{
    $evenc->ShowSearch($_POST["search_string"]);
}
// Add event
else if(isset($_POST["action"]) && ($_POST["action"] == "event"))
{
    $enddate = $_POST["date"];
    switch($_POST['period'])
    {
        case 'forever':
        {
            $enddate = Calendar::FOREVER;
            break;
        }
        case 'until':
        {
            $enddate = $_POST['until'];
            break;
        }
        case 'times':
        {
            $rec = Event::R_NONE;
            switch($_POST['recurrence'])
            {
                case 'Day':
                    $rec = Event::R_DAY;
                break;
                case 'Week':
                    $rec = Event::R_WEEK;
                break;
                case 'Month':
                    $rec = Event::R_MONTH;
                break;
                case 'Year':
                    $rec = Event::R_YEAR;
                break;
            }
            $enddate = Calendar::ComputeEndDate($_POST["date"],$_POST["times"],$rec);
            break;
        }
    }

    $evenc->AddEvent($_POST["date"],
                    $enddate,
                    $_POST["time"],
                    $_POST["name"],
                    $_POST["location"],
                    $_POST["text"],
                    $_POST["recurrence"]);
}
// Add comment
else if(isset($_POST["action"]) && ($_POST["action"] == "comment"))
{
    $evenc->AddComment($_POST["id"],
                    $_POST["name"],
                    $_POST["email"],
                    $_POST["text"]);
}
else if(isset($_GET["action"]) && isset($_GET["event"]))
{
    if($_GET['action'] == 'ical')
        $evenc->iCalEvent($_GET['event']);
    else
        $evenc->ShowEvent($_GET["event"],$_GET["day"],$_GET["month"],$_GET["year"]);
}
// Show searchform
else if(isset($_GET["action"]))
{
    switch($_GET["action"])
    {
        // Search
        case 'search':
        {
            $evenc->ShowSearchForm();
            break;
        }
        // Add Event
        case 'event':
        {
            $evenc->ShowEventForm($_GET["day"],$_GET["month"],$_GET["year"]);
            break;
        }
        // ical for a date
        case 'ical':
        {
            $evenc->iCalDate($_GET["year"].'-'.$_GET["month"].'-'.$_GET["day"]);
            break;
        }
        // ical for all events
        case 'ical-all':
        {
            $evenc->iCalAllEvents();
            break;
        }
        // Rss for a date
        case 'rss':
        {
            $evenc->RSSDate($_GET["year"].'-'.$_GET["month"].'-'.$_GET["day"]);
            break;
        }
        // Rss for all events
        case 'rss-all':
        {
            $evenc->RSSAllEvents();
            break;
        }
        //
        default:
        {
            $evenc->ShowDate($_GET["day"],$_GET["month"],$_GET["year"]);
        }
    }
}
// Single event
else if(isset($_GET["event"]))
{
    $evenc->ShowEvent($_GET["event"],$_GET["day"],$_GET["month"],$_GET["year"]);
}
// List day
else
{
    $evenc->ShowDate($_GET["day"],$_GET["month"],$_GET["year"]);
}

?>
