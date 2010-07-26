<?
/* evenc - a simple event calendar
 * Copyright (c) 2010 Andreas Volk
 * Licensed under the MIT License
 */

/**
    Simple Template class.
    [_c_CONTENT_]
    [_l_LANGUAGE_]
*/
class Template
{
    private $m_Content;

    const ERROR             = 'ERROR';
    const MESSAGE           = 'MESSAGE';

    const HTML_CHECKED      = 'checked="checked"';

  /**
    Strings for the current language
  */
    private $m_LangStrings;

    private $m_Settings;

    public function __construct(Settings &$Settings)
    {
        $this->m_Settings = $Settings;
        $this->m_Content = array("CONTENT" => "", "SUBJECT" => "");
        $this->m_LangStrings = NULL;

        //Make Settings Public
        //SETTINGS(if-construct)
        $this->SetVar('SETTINGS_BBCODE',        $this->m_Settings->GetBBCode());
        $this->SetVar('SETTINGS_DEBUG',         $this->m_Settings->GetDebug());
        $this->SetVar('SETTINGS_REWRITEURL',    $this->m_Settings->GetRewriteURL());
        $this->SetVar('SETTINGS_MULTIUSER',     $this->m_Settings->GetMultiUser());
        $this->SetVar('SETTINGS_PUBLICPOSTING', $this->m_Settings->GetPublicPosting());
    }

  /**
    Sets a var.
  */
    public function SetVar($Name, $Value)
    {
        $this->m_Content[$Name] = $Value;
    }

    /**
     * Sets an array as if their keys are names.
    */
    public function SetArray(&$Array)
    {
        if(!is_array($Array))
            return false;
        foreach($Array AS $key => $val)
        {
            $this->m_Content[$key] = $val;
        }
    }

  /**
    Appends data to a var.
  */
    public function AppVar($Name, $Value)
    {
        if(!isset($this->m_Content[$Name]))
            $this->m_Content[$Name] = "";
        $this->m_Content[$Name] .= $Value;
    }
    /*
    Returns the given var.
    */
    public function GetVar($Name)
    {
        return (isset($this->m_Content[$Name]))?$this->m_Content[$Name]:NULL;
    }

  /**
    Parses a string and replaces all [_c_*_] vars listed in Array.
    @return none
  */
    public function Parse(&$Str, &$Array)
    {

        while(list($key, $value) = each($Array))
        {
            $Str = str_replace("[_c_".$key."_]", $value, $Str);
        }
        reset($Array);
    }

  /**
    Parses a string an replaces all [_l_*_] vars listet in LangStrings Array
    @return none
  */
    public function ParseLang(&$Str)
    {
        if(!$this->m_LangStrings)
            $this->m_LangStrings = parse_ini_file("./lang/".$this->m_Settings->GetLang().".lng", false);

        while(list($key, $value) = each($this->m_LangStrings))
        {
            $Str = str_replace("[_l_".$key."_]", $value, $Str);
        }
        reset($this->m_LangStrings);
    }

    public function ParseBBCode(&$Str)
    {
        /*
        ToDo:
        [font][/font]
        [list][*][*][/list]
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
          $replace[5] ="<blockquote class='bbc_quote' cite='$2'><p>$3</p></blockquote>";
          //[code]text[/code]
          $bbcode[6]  ="\[code\](.*)\[\/code\]";
          $replace[6] ="<code class='bbc_code'>$1</code>";
          //[size=X][/size] // size 0-19
          $bbcode[7]  ="\[size(=([1]?[0-9]{1})){1}\](.*)\[\/size\]";
          $replace[7] ="<span class='bbc_size' style='font-size: $2"."ex;'>$3</span>";
          //[size=X][/size] // size 0-19
          $bbcode[8]  ="\[font(=([\s\w]*)){1}\](.*)\[\/font\]";
          $replace[8] ="<span class='bbc_size' style=\"font-family: '$2';\">$3</span>";

          //[url]addy[/url]
          $bbcode[9]  ="\[url\](.*)\[\/url\]";
          $replace[9] ="<a href='$1'>$1</a>";
          //[url=addy]text[/url]
          $bbcode[10]  ="\[url(=(.*)){1}\](.*)\[\/url\]";
          $replace[10] ="<a href='$2'>$3</a>";

          // add parameter to bbcode
          foreach($bbcode AS $key => $val)
            $bbcode[$key] = "/".$bbcode[$key]."/isuU";

          //[list][*][*][/list] ... wow - it works :)
          $bbcode[11]  ="/\[list\](.*)\[\/list\]/eisuU";
          $replace[11] ='\'<ul class="bbc_list">\'.preg_replace(\'/(\[\*\]([^[]*))/is\',\'<li>\$2</li>\',\'$1\').\'</ul>\'';

          $Str = preg_replace($bbcode, $replace, $Str);
    }

    const IF_FULL   = 0;
    const IF_VAR    = 1;
    const IF_BODY   = 2;
    const IF_ELSE   = 5;

    /**
     * Parses an string and resolves any if-construct
    */
    public function ParseConstructs(&$Str, &$Array)
    {
        $matches = array();
        //IF CONSTRUCT
        if(preg_match_all('@\[_if_([A-Z0-9_.]*)_\]((.|\n)*)(\[_else_\]((.|\n)*))?\[_endif_\]@U',$Str, $matches, PREG_SET_ORDER))
        {
            foreach($matches AS $if)
            {
                //IF_VAR is set
                if(isset($Array[$if[self::IF_VAR]]) && $Array[$if[self::IF_VAR]])
                {
                    $Str = str_replace($if[self::IF_FULL],$if[self::IF_BODY],$Str);
                }
                //IF_VAR not set AND else exists
                else if(isset($if[self::IF_ELSE]))
                {
                    $Str = str_replace($if[self::IF_FULL],$if[self::IF_ELSE],$Str);
                }
                //IF_VAR not set NO else exists
                else
                {
                    $Str = str_replace($if[self::IF_FULL],'',$Str);
                }
            }
        }
    }

    /**
     * Reads a File and writes it into the Buffer
     * @return String or NULL
     * @throws FileException
    */
    public function File2String($File)
    {
        $path = "." . DIRECTORY_SEPARATOR . "html" . DIRECTORY_SEPARATOR . $this->m_Settings->getTemplate();
        return file_get_contents( $path . DIRECTORY_SEPARATOR . $File);
    }

  /**
    Displays the File. Parses all [_*_] vars.
    @return bool Success
  */
    public function Display($File, $Print = false)
    {
        // read file
        $buffer = $this->File2String($File);
        if(!$buffer)
            return false;

        //construct
        $this->ParseConstructs($buffer, $this->m_Content);

        // replace
        $this->Parse($buffer, $this->m_Content);

        //language
        $this->ParseLang($buffer);

        if($Print)
        {
            echo $buffer;
            return true;
        }
        else
            return $buffer;
    }


  /**
    @return String Htmlcode of the event
  */
    public function DisplayEvent(Event &$Event, $Print=false)
    {
        //Read File
        $buffer = "";
        try
        {
            $buffer = $this->File2String("event_box.html");
        }
        catch(FileException $f)
        {
            return "could not load content from event_form.html";
        }


        //Prepare Array
        $eventarray = array();
        $this->EventToTemplateArray($Event, $eventarray);

        //bbcode
        if($this->m_Settings->GetBBCode())
            $this->ParseBBCode($eventarray["EVENT_TEXT"]);

        //replace
        $this->Parse($buffer, $eventarray);
        $this->Parse($buffer, $this->m_Content);

        //language
        $this->ParseLang($buffer);

        if($Print)
        {
            echo $buffer;
            return true;
        }
        else
            return $buffer;
    }

    /**
     * Displays the event form for a given event as preselected values
     * @param URL Url the form should point to
     * @return String
    */
    public function DisplayEventForm(Event $E, $URL='index.php')
    {
        //Readfile
        $buffer = "";
        try
        {
            $buffer = $this->File2String("event_form.html");
        }
        catch(FileException $f)
        {
            return "could not load content from event_form.html";
        }

        //prepare
        $eventarray = array();
        if($URL == 'index.php')
            $eventarray['URL_FORM_EVENT'] = $this->CreateURL(array(),$URL);
        else
            $eventarray['URL_FORM_EVENT'] = $URL;

        $this->EventToTemplateArray($E, $eventarray);
        switch($E->GetRepeat())
        {
            case Event::R_NONE:
                $eventarray['EVENT_RECURRENCE_NONE']    = self::HTML_CHECKED;
                break;
            case Event::R_DAY:
                $eventarray['EVENT_RECURRENCE_DAY']     = self::HTML_CHECKED;
                break;
            case Event::R_WEEK:
                $eventarray['EVENT_RECURRENCE_WEEK']    = self::HTML_CHECKED;
                break;
            case Event::R_MONTH:
                $eventarray['EVENT_RECURRENCE_MONTH']   = self::HTML_CHECKED;
                break;
            case Event::R_YEAR:
                $eventarray['EVENT_RECURRENCE_YEAR']    =  self::HTML_CHECKED;
                break;
        }
        if($E->GetEndDate() == Calendar::FOREVER)
        {
            $eventarray['EVENT_PERIOD_FOREVER'] = self::HTML_CHECKED;
            $eventarray['EVENT_ENDDATE']  = Calendar::GetDateNow();
        }
        else
        {
            $eventarray['EVENT_PERIOD_UNTIL']   = self::HTML_CHECKED;
        }

        $this->ParseConstructs($buffer, $this->m_Content);

        //parse
        $this->Parse($buffer, $eventarray);

        return $buffer;
    }

  /**
    Displays a Messagebox
    @param Type Can be "MESSAGE"(default) or "ERROR"
    @return String Htmlcode of the Messagebox
  */
    public function DisplayMessage($Text, $Type=Template::MESSAGE, $Print=false)
    {
        //Read File
        $buffer = $this->File2String("message_box.html");
        if(!$buffer)
            return;

        //Prepare Array
        $msgarray["MESSAGE_TYPE"] = "[_l_".$Type."_]";
        $msgarray["MESSAGE_TEXT"] = $Text;

        //replace
        $this->Parse($buffer, $msgarray);

         //language
        $this->ParseLang($buffer);

        if($Print)
        {
            echo $buffer;
            return true;
        }
        else
            return $buffer;
    }

  /**
    Displays a Comment.
    @return String
  */
    public function DisplayComment(Comment &$Comment, $Print=false)
    {
        //Read File
        $buffer = $this->File2String("comment_box.html");
        if(!$buffer)
            return NULL;

        //Prepare Array
        $com = array();
        $this->CommentToTemplateArray($Comment, $com);

        //bbcode
        if($this->m_Settings->GetBBCode())
            $this->ParseBBCode($com["COMMENT_TEXT"]);

        //replace
        $this->Parse($buffer, $com);
        $this->Parse($buffer, $this->m_Content);

        //language
        $this->ParseLang($buffer);

        if($Print)
        {
            echo $buffer;
            return true;
        }
        else
            return $buffer;
    }

    /**
     * Displays the comment form
    */
    public function DisplayCommentForm(Comment $Comment)
    {
        //Readfile
        $buffer = "";
        try
        {
            $buffer = $this->File2String("comment_form.html");
        }
        catch(FileException $f)
        {
            return "could not load content from comment_form.html";
        }
        //prepare
        $commentarray = array();
        $commentarray['URL_FORM_COMMENT'] = $this->CreateURL(array());
        $this->CommentToTemplateArray($Comment, $commentarray);
        //replace
        $this->Parse($buffer, $commentarray);

        return $buffer;
    }

  /**
    Displays the calendar
    @return string
  */
    public function DisplayCalendar(Calendar &$Calendar, DBConnection &$MySQLConnection, $Print=false)
    {
        //Read File
        $buffer = $this->File2String("calendar.html");
        if(!$buffer)
            return NULL;

        //Prepare Array
        $calendararray["YEAR"] = $Calendar->GetYear();
        $calendararray["MONTH"] = $Calendar->GetMonth(true);

        $calendararray["CALENDAR_DAYS"] = "<tr>";

            // leading days
        $empty_days = $Calendar->GetFirstWeekdayOfMonth()-1;
        for($i=0; $i< $empty_days; $i++)
        {
            $calendararray["CALENDAR_DAYS"] .= "<td> </td>";
        }

            // days
        $num_days = $Calendar->GetDaysOfMonth();
        $class = "";
        for($i=0; $i<$num_days; $i++)
        {
            $class = "";
            if((($i+$empty_days)%7)==0)
                $calendararray["CALENDAR_DAYS"] .="</tr><tr>";

            //events
            $numevents = $MySQLConnection->NumEventsAtDate($Calendar->GetYear()."-".$Calendar->GetMonth()."-".(($i<9)?"0":"").($i+1));
            if($numevents && $numevents <= 1)           // 1
                $class = "events_1";
            elseif($numevents > 1 && $numevents <= 4)   // 2 - 4
                $class = "events_2";
            elseif($numevents > 4 && $numevents <= 7)   // 5 - 7
                $class = "events_3";
            elseif($numevents > 7 && $numevents <= 10)  // 8 - 10
                $class = "events_4";
            elseif($numevents > 10)
                $class = "events_5";

            //today
            if($Calendar->GetYear() == $Calendar->GetYearToday())
            {
                if($Calendar->GetMonth() == $Calendar->GetMonthToday())
                {
                    if(($i+1) == $Calendar->GetDayToday())
                    {
                        $class .= " today";
                    }
                }
            }

            //title
            $title = $Calendar->GetDateLocale();
            if($numevents == 1)
                $title .= ' - 1 [_l_EVENT_]';
            elseif($numevents)
                $title .= ' - '.$numevents.' [_l_EVENTS_]';

            $calendararray["CALENDAR_DAYS"] .= '<td><a class="'.$class.'" title="'.$title.'" href="'.$this->CreateURL( array('day' => ($i+1),'month' =>$Calendar->GetMonth(), 'year' => $Calendar->GetYear())).'">'.($i+1).'</a></td>'."\n";

        }

            // following days
        $follow_days = 0;
        while( ($num_days+$empty_days+$follow_days)%7)
        {
            $calendararray["CALENDAR_DAYS"] .= "<td> </td>";
            $follow_days++;
        }


        //
        $calendararray["CALENDAR_DAYS"] .="</tr>";

        $month_prev = array('month' => $Calendar->GetMonth()-1, 'year' => $Calendar->GetYear());
        $month_next = array('month' => $Calendar->GetMonth()+1, 'year' => $Calendar->GetYear());

        if($month_prev['month'] == 0)
        {
            $month_prev['month'] = 12;
            $month_prev['year'] -= 1;
        }

        if($month_next['month'] == 13)
        {
            $month_next['month'] = 1;
            $month_next['year'] += 1;
        }
        $calendararray["CALENDAR_URL_MONTH_PREV"] = $this->CreateURL($month_prev);
        $calendararray["CALENDAR_URL_MONTH_NEXT"] = $this->CreateURL($month_next);
        $calendararray["CALENDAR_URL_YEAR_PREV"]  = $this->CreateURL(array('month' => $Calendar->GetMonth(), 'year' => $Calendar->GetYear()-1));
        $calendararray["CALENDAR_URL_YEAR_NEXT"]  = $this->CreateURL(array('month' => $Calendar->GetMonth(), 'year' => $Calendar->GetYear()+1));
        //replace
        $this->Parse($buffer, $calendararray);

        //language
        $this->ParseLang($buffer);

        if($Print)
        {
            echo $buffer;
            return true;
        }
        else
            return $buffer;
    }
    /**
     * Creates an url for a link.
     * index := [login/]year/month/day[/event][/action]
     * where action can be 'ical', 'rss', 'ical-all' or 'rss-all'
     * @param Args Array containing the keys 'day', 'month', 'year' and 'action'
     * @param File 'index' or 'file'
     * @todo introduce rewriteable urls
    */
    public function CreateURL($Args, $File = 'index.php')
    {
        $valid_actions = array('ical', 'ical-all', 'rss', 'rss-all', 'search', 'event');

        if(!isset($Args['day']) || !Check::Day($Args['day']))
            $Args['day'] = Calendar::GetDayToday();
        if(!isset($Args['month']) || !Check::Day($Args['month']))
            $Args['month'] = Calendar::GetMonthToday();
        if(!isset($Args['year']) || !Check::Year($Args['year']) )
            $Args['year'] = Calendar::GetYearToday();
        if(isset($Args['event']) && !Check::ID($Args['event']))
            unset($Args['event']);
        if(isset($Args['action']) && !in_array($Args['action'],$valid_actions))
            unset($Args['action']);
        if($this->m_Settings->GetMultiUser())
            $Args['login']  = $this->m_Settings->GetAccount()->GetLogin();
        else
            unset($Args['login']);

        if($this->m_Settings->GetRewriteURL())
            return $this->GetBaseURL().(isset($Args['login'])?$Args['login'].'/':'').$Args['year'].'/'.$Args['month'].'/'.$Args['day'].(isset($Args['event'])?'/'.$Args['event']:'').(isset($Args['action'])?'/'.$Args['action']:'');
        else
            return $this->GetBaseURL().'index.php?year='.$Args['year'].'&amp;month='.$Args['month'].'&amp;day='.$Args['day'].(isset($Args['event'])?'&amp;event='.$Args['event']:'').(isset($Args['action'])?'&amp;action='.$Args['action']:'').(isset($Args['login'])?'&amp;login='.$Args['login']:'');
    }

    /**
     * @todo: Fix this!
    */
    public function GetBaseURL()
    {
        if($this->m_Settings->GetRewriteURL() && isset($_SERVER['REDIRECT_URI']))
            return 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['REDIRECT_URI']).'/';
        else
            return 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER["SCRIPT_NAME"]).'/';

    }

    /**
     * Converts an Event into an template array
    */
    public function EventToTemplateArray(Event &$E, &$array)
    {
        $array['EVENT_ID']          = $E->GetID();
        $array['EVENT_NAME']        = $E->GetName();
        $array['EVENT_LOCATION']    = $E->GetLocation();
        $array['EVENT_TEXT']        = $E->GetText();
        $array['EVENT_TIME']        = $E->GetTime();
        $array['EVENT_STARTDATE']   = $E->GetStartDate();
        $array['EVENT_ENDDATE']     = $E->GetEndDate();
        $array['EVENT_RECURRENCE_NONE'] = '';
        $array['EVENT_RECURRENCE_DAY']  = '';
        $array['EVENT_RECURRENCE_WEEK'] = '';
        $array['EVENT_RECURRENCE_MONTH']= '';
        $array['EVENT_RECURRENCE_YEAR'] = '';
        $array['EVENT_REPEAT_FOREVER']  = '';
        $array['EVENT_REPEAT_UNTIL']    = '';
        $array['EVENT_REPEAT_TIMES']    = '';
        $array['EVENT_URL']         = $this->CreateURL( array(  'event'=>$E->GetID(),
                                                                'day'=>'[_c_DAY_]',
                                                                'month' => '[_c_MONTH_]',
                                                                'year'=> '[_c_YEAR_]'));
        $array['EVENT_URL_ICAL']    = $this->CreateURL( array( 'action' => 'ical',
                                                                'event' => $E->GetID()));
    }

    /**
     * Converts a comment into an template array
    */
    public function CommentToTemplateArray(Comment &$C, &$array)
    {
        $array['COMMENT_ID']    = $C->GetID();
        $array['COMMENT_NAME']  = $C->GetName();
        $array['COMMENT_EMAIL'] = $C->GetEMail();
        $array['COMMENT_DATE']  = $C->GetDate();
        $array['COMMENT_TIME']  = $C->GetTime();
        $array['COMMENT_TEXT']  = $C->GetText();
        $array['COMMENT_EVENT_ID'] = $C->GetEventID();
    }
}
?>
