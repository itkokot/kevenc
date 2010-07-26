<?
/* evenc - a simple event calendar
 * Copyright (c) 2010 Andreas Volk
 * Licensed under the MIT License
 */

require_once("./php/class.account.php");
require_once("./php/class.calendar.php");
require_once("./php/class.check.php");
require_once("./php/class.comment.php");
require_once("./php/class.event.php");
require_once("./php/class.evenc.php");
require_once("./php/class.exceptions.php");
require_once("./php/class.icalendar.php");
require_once("./php/class.mysqlconnection.php");
require_once("./php/class.session.php");
require_once("./php/class.settings.php");
require_once("./php/class.template.php");

/**
 * Evenc Admin Interface
*/
class EvencAdmin
{
    private     $m_db;              /// Database
    private     $m_cal;             /// Calendar
    private     $m_settings;        /// Settings
    private     $m_tmpl;            /// Templates
    private     $m_sess;            /// Session

    private     $print = true;      ///Print Page?


    /**
     * Constructor
    */
    public function __construct()
    {
        //Create instance
        $this->m_settings   = new Settings(new Account());
        $this->m_tmpl       = new Template($this->m_settings);
        $this->m_db         =& DBConnection::GetInstance(DBConnection::MYSQL);
        $this->m_sess       = new Session();
        $this->m_cal        = Calendar::getInstance();
    }

    /**
     * Destructor. Prints the page.
    */
    public function __destruct()
    {
        /* The working directory in the script shutdown phase can be different with some SAPIs (e.g. Apache). */
        chdir(dirname(dirname(__FILE__)));
        $this->PrintPage();
    }

    /**
     * Init internal data
    */
    public function StartUp(&$CONFIG)
    {
        // Connect db
        $this->m_db->Connect($CONFIG["db"]["host"], $CONFIG["db"]["user"], $CONFIG["db"]["password"], $CONFIG["db"]["name"]);
        $this->m_db->SetTablePrefix($CONFIG["db"]["prefix"]);

        if($this->m_sess->IsValid())
        {
            // Set Account
            $this->m_db->SetCurrentAccount($this->m_sess->GetAccount());
            // Load settings
            try
            {
                //Set Settings
                $this->m_settings   = new Settings($this->m_sess->GetAccount());
                $this->m_db->GetSettings($this->m_settings);
                $this->m_tmpl       = new Template($this->m_settings);
            }
            catch(LanguageException $l)
            {
                $lang = str_replace("[_l_LANGUAGEEXCEPTION_]","",$l->getMessage());
                die("Invalid setting in your database: language = ".htmlentities($lang).". An language-file for this language does not exist.");
            }
            catch(TemplateException $t)
            {
                die("Invalid setting in your database: template = ".htmlentities($t->getMessage()).". An index-file for this template does not exist.");
            }
        }
        $this->m_cal->SetToday();
    }

    /**
     * Displays all events and lets the user edit them
    */
    public function ShowEventList()
    {
        if(!Check::Session($this->m_sess))
            return false;

        $this->m_tmpl->SetVar('SUBJECT', 'Eintr&auml;ge');

        $list = $this->m_db->GetAllEvents();
        if(count($list))
        {
            // Display List
            $count = 0;
            foreach($list AS $key => $val)
            {
                // Set Vars
                $evtarray = array();
                $this->m_tmpl->EventToTemplateArray($val, $evtarray);
                $this->m_tmpl->SetArray($evtarray);

                $this->m_tmpl->SetVar('ODDEVEN', (($count++)%2)?'even':'odd');
                // Display
                $this->m_tmpl->AppVar('EVENTLIST', $this->m_tmpl->Display('admin_event.html'));
            }
        }
        else
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Es sind noch keine Einträge vorhanden."));
            $this->m_tmpl->AppVar('EVENTLIST', '');
        }
        //Display
        $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->Display('admin_event_list.html'));
    }

    /**
     * Displays the new-event-form
    */
    public function ShowEventForm()
    {
        if(!Check::Session($this->m_sess))
            return false;

        $this->m_tmpl->SetVar('SUBJECT', 'Neuer Eintrag');

        $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayEventForm(new Event(),'./admin.php?'.$this->m_sess->GetSessionURL()));
    }

    /**
     * Displays the form where a user can edit an event
    */
    public function ShowEventEditForm($EventID)
    {
        if(!Check::Session($this->m_sess))
            return false;

        $this->m_tmpl->SetVar('SUBJECT', 'Eintrag bearbeiten');

        $e = null;

        //Get Event
        try
        {
            $e = $this->m_db->GetEvent($EventID);
        }
        catch(FormatException $f)
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Ungültige Parameter", Template::ERROR));
            $this->m_tmpl->AppVar('DEBUG', $f);
        }
        // Display
        if($e)
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayEventForm($e, './admin.php?'.$this->m_sess->GetSessionURL()));
        }
        else
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Es wurde ein ungültiger Eintrag angegeben.", Template::ERROR));
        }
    }


    /**
     * Displays all comments. The user can delete them.
    */
    public function ShowCommentsList()
    {
        if(!Check::Session($this->m_sess))
            return false;

        $this->m_tmpl->SetVar('SUBJECT', 'Kommentare');
        $list = $this->m_db->GetAllComments();
        if(!count($list))
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Es sind noch keine Kommentare vorhanden."));
            return;
        }
        // Display List
        $count = 0;
        foreach($list AS $key => $val)
        {
            //Set Vars
            $cmtarray = array();
            $this->m_tmpl->CommentToTemplateArray($val, $cmtarray);

            if(strlen($cmtarray['COMMENT_TEXT'])>66)
                substr($cmtarray['COMMENT_TEXT'],0,66).'...';

            $this->m_tmpl->SetArray($cmtarray);
            $this->m_tmpl->SetVar('ODDEVEN', (($count++)%2)?'even':'odd');
            //Display
            $this->m_tmpl->AppVar('COMMENTLIST', $this->m_tmpl->Display('admin_comment.html'));
        }

        //Display
        $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->Display('admin_comment_list.html'));
    }

    /**
     * Shows the settings a user can edit
    */
    public function ShowSettingsPage()
    {
        if(!Check::Session($this->m_sess))
            return false;

        $this->m_tmpl->SetVar('SUBJECT', 'Einstellungen');
        $this->m_tmpl->SetVar('SETTINGS_SELECTION_LANGUAGE',    $this->CreateLanguageSelection());
        $this->m_tmpl->SetVar('SETTINGS_SELECTION_TEMPLATE',    $this->CreateTemplateSelection());
        $this->m_tmpl->SetVar('SETTINGS_BBCODE_CHECK',          $this->m_settings->GetBBCode()?Template::HTML_CHECKED:'');
        $this->m_tmpl->SetVar('SETTINGS_REWRITEURL_CHECK',      $this->m_settings->GetRewriteURL()?Template::HTML_CHECKED:'');
        $this->m_tmpl->SetVar('SETTINGS_PUBLICPOSTING_CHECK',   $this->m_settings->GetPublicPosting()?Template::HTML_CHECKED:'');
        $this->m_tmpl->SetVar('SETTINGS_COMMENTS_CHECK',        $this->m_settings->GetComments()?Template::HTML_CHECKED:'');
        $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->Display("admin_settings.html"));
    }

    /**
     * Displays some Backup Options like Im- and Exporting Events
    */
    public function ShowBackup()
    {
        $this->m_tmpl->SetVar('SUBJECT', 'Backup');
        $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->Display('admin_backup.html'));
    }

    /**
     * Finally prints the page
    */
    private function PrintPage()
    {
        if(!$this->print)
            return;

        $this->m_tmpl->SetVar("VERSION", "admin 0.4");
        $this->m_tmpl->SetVar("COPYRIGHT", "<a href=''>evencAdmin</a> (c) 2005-".$this->m_cal->getYearToday()." <a href='http://andreasvolk.de'>Andreas Volk</a>");
        $this->m_tmpl->SetVar("CALENDAR", '');

        if($this->m_settings->GetDebug())
        {
            $debug = $this->m_tmpl->GetVar("DEBUG");
            if($debug)
                $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage($debug,Template::ERROR));
        }

        //Check Session
        if($this->m_sess->IsValid())
        {
            $this->m_tmpl->SetVar('NAVI', $this->m_tmpl->Display("admin_navi.html"));
            $this->m_tmpl->SetVar('SESSION_URL', $this->m_sess->GetSessionURL());
            $this->m_tmpl->SetVar('SESSION_ACCOUNT_LOGIN', $this->m_sess->GetAccount()->GetLogin());
            $this->m_tmpl->SetVar('SESSION_ACCOUNT_NAME', $this->m_sess->GetAccount()->GetName());
            $this->m_tmpl->SetVar('SESSION_ACCOUNT_EMAIL', $this->m_sess->GetAccount()->GetEMail());
        }
        else
        {
            $this->m_tmpl->SetVar('NAVI', '');
            $this->m_tmpl->SetVar('SUBJECT', '');
            $this->m_tmpl->SetVar('CONTENT', '[_c_SESSION_MESSAGE_]');
            if($this->m_sess->TimedOut())
                $this->m_tmpl->AppVar('SESSION_MESSAGE', $this->m_tmpl->DisplayMessage('Ihre Session wurde wegen Inaktivit&auml;t sicherheitshalber beendet.',Template::ERROR));
            else
                $this->m_tmpl->AppVar('SESSION_MESSAGE', '');
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->Display("admin_login.html"));
        }
        $this->m_tmpl->SetVar('DAY', Calendar::GetDayToday());
        $this->m_tmpl->SetVar('MONTH', Calendar::GetMonthToday());
        $this->m_tmpl->SetVar('YEAR', Calendar::GetYearToday());
        $this->m_tmpl->SetVar('URL_RSS', $this->m_tmpl->CreateURL(array('type' => 'rss'),'file'));
        $this->m_tmpl->SetVar('URL_RSS_DATE', $this->m_tmpl->CreateURL(array('type' => 'rss', 'date' => $this->m_cal->GetDate()),'file'));

        $this->m_tmpl->SetVar('URL_ICAL', $this->m_tmpl->CreateURL(array('type' => 'ical'),'file'));
        $this->m_tmpl->SetVar('URL_ICAL_DATE', $this->m_tmpl->CreateURL(array('type' => 'ical', 'date' => $this->m_cal->GetDate()),'file'));

        $this->m_tmpl->SetVar('URL_BASE', $this->m_tmpl->GetBaseURL());
        $this->m_tmpl->Display("index.html", true);
    }

    /**
     * Logs an user in
    */
    public function Login($AccountLogin, $AccountPassword)
    {
        if(!Check::Login($AccountLogin) || !Check::PasswordHash(Account::CreatePasswordHash($AccountPassword)))
        {
            $this->m_tmpl->SetVar('SESSION_MESSAGE', $this->m_tmpl->DisplayMessage("Sie haben einen ung&uuml;ltigen Login oder Passwort angegeben"));
            return;
        }
        //Check Account and Password
        $acc = $this->m_db->GetAccountByLogin($AccountLogin);
        if($acc && ($acc->GetPassword() == Account::CreatePasswordHash($AccountPassword)) && $acc->GetLogin() == $AccountLogin )
        {
            $this->m_sess->Login($acc);
            $this->m_db->SetCurrentAccount($acc);
            $this->m_db->GetSettings($this->m_settings);
            $this->m_tmpl->SetVar('SUBJECT', 'Login');
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage('Sie wurden erfolgreich eingeloggt.'));
        }
        else
        {
            $this->m_tmpl->SetVar('SESSION_MESSAGE', $this->m_tmpl->DisplayMessage("Sie haben einen ung&uuml;ltigen Login oder Passwort angegeben"));
        }
    }

    /**
     * Logs the current user out
    */
    public function Logout()
    {
        $this->m_sess->Logout();
    }

    /**
     * Changes the settings for this account
    */
    public function EditSettings($Language, $Template, $BBCode, $RewriteURL, $PublicPosting, $Comments)
    {
        if(!Check::Session($this->m_sess))
            return false;

        try
        {
            // Set Values
            $this->m_settings->SetLang($Language);
            $this->m_settings->SetTemplate($Template);
            $this->m_settings->SetBBCode($BBCode);
            $this->m_settings->SetRewriteURL($RewriteURL);
            $this->m_settings->SetPublicPosting($PublicPosting);
            $this->m_settings->SetComments($Comments);
            // Save Settings
            $this->m_db->SetSettings($this->m_settings);
        }
        catch(Exception $e)
        {
            $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage($e->getMessage(), Template::ERROR) );
            $this->m_tmpl->AppVar("DEBUG", $e);
            $this->ShowSettingsPage();
            return;
        }

        // Success
        $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage("Die Einstellungen wurden erfolgreich gespeichert.") );
        $this->ShowSettingsPage();
    }

    /**
     * Edits the current account
    */
    public function EditAccount($Name, $EMail, $Password)
    {
        if(!Check::Session($this->m_sess))
            return false;
        try
        {
            $acc = $this->m_sess->GetAccount();
            $acc->SetName($Name);
            $acc->SetEMail($EMail);
            if(strlen($Password) > 3) // Change password
            {
                $acc->SetPassword(Account::CreatePasswordHash($Password));
            }
            elseif (strlen($Password) > 0)
            {
                $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage('Ihr gewähltes Passwort ist zu kurz.', Template::ERROR) );
            }
            // Save Changes
            $this->m_db->UpdateAccount($acc);
            $this->m_sess->Login($acc);
        }
        catch(Exception $e)
        {
            $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage($e->getMessage(), Template::ERROR) );
            $this->m_tmpl->AppVar("DEBUG", $e);
            $this->ShowSettingsPage();
            return;
        }
        // Success
        $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage("Die Einstellungen wurden erfolgreich gespeichert.") );
        $this->ShowSettingsPage();
    }

    /**
     * Edits an event or adds a new one, if it doesn't exist, yet
     * @param StartDate
     * @param Time
     * @param Name
     * @param Location
     * @param Text
     * @param Recurrence
     * @param EndDate
     * @param ID
    */
    public function EditOrAddEvent($StartDate, $Time, $Name, $Location, $Text, $Recurrence, $EndDate, $ID)
    {
        if(!Check::Session($this->m_sess))
            return false;

        $e = null;
        $newevent = false;

        try
        {
            $e = $this->m_db->GetEvent($ID);
        }
        catch(FormatException $f)
        {
            // Add new event
            $e = null;
        }

        if(!$e)
        {
            $e = new Event();
            $newevent = true;
        }

        try
        {
            $e->SetStartDate($StartDate);
            $e->SetTime($Time);
            $e->SetName($Name);
            $e->SetLocation($Location);
            $e->SetText($Text);
            $e->SetRepeat($Recurrence);
            $e->SetEndDate($EndDate);
            $log = $e->GetLog().Calendar::GetDateNow().",".Calendar::GetTimeNow().",".$_SERVER['REMOTE_ADDR'].",Admin\n";
            $e->SetLog($log);
            if($newevent)
            {
                if($this->m_db->AddEvent($e))
                {
                    $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Der Eintrag wurde erfolgreich angelegt."));
                }
                else
                {
                    $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Der Eintrag konnte nicht gespeichert werden.", Template::ERROR));
                }
            }
            else
            {
                if($this->m_db->UpdateEvent($e))
                {
                    $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage('Der Eintrag wurde erfolgreich geändert.'));
                }
                else
                {
                    $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage('Der Eintrag konnte nicht geändert werden.', Template::ERROR));
                }
            }
            $this->ShowEventList();
        }
        catch(FormatException $f)
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage($f->getMessage(), Template::ERROR));
            $this->m_tmpl->AppVar('DEBUG', $f);
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayEventForm($e, './admin.php?'.$this->m_sess->GetSessionURL()));
        }
        catch(SQLException $s)
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Es ist ein Fehler bei der Abfrage der Datenbank aufgetreten. Bitte kontaktieren Sie einen Administrator.", Template::ERROR));
            $this->m_tmpl->AppVar('DEBUG', $s);
        }
    }

    /**
     * Deletes an existing event and all comments from this event
     * @param EventID Event that should be deleted
    */
    public function DeleteEvent($EventID)
    {
        if(!Check::Session($this->m_sess))
            return false;

        $target = null;
        // Check Event
        try
        {
            $target = $this->m_db->GetEvent($EventID);
        }
        catch(FormatException $f)
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Es wurde ein ungültiger Eintrag angegeben.", Template::ERROR));
            $this->m_tmpl->AppVar('DEBUG', $f);
            return;
        }
        catch(SQLException $s)
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Es ist ein Fehler bei der Abfrage der Datenbank aufgetreten. Bitte kontaktieren Sie einen Administrator.", Template::ERROR));
            $this->m_tmpl->AppVar('DEBUG', $f);
            return;
        }

        if(!$target)
        {
            //Event doesn't exist
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Es wurde ein ungültiger Eintrag angegeben.", Template::ERROR));
            return;
        }
        // Delete Event
        try
        {
            // Delete comments
            if($this->m_db->DeleteAllCommentsForEvent($target))
            {
                // Delete event
                if($this->m_db->DeleteEvent($target))
                {
                    $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Der Eintrag wurde erfolgreich gelöscht."));
                }
                else
                {
                    $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Der Eintrag konnte nicht gelöscht werden.", Template::ERROR));
                }
            }
            else
            {
                $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Die Kommentare des Eintrages konnten nicht gelöscht werden.", Template::ERROR));
            }
        }
        catch(SQLException $s)
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Es ist ein Fehler bei der Abfrage der Datenbank aufgetreten. Bitte kontaktieren Sie einen Administrator.", Template::ERROR));
            $this->m_tmpl->AppVar('DEBUG', $s);
        }
        // Return to list
        $this->ShowEventList();
    }

    /**
     * Deletes an existimg comment
    */
    public function DeleteComment($CommentID)
    {
        if(!Check::Session($this->m_sess))
            return false;

        $c = null;
        try
        {
            $c = $this->m_db->GetComment($CommentID);
        }
        catch(SQLException $s)
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Es ist ein Fehler bei der Abfrage der Datenbank aufgetreten. Bitte kontaktieren Sie einen Administrator.", Template::ERROR));
            $this->m_tmpl->AppVar('DEBUG', $s);
            return;
        }
        catch(FormatException $f)
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Es wurde ein inkorrekter Kommentar angegeben.", Template::ERROR));
            $this->m_tmpl->AppVar('DEBUG', $f);
            return;
        }

        if(!$c)
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Es wurde ein ungültiger Kommentar angegeben.", Template::ERROR));
            return;
        }

        try
        {
            if($this->m_db->DeleteComment($c))
            {
                $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Der Kommentar wurde erfolgreich gelöscht."));
            }
            else
            {
                $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Der Kommentar konnte nicht gelöscht werden.", Template::ERROR));
            }
        }
        catch(SQLException $s)
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Es ist ein Fehler bei der Abfrage der Datenbank aufgetreten. Bitte kontaktieren Sie einen Administrator.", Template::ERROR));
            $this->m_tmpl->AppVar('DEBUG', $s);
            return;
        }
        $this->ShowCommentsList();
    }

    /**
     * Deletes all events and comments from this account and the account itself
    */
    public function DeleteAccount()
    {
        if(!Check::Session($this->m_sess))
            return false;

        try
        {
            if($this->m_db->DeleteAllComments() &&
               $this->m_db->DeleteAllEvents() &&
               $this->m_db->DeleteAllSettings() &&
               $this->m_db->DeleteAccount($this->m_sess->GetAccount()))
            {
                $this->m_tmpl->AppVar('SESSION_MESSAGE', $this->m_tmpl->DisplayMessage('Ihr Account und alle Ihre Daten wurden erfolgreich gelöscht.'));
                $this->Logout();
            }
            else
            {
                $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage('Ihr Account konnte leider nicht gelöscht worden. Bitte kontaktieren Sie einen Administrator.', Template::ERROR));
            }
        }
        catch(SQLException $s)
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Es ist ein Fehler bei der Abfrage der Datenbank aufgetreten. Bitte kontaktieren Sie einen Administrator.", Template::ERROR));
            $this->m_tmpl->AppVar('DEBUG', $s);
            return;
        }
    }

    /**
     * Exports all Events in an iCalendar-File
    */
    public function ExportiCal()
    {
        if(!Check::Session($this->m_sess))
            return false;

        try
        {
            $events = $this->m_db->GetAllEvents();
            $iCal = new iCalendar();
            $iCal->setEvents($events);

        }
        catch(SQLException $s)
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Es ist ein Fehler bei der Abfrage der Datenbank aufgetreten. Bitte kontaktieren Sie einen Administrator.", Template::ERROR));
            $this->m_tmpl->AppVar('DEBUG', $s);
            return;
        }

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="evenc_all.ics"');

        echo $iCal->prints();
        //Dont print regular content
        $this->print = false;
    }

    /**
     * Imports Events from an iCalendar-File
    */
    public function ImportiCal($filename)
    {
        if($filename == "")
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Es wurde keine Datei angegeben.", Template::ERROR));
            return;
        }

        $events = array();
        try
        {
            $iCal = new iCalendar();
            $iCal->parseiCalFile($filename);
            $events = $iCal->getEvents();
        }
        catch(FileException $f)
        {
            $this->m_tmpl->AppVar('DEBUG', $f);
        }
        catch(ParseException $p)
        {
            $this->m_tmpl->AppVar('DEBUG', $p);
        }

        //No events
        if(!count($events))
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Die Datei enthielt keine Events.", Template::ERROR));
            return;
        }

        //Failures
        $failure = array();
        try
        {
            foreach($events AS $key => $val)
            {
                if(!$this->m_db->AddEvent($val))
                    $failure[] = $key;
            }
        }
        catch(SQLException $s)
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage("Es ist ein Fehler bei der Abfrage der Datenbank aufgetreten. Bitte kontaktieren Sie einen Administrator.", Template::ERROR));
            $this->m_tmpl->AppVar('DEBUG', $s);
            return;
        }
        $this->m_tmpl->AppVar('CONTENT',$this->m_tmpl->DisplayMessage('Es konnte(n) '.(count($events)-count($failure)).' von '.count($events).' Event(s) importiert werden.'));
    }

    /*
     * HELPER FUNCTIONS
    */
    /**
     * @returns String A <option></option>-List of all available templates
    */
    private function CreateTemplateSelection()
    {
        $retval = "";
        $dirlist = scandir("./html/");  // list available dirs
        $currtemplate = $this->m_settings->GetTemplate();

        foreach($dirlist AS $key => $val)
        {
            if(Check::Template($val)) // check valid template
            {
                if($val === $currtemplate)
                    $retval .= '<option value="'.$val.'" selected="selected" >'.$val.'</option>';
                else
                    $retval .= '<option value="'.$val.'">'.$val.'</option>';
            }
        }
        return $retval;
    }

    /**
     * @returns String A <option></option>-List of all available languages
    */
    private function CreateLanguageSelection()
    {
        $retval = "";
        $dirlist = scandir("./lang/");  // list available dirs
        $currlang = $this->m_settings->GetLang();

        foreach($dirlist AS $key => $val)
        {
            // remove .lang
            $val = str_replace('.lng', '', $val);
            if(Check::Language($val)) // check valid language
            {
                if($val === $currlang)
                    $retval .= '<option value="'.$val.'" selected="selected" >'.$val.'</option>';
                else
                    $retval .= '<option value="'.$val.'">'.$val.'</option>';
            }
        }
        return $retval;
    }
}

?>
