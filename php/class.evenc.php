<?
/* evenc - a simple event calendar
 * Copyright (c) 2010 Andreas Volk
 * Licensed under the MIT License
 */

require_once('./php/class.account.php');
require_once('./php/class.calendar.php');
require_once('./php/class.check.php');
require_once('./php/class.comment.php');
require_once('./php/class.event.php');
require_once('./php/class.exceptions.php');
require_once('./php/class.icalendar.php');
require_once('./php/class.mysqlconnection.php');
require_once('./php/class.rssfeed.php');
require_once('./php/class.settings.php');
require_once('./php/class.template.php');

/**
 * Evenc - a simple calendar
 * @todo display num comments in event box
 * @todo add XML support
 */
class evenc
{
    /**
     * @var Calendar
     */
    private     $m_cal      = null;
    /**
     *
     * @var DBConnection
     */
    private     $m_db       = null;
    /**
     * @var Settings
     */
    private     $m_settings = null;
    /**
     * @var Templates
     */
    private     $m_tmpl     = null;

    const       Version = '2.0beta8';

    /**
     * Constructor
    */
    public function __construct()
    {
        //Create instance
        $this->m_cal        = &Calendar::getInstance();
    }

    /**
     * Connects to the db, reads settings
     * @param CONFIG Configuration array
     * @param Login Account name
    */
    public function StartUp(&$CONFIG, $login)
    {
        //DB
        $this->m_db =& DBConnection::getInstance(DBConnection::MYSQL);
        // Connect db
        $this->m_db->Connect($CONFIG["db"]["host"], $CONFIG["db"]["user"], $CONFIG["db"]["password"], $CONFIG["db"]["name"]);
        $this->m_db->SetTablePrefix($CONFIG["db"]["prefix"]);

        // Select Account
        $acc = null;
        try
        {
            $acc = $this->m_db->GetAccountByLogin($login);
            if(!$acc)
                die("account does not exist");
            else
                $this->m_db->SetCurrentAccount($acc);
        }
        catch(FormatException $e)
        {
            die("invalid login: ". htmlspecialchars($login));
        }

        // Load settings
        $this->m_settings = new Settings($acc);
        try
        {
            $this->m_db->GetSettings($this->m_settings);
            $this->m_settings->SetMultiUser($CONFIG['multiuser']);
        }
        catch(LanguageException $l)
        {
            $lang = str_replace("[_l_LANGUAGEEXCEPTION_]","",$l->getMessage());
            die("Invalid setting in your database: language = ". htmlspecialchars($lang) .". An language-file for this language does not exist.");
        }
        catch(TemplateException $t)
        {
            die("Invalid setting in your database: template = ". htmlspecialchars($t->getMessage()).". An index-file for this template does not exist.");
        }
        // Vars
        $this->m_tmpl       = new Template($this->m_settings);
    }

    /**
     * Adds an event to the database
    */
    public function AddEvent($StartDate,$EndDate, $Time, $Name, $Location, $Text, $Repeat)
    {
        $this->m_tmpl->SetVar("SUBJECT", "[_l_EVENT_ADD_]");

        //Public Posting allowed?
        if(!$this->m_settings->GetPublicPosting())
        {
            $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage('[_l_EVENT_PUBLICPOSTING_]', "ERROR") );
            $this->PrintPage();
            return;
        }

        $failed = false;
        $event = new Event();
        // Dates
        try
        {
            $this->m_cal->SetDate($StartDate);
            $event->SetStartDate($StartDate);
            $event->SetEndDate($EndDate);
        }
        catch(FormatException $f)
        {
            $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage($f->getMessage(), "ERROR") );
            $this->m_tmpl->AppVar("DEBUG", $f);
            $failed = true;
        }
        // Time
        try
        {
            $event->SetTime($Time);
        }
        catch(FormatException $f)
        {
            $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage($f->getMessage(), "ERROR") );
            $this->m_tmpl->AppVar("DEBUG", $f);
            $failed = true;
        }
        // Name
        try
        {
            $event->SetName($Name);
        }
        catch(FormatException $f)
        {
            $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage($f->getMessage(), "ERROR") );
            $this->m_tmpl->AppVar("DEBUG", $f);
            $failed = true;
        }
        // Location
        try
        {
            $event->SetLocation($Location);
        }
        catch(FormatException $f)
        {
            $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage($f->getMessage(), "ERROR") );
            $this->m_tmpl->AppVar("DEBUG", $f);
            $failed = true;
        }
        // Description
        try
        {
            $event->SetText($Text);
        }
        catch(FormatException $f)
        {
            $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage($f->getMessage(), "ERROR") );
            $this->m_tmpl->AppVar("DEBUG", $f);
            $failed = true;
        }
        // Log
        $log = "IP: ".$_SERVER["REMOTE_ADDR"];
        $event->SetLog($log);
        // Recurrence
        try
        {
            switch($Repeat)
            {
                case "Year":
                {
                    $event->SetRepeat(Event::R_YEAR);
                    break;
                }
                case "Month":
                {
                    $event->SetRepeat(Event::R_MONTH);
                    break;
                }
                case "Day":
                {
                    $event->SetRepeat(Event::R_DAY);
                    break;
                }
                case "Week":
                {
                    $event->SetRepeat(Event::R_WEEK);
                    break;
                }
                default:
                {
                    $event->SetRepeat(Event::R_NONE);
                }
            }
        }
        catch(FormatException $f)
        {
            $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage($f->getMessage(), "ERROR") );
            $this->m_tmpl->AppVar("DEBUG", $f);
            $failed = true;
        }

        //display form
        if($failed)
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayEventForm($event));
            $this->printPage();
            return;
        }

        // add event
        try
        {
            if($this->m_db->AddEvent($event))
            {
                $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage("[_l_EVENT_SUCCESS_]"));
                $this->ShowEvent($event->GetID());
                return;
            }
            else
            {
                $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage("[_l_EVENT_FAILED_]", "ERROR"));
            }
        }
        catch(SQLException $e)
        {
            $this->m_tmpl->SetVar("CONTENT", $this->m_tmpl->DisplayMessage("[_l_SQLEXCEPTION_]", "ERROR") );
            $this->m_tmpl->AppVar("DEBUG", $e);
            $this->printPage();
            return;
        }
        $this->printPage();
    }

    /**
     * Adds a comment to an event
     * @todo implement comment::eventdate
    */
    public function AddComment($EventID, $Name, $EMail, $Text)
    {

        //Comments allowed?
        if(!$this->m_settings->GetComments())
        {
            $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage('[_l_COMMENT_DISABLED_]', "ERROR") );
            $this->PrintPage();
            return;
        }

        $comment = new Comment();
        $failed = false;
        try
        {
            $comment->SetEventID($EventID);
            $comment->SetTime($this->m_cal->GetTimeNow());
            $comment->SetDate($this->m_cal->GetDateNow());
            $log = "IP: ".$_SERVER["REMOTE_ADDR"];
            $comment->SetLog($log);
        }
        catch(FormatException $f)
        {
            $this->m_tmpl->SetVar("CONTENT", $this->m_tmpl->DisplayMessage($f->getMessage(), "ERROR") );
            $this->m_tmpl->AppVar("DEBUG", $f);
            $this->printPage();
            return;
        }
        // Check user input
        try
        {
            $comment->SetName($Name);
        }
        catch(FormatException $f)
        {
            $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage($f->getMessage(), "ERROR") );
            $this->m_tmpl->AppVar("DEBUG", $f);
            $failed = true;
        }
        try
        {
            $comment->SetEMail($EMail);

        }
        catch(FormatException $f)
        {
            $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage($f->getMessage(), "ERROR") );
            $this->m_tmpl->AppVar("DEBUG", $f);
            $failed = true;
        }
        try
        {
            $comment->SetText($Text);
        }
        catch(FormatException $f)
        {
            $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage($f->getMessage(), "ERROR") );
            $this->m_tmpl->AppVar("DEBUG", $f);
            $failed = true;
        }

        // Display form again
        if($failed)
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayCommentForm($comment));
            $this->printPage();
            return;
        }

        // add comment
        try
        {
            if($this->m_db->AddComment($comment))
            {
                $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage("[_l_COMMENT_SUCCESS_]"));
            }
            else
            {
                $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage('[_l_COMMENT_FAILED_]', "ERROR"));
            }
            $this->ShowEvent($EventID);
        }
        catch(SQLException $e)
        {
            $this->m_tmpl->SetVar("CONTENT", $this->m_tmpl->DisplayMessage("[_l_SQLEXCEPTION_]", "ERROR") );
            $this->m_tmpl->AppVar("DEBUG", $e);
            $this->printPage();
            return;
        }
    }

    /**
     * Displays a single event including comments, etc.
     * If it's a series, the give date will be shown.
     * @param ID Event-ID
    */
    public function ShowEvent($ID,$day=0, $month=0, $year=0)
    {
        //set calendar
        $this->m_cal->SetDay($day);
        $this->m_cal->SetMonth($month);
        $this->m_cal->SetYear($year);
        //set vars
        $this->m_tmpl->SetVar('EVENT', '' );
        $this->m_tmpl->SetVar('COMMENTS', '');
        $this->m_tmpl->SetVar('COMMENT_FORM', '');

        $event = NULL;
        try
        {
            $event = $this->m_db->GetEvent($ID);
        }
        catch(FormatException $f)
        {
            $this->m_tmpl->SetVar("CONTENT", $this->m_tmpl->DisplayMessage($f->getMessage(), "ERROR") );
            $this->m_tmpl->AppVar("DEBUG", $f);
            $this->printPage();
            return;
        }
        catch(SQLException $e)
        {
            $this->m_tmpl->SetVar("CONTENT", $this->m_tmpl->DisplayMessage("[_l_SQLEXCEPTION_]", "ERROR") );
            $this->m_tmpl->AppVar("DEBUG", $e);
            $this->printPage();
            return;
        }

        //Print event
        if($event != NULL)
        {
            //Set time
            $this->m_cal->SetTime($event->GetTime());
            //display event
            $this->m_tmpl->AppVar('EVENT', $this->m_tmpl->DisplayEvent($event));

            //Comments enabled
            if($this->m_settings->GetComments())
            {
                //Print comments
                $comments = $this->m_db->GetCommentsForEvent($event);
                if($comments)
                {
                    foreach($comments AS $key => $val)
                    {
                        $this->m_tmpl->AppVar('COMMENTS', $this->m_tmpl->DisplayComment($val));
                    }
                 }
                 // Set date
                if($event->GetRepeat() == Event::R_NONE)
                    $this->m_cal->SetDate($event->getStartDate());

                //Print comment-form
                $c = new Comment();
                $c->SetEventID($event->GetID());
                $this->m_tmpl->AppVar('COMMENT_FORM',$this->m_tmpl->DisplayCommentForm($c));
            }
        }
        //No event available
        else
        {
            $this->m_tmpl->SetVar('EVENT', $this->m_tmpl->DisplayMessage("[_l_EVENT_NONE_]") );
            $this->m_tmpl->SetVar('COMMENTS', '');
            $this->m_tmpl->SetVar('COMMENT_FORM', '');
        }

        $this->m_tmpl->SetVar('CONTENT', $this->m_tmpl->Display('event.html') );

        //Set Date
        $this->m_tmpl->SetVar("SUBJECT",$this->m_cal->GetDateLocale());
        $this->printPage();
    }

    /**
     * Displays the event form.
    */
    public function ShowEventForm($day=0, $month=0, $year=0)
    {
        $this->m_cal->SetDay($day);
        $this->m_cal->SetMonth($month);
        $this->m_cal->SetYear($year);
        $this->m_tmpl->SetVar('SUBJECT', '[_l_EVENT_ADD_]');

        //Public Posting allowed?
        if(!$this->m_settings->GetPublicPosting())
        {
            $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage('[_l_EVENT_PUBLICPOSTING_]', "ERROR") );
            $this->PrintPage();
            return;
        }

        //Display empty form
        $e = new Event();
        $e->SetStartDate($this->m_cal->GetDate());
        $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayEventForm($e,'index.php'));
        $this->printPage();
    }

    /**
     * Displays all events that take place on a date
     */
    public function ShowDate($day=0, $month=0, $year=0)
    {
        //Set Calendar
        $this->m_cal->SetDay($day);
        $this->m_cal->SetMonth($month);
        $this->m_cal->SetYear($year);

        $this->m_tmpl->SetVar('SUBJECT', $this->m_cal->GetDateLocale());


        try
        {
            $events = $this->m_db->GetEventsForDate($this->m_cal->GetDate());
        }
        catch(SQLException $e)
        {
            $this->m_tmpl->SetVar("CONTENT", $this->m_tmpl->DisplayMessage("[_l_SQLEXCEPTION_]", "ERROR") );
            $this->m_tmpl->AppVar("DEBUG", $e);
            $this->printPage();
            return;
        }

        if($events != NULL)
        {
            //Print events
            foreach($events AS $key => $val)
            {
                $this->m_tmpl->AppVar('EVENTS', $this->m_tmpl->DisplayEvent($val));
            }
        }
        else
        {
            //No events found
            $this->m_tmpl->AppVar('EVENTS', $this->m_tmpl->DisplayMessage('[_l_NO_EVENTS_LISTED_]') );
        }
        $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->Display('day.html'));
        $this->printPage();
    }

    /**
     * Displays all events containing the searchstring
    */
    public function ShowSearch($SearchString)
    {
        $this->m_tmpl->SetVar('SUBJECT', '[_l_SEARCH_STRING_]');


        //check length
        if(strlen($SearchString) < 3 )
        {
            $this->m_tmpl->AppVar('EVENTS', $this->m_tmpl->DisplayMessage('[_l_SEARCH_LIMIT_]') );
            $this->m_tmpl->SetVar('SEARCH_FORM', $this->m_tmpl->Display('search_form.html'));
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->Display('search.html'));

            $this->printPage();
            return;
        }

        //get events
        $events = NULL;
        try
        {
            $events = $this->m_db->SearchEvents($SearchString);
        }
        catch(SQLException $s)
        {
            $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->DisplayMessage('[_l_SQLEXCEPTION_]'));
            $this->m_tmpl->AppVar('DEBUG', $s);
            $this->printPage();
            return;
        }
        //list events
        if($events)
        {
            foreach($events AS $key => $val)
            {
                $this->m_tmpl->AppVar('EVENTS', $this->m_tmpl->DisplayEvent($val));
            }
        }
        //none found
        else
        {
            $this->m_tmpl->AppVar('EVENTS', $this->m_tmpl->DisplayMessage("[_l_SEARCH_NOTHING_FOUND_]"));
        }

        $this->m_tmpl->SetVar('SEARCH_FORM', $this->m_tmpl->Display('search_form.html'));
        $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->Display('search.html'));

        $this->printPage();
    }

    /**
     * Displays the search form
    */
    public function ShowSearchForm()
    {
        $this->m_tmpl->SetVar('SUBJECT', '[_l_SEARCH_STRING_]');
        $this->m_tmpl->SetVar('SEARCH_FORM', $this->m_tmpl->Display('search_form.html'));
        $this->m_tmpl->SetVar('EVENTS', '');
        $this->m_tmpl->AppVar('CONTENT', $this->m_tmpl->Display('search.html'));
        $this->printPage();
    }

    /**
     * Finally prints the page
    */
    private function printPage()
    {
        $this->m_tmpl->SetVar('VERSION', self::Version);
        $this->m_tmpl->SetVar('COPYRIGHT', '<a href="http://www.evenc.net">evenc</a> (c) 2005-'.$this->m_cal->GetYearToday().' <a href="http://andreasvolk.de">Andreas Volk</a>');

        //URL
        $this->m_tmpl->SetVar('URL_EVENT_ADD',  $this->m_tmpl->CreateURL( array('action' => 'event',
                                                                                'day' => $this->m_cal->GetDay(),
                                                                                'month' => $this->m_cal->GetMonth(),
                                                                                'year' => $this->m_cal->GetYear())));
        $this->m_tmpl->SetVar('URL_SEARCH',     $this->m_tmpl->CreateURL( array('action' => 'search',
                                                                                'day' => $this->m_cal->GetDay(),
                                                                                'month' => $this->m_cal->GetMonth(),
                                                                                'year' => $this->m_cal->GetYear())));
        $this->m_tmpl->SetVar('URL_FORM_SEARCH', $this->m_tmpl->CreateURL(array()));

        $this->m_tmpl->SetVar('URL_RSS',        $this->m_tmpl->CreateURL( array('action' => 'rss-all')));
        $this->m_tmpl->SetVar('URL_RSS_DATE',   $this->m_tmpl->CreateURL( array('action' => 'rss',
                                                                                'day' => $this->m_cal->GetDay(),
                                                                                'month' => $this->m_cal->GetMonth(),
                                                                                'year' => $this->m_cal->GetYear())));

        $this->m_tmpl->SetVar('URL_ICAL',       $this->m_tmpl->CreateURL( array('action' => 'ical-all')));
        $this->m_tmpl->SetVar('URL_ICAL_DATE',  $this->m_tmpl->CreateURL( array('action' => 'ical',
                                                                                'day' => $this->m_cal->GetDay(),
                                                                                'month' => $this->m_cal->GetMonth(),
                                                                                'year' => $this->m_cal->GetYear())));

        $this->m_tmpl->SetVar('URL_BASE',       $this->m_tmpl->GetBaseURL());


        //Old
        $this->m_tmpl->SetVar("LANG", $this->m_settings->GetLang());

        //Calendar
        try
        {
            $this->m_tmpl->SetVar("CALENDAR", $this->m_tmpl->DisplayCalendar($this->m_cal, $this->m_db));
        }
        catch(SQLException $s)
        {
            $this->m_tmpl->AppVar("DEBUG", $s);
        }
        $this->m_tmpl->SetVar("DAY", $this->m_cal->GetDay());
        $this->m_tmpl->SetVar("MONTH", $this->m_cal->GetMonth());
        $this->m_tmpl->SetVar("YEAR", $this->m_cal->GetYear());
        $this->m_tmpl->SetVar("TIME", $this->m_cal->GetTimeNow());

        if($this->m_settings->GetDebug())
        {
            $debug = $this->m_tmpl->GetVar("DEBUG");
            if($debug)
                $this->m_tmpl->AppVar("CONTENT", $this->m_tmpl->DisplayMessage($debug,Template::ERROR));
        }
        //Navigation
        $this->m_tmpl->SetVar("NAVI", $this->m_tmpl->Display("navi.html"));


        //'The Page'
        $this->m_tmpl->Display("index.html", true);
    }

    /**
     * Displays all Events as an RSS-Feed
    */
    public function RSSAllEvents()
    {
        $feed = new RSSFeed();
        $feed->setTitle('evenc - all events');
        $feed->setDescription('All events');
        $feed->setLink($this->m_tmpl->CreateURL(array('action'=>'rss-all')));
        //Get events
        $events = $this->m_db->GetAllEvents();
        $this->RSSCreateFeed($events, $feed);
        header("Content-Type: application/rss+xml");
        echo $feed->prints();
    }

    /**
     * Displays a single Day as an RSS-Feed
    */
    public function RSSDate($Date)
    {
        if(!Check::Date($Date))
            $Date = Calendar::GetDateNow();

        $this->m_cal->SetDate($Date);

        $feed = new RSSFeed();
        $feed->setTitle('evenc - '.$this->m_cal->GetDate());
        $feed->setDescription($this->m_cal->GetDate());
        $feed->setLink($this->m_tmpl->CreateURL( array( 'action'  => 'rss',
                                                        'year'  => $this->m_cal->GetYear(),
                                                        'month' => $this->m_cal->GetMonth(),
                                                        'day'   => $this->m_cal->GetDay())));
        // Get Events
        $events = $this->m_db->GetEventsForDate($this->m_cal->GetDate(), $feed);
        $this->RSSCreateFeed($events, $feed);
        header("Content-Type: application/rss+xml");
        echo $feed->prints();
    }

    /**
     * Adds events to an rss-feed
     * @param Events Array of Event
     * @param Feed RSSFeed
    */
    private function RSSCreateFeed(&$Events, &$Feed)
    {
        if(!is_array($Events) || !is_a($Feed, 'RSSFeed'))
            return;
        foreach($Events AS $key => $val)
        {
            if(!is_a($val, 'Event'))
                continue;
            //Prepare vars
            $title = $val->GetStartDate().': '.$val->GetName();
            $desc = $val->GetName()."\n".$val->GetStartDate()."\n".$val->GetTime()."\n".$val->GetLocation()."\n".$val->GetText();

            $url = $this->m_tmpl->CreateURL( array('event', $val->GetID()));
            $this->m_cal->SetDate($val->GetStartDate());
            $this->m_cal->SetTime($val->GetTime());
            // Create item
            $item = new RSSItem($title);
            $item->setDescription($desc);
            $item->setLink($url);
            $item->setGUID($val->GetID());
            $item->setGUID(md5(serialize($val)));
            $item->setPubDate($this->m_cal->GetDateRFC());
            // Add item
            $Feed->addItem($item);
        }
    }

    /**
     * Displays a single event as an ical-file
    */
    public function iCalEvent($EventID)
    {
        $ic = new iCalendar();
        $event = null;
        try
        {
            $event = $this->m_db->GetEvent($EventID);
        }
        catch(Exception $e)
        {
        }
        if($event)
            $ic->addEvent($event);

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.urlencode($event->GetName()).'.ics"');
        echo $ic->prints();
    }

    /**
     * Displays a single day as an ical-file
    */
    public function iCalDate($Date)
    {
        if(!Check::Date($Date))
            $Date = Calender::GetDateNow();

        $ic = new iCalendar();
        $events = array();
        try
        {
            $events = $this->m_db->GetEventsForDate($Date);
        }
        catch(Exception $e)
        {
            $events = array();
        }

        foreach($events AS $key => $val)
        {
            $ic->addEvent($val);
        }
        header('Content-Type: text/calendar');

        header('Content-Disposition: attachment; filename="'.$Date.'.ics"');
        echo $ic->prints();
    }

    /**
     * Displays all Events in an ical-file
    */
    public function iCalAllEvents()
    {
        $ic = new iCalendar();
        $events = array();
        try
        {
            $events = $this->m_db->GetAllEvents();
        }
        catch(Exception $e)
        {
            $events = array();
        }

        foreach($events AS $key => $val)
        {
            $ic->addEvent($val);
        }

        header('Content-Type: text/calendar');
        header('Content-Disposition: attachment; filename="evenc.ics"');
        echo $ic->prints();
    }

};
?>
