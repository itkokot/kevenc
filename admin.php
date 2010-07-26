<?
/* evenc - a simple event calendar
 * Copyright (c) 2010 Andreas Volk
 * Licensed under the MIT License
 */

require_once("./php/class.evencadmin.php");

require_once("./config.php");

$evcadm = new EvencAdmin();
$evcadm->StartUp($CONFIG);

if(isset($_GET['action']))
{
    switch($_GET['action'])
    {
        case "login":
        {
            $login = isset($_POST['login'])?$_POST['login']:"";
            $password = isset($_POST['password'])?$_POST['password']:"";
            $evcadm->Login($login,$password);
            break;
        }
        case "logout":
        {
            $evcadm->Logout();
            break;
        }
        case "events":
        {
            $evcadm->ShowEventList();
            break;
        }
        case "addevent":
        {
            $evcadm->ShowEventForm();
            break;
        }
        case 'editevent':
        {
            $evcadm->ShowEventEditForm($_GET['id']);
            break;
        }
        case 'deleteevent':
        {
            if(isset($_GET['id']))
                $evcadm->DeleteEvent($_GET['id']);
            break;
        }
        case "comments":
        {
            $evcadm->ShowCommentsList();
            break;
        }
        case "settings":
        {
            if(isset($_POST['delete']))
            {
                $evcadm->DeleteAccount();
            }
            else if(isset($_POST['language']) && isset($_POST['template']))
            {
                $evcadm->EditSettings($_POST['language'],
                                        $_POST['template'],
                                        isset($_POST['bbcode']),
                                        isset($_POST['rewriteurl']),
                                        isset($_POST['publicposting']),
                                        isset($_POST['comments']));
            }
            elseif(isset($_POST['name']) && isset($_POST['email']) && isset($_POST['password']) )
            {
                $evcadm->EditAccount($_POST['name'], $_POST['email'], $_POST['password']);
            }
            else
            {
                $evcadm->ShowSettingsPage();
            }
            break;
        }
        case 'backup':
        {
            if(isset($_POST['export']))
            {
                $evcadm->ExportiCal();
            }
            elseif(isset($_POST['import']) && isset($_FILES['ical']))
            {
                $evcadm->ImportiCal($_FILES['ical']['tmp_name']);
            }
            $evcadm->ShowBackup();
            break;
        }
    }
}
elseif(isset($_POST['action']))
{
    switch($_POST['action'])
    {
        case 'event':
        {
            if( isset($_POST['date']) &&
                isset($_POST['time']) &&
                isset($_POST['name']) &&
                isset($_POST['location']) &&
                isset($_POST['text']) &&
                isset($_POST['recurrence']) &&
                isset($_POST['period']) &&
                isset($_POST['until']) &&
                isset($_POST['times']) &&
                isset($_POST['id']))
            {
                $rec = Event::R_NONE;
                switch($_POST['recurrence'])
                {
                    case 'Day':
                        $rec = Event::R_DAY ;
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
                $enddate = $_POST['date'];
                try
                {
                    $enddate = Calendar::ComputeEndDate($_POST['date'], $_POST['times'], $rec);
                }
                catch(Exception $e)
                {
                    $enddate = $_POST['date'];
                    $rec = Event::R_NONE;
                }
                $evcadm->EditOrAddEvent($_POST['date'],
                                        $_POST['time'],
                                        $_POST['name'],
                                        $_POST['location'],
                                        $_POST['text'],
                                        $rec,
                                        $enddate,
                                        $_POST['id']);
                break;
            }
        }
        case 'comment':
        {
            if(isset($_POST['id']))
                $evcadm->DeleteComment($_POST['id']);
        }
    }
}

?>
