<?
/* evenc - a simple event calendar
 * Copyright (c) 2010 Andreas Volk
 * Licensed under the MIT License
 */

require_once('class.event.php');

/**
 * iCalendar File
*/
class iCalendar extends ArrayObject
{
    const CRLF = "\r\n";
    //private $events;
    private $cal;

    //Parser: iCalender-Line
    const PARAM_NAME = 0;
    const PARAM_VALUE = 1;

    //Parser status
    const NONE      = 0;
    const VCALENDAR = 1;
    const VEVENT    = 2;

    //                          iCal        -> evenc
    static $freq_table = array('SECONDLY'   => Event::R_DAY,
                                'MINUTELY'  => Event::R_DAY,
                                'DAILY'     => Event::R_DAY,
                                'WEEKLY'    => Event::R_WEEK,
                                'MONTHLY'   => Event::R_MONTH,
                                'YEARLY'    => Event::R_YEAR
                               );


    /**
     * Creates a new iCalendar-File
    */
    public function __construct()
    {
        //$this->events = array();
        $this->cal = Calendar::getInstance();
    }

    /**
     * Adds an event to this iCal-file
    */
    public function addEvent(Event &$E)
    {
        $this->append($E);
    }

    /**
     * Sets all events for this iCalendar-File
     * @param events Event Array
    */
    public function setEvents($events)
    {
        $this->exchangeArray($events);
    }

    /**
     * Returns all events from this iCalendar-File
     * @return Array
    */
    public function getEvents()
    {
        $retval = array();
        foreach($this AS $val)
        {
            $retval[] = $val->toEvent();
        }
        return $retval;
    }

    /**
     * Returns the iCalendar-File as a string
    */
    public function prints()
    {
        $retval = "BEGIN:VCALENDAR".self::CRLF;
        $retval .= "PRODID:evenc".self::CRLF;
        $retval .= 'VERSION:'.evenc::Version.self::CRLF;
        foreach($this->events AS $key => $val)
        {
            $retval .= $this->createEventString($val);
        }
        $retval .= "END:VCALENDAR".self::CRLF;
        return $retval;
    }

    /**
     * Converts an Event into an iCalendar-VEVENT-String
     * @param E Event
     * @return iCalendar-VEVENT
    */
    private function createEventString(Event &$E)
    {

        $retval = "BEGIN:VEVENT\r\n";
        // Break long lines. See RFC 2445 4.1 Content Lines.
        $retval .= wordwrap("UID:".$E->getID(), 75, self::CRLF.' ').self::CRLF;
        $retval .= wordwrap("SUMMARY:".$E->getRAWName(), 75, self::CRLF.' ').self::CRLF;
        $retval .= wordwrap("LOCATION:".$E->getRAWLocation(), 75, self::CRLF.' ').self::CRLF;
        $retval .= wordwrap("DESCRIPTION:".$E->getRAWText(), 75, self::CRLF.' ').self::CRLF;
        $this->cal->SetDate($E->GetStartDate());
        $this->cal->SetTime($E->GetTime());
        $retval .= "DTSTART:".$this->cal->GetDateiCal().self::CRLF;
        if($E->GetRepeat() != Event::R_NONE)
        {
            $repeat = '';
            switch($E->GetRepeat())
            {
                case Event::R_DAY:
                {
                    $repeat = 'FREQ=DAILY';
                    break;
                }
                case Event::R_WEEK:
                {
                    $repeat = 'FREQ=WEEKLY';
                    break;
                }
                case Event::R_MONTH:
                {
                    $repeat = 'FREQ=MONTHLY';
                    break;
                }
                case Event::R_YEAR:
                {
                    $repeat = 'FREQ=YEARLY';
                    break;
                }
            }

            $this->cal->SetDate($E->GetEndDate());
            $retval .= "RRULE:".$repeat.";UNTIL=".$this->cal->GetDateiCal().self::CRLF;
        }
        $retval .= "DURATION:PT2H".self::CRLF;
        $retval .="END:VEVENT".self::CRLF;
        return $retval;
    }

    /**
     * @throws FileException
     * @throws FormatException
    */
    public function parseFile($filename)
    {
        //Open file
        $fhandle = @fopen($filename,"r");
        if(!$fhandle)
            throw new FileException($filename);

        //Read file
        $buffer = "";
        while(!feof($fhandle))
        {
            $buffer .= fgets($fhandle, 1024);
        }
        fclose($fhandle);
        //Parse content
        return $this->parse($buffer);
    }


    /**
     * Parses an iCalendar-String extracts all events
     * @param content String containing an iCalendar
     * @return none
     * @throws ParseException
     * @throws FormatException
    */
    public function parse(&$content)
    {
        //Split into array
        $iCal = iCalendar::splitFile($content);
        iCalendar::unfolding($iCal);

        //Not an valid iCalendar file
        if(count($iCal) < 2)
        {
            return;
        }

        //Parse
        $event_start = null;
        foreach($iCal AS $key => $val)
        {
            if($val == 'BEGIN:VEVENT')
            {
                $event_start = array();
            }
            elseif($val == 'END:VEVENT')
            {

                $event_start[] =& $iCal[$key];
                $e = new iCalendarEvent();

                $e->parse($event_start);
                $this->append($e);
                $event_start = null;
                continue;
            }
            if(!is_null($event_start))
            {
                $event_start[] =& $iCal[$key];
            }
        }
    }

    /**
     * Does 'unfolding'; i.e. converts short lines to long lines as described
     * in RFC 2445 4.1
    */
    public static function unfolding(&$linearray)
    {
        $last = NULL;
        foreach($linearray AS $key => $val)
        {
            if(isset($linearray[$last]) && $val{0} == ' ' )
            {
                $linearray[$last] .= $val;
                unset($linearray[$key]);
            }
            $last = $key;
        }
    }

    /**
     * @param $content String
     * @return Array
    */
    public static function splitFile(&$content)
    {
        return preg_split("@\r?\n@",$content);
    }

    const NAME = 1;
    const PARAM = 2;
    const VALUE = 3;

    const PARAMNAME = 0;
    const PARAMVALUE = 1;

    public static function splitContentline(&$line)
    {
        //$line = "DTSTART;P1=V1;P2=V2,V22,V23:20080501T101501";
        $matches = array();


        $name = '[A-Z0-9-]+';

        $param_name = '[A-Z0-9-]+';
        //?: == don't capture
        $param_value ='(?:[^;:,"[:cntrl:]]*|"[^"[:cntrl:]]*")';
        $param = "$param_name=$param_value(?:,$param_value)*";
        $params = "(?:;$param)*";
        $value = '[[:print:]\x80-\xf8]*';

        $content_line = "($name)($params):($value)";
        if(!preg_match_all("@$content_line@iu",$line, $matches, PREG_SET_ORDER))
            return array();
        $matches = $matches[0];
        unset($matches[0]);
        //split params
        $matches[self::PARAM] = preg_split('@;@',$matches[self::PARAM], -1, PREG_SPLIT_NO_EMPTY);
        foreach($matches[self::PARAM] AS $key => &$val)
        {
            //split name=value
            $val = explode('=', $val);
            //split value1,value2,value1
            $val[self::PARAMVALUE] = explode(',', $val[self::PARAMVALUE]);

            //add with name
            $matches[self::PARAM][$val[self::PARAMNAME]] = $val[self::PARAMVALUE];
            //remove prev key
            unset($matches[self::PARAM][$key]);
            //$val = array($val[self::PARAMNAME] => $val[self::PARAMVALUE]);
        }

        return $matches;
    }
}

/**
 * A single vEvent
 */
class iCalendarEvent extends ArrayObject
{

    const BEGIN     = 'BEGIN';
    const END       = 'END';

    const ATTENDEE      = 'ATTENDEE';
    const DESCRIPTION   = 'DESCRIPTION';
    const DTSTAMP       = 'DTSTAMP';
    const DTSTART       = 'DTSTART';
    const DURATION      = 'DURATION';
    const LOCATION      = 'LOCATION';
    const RRULE         = 'RRULE';          ///Recurrence Rule
    const SUMMARY       = 'SUMMARY';
    const UID           = 'UID';

    const UNSUPPORTED   = 'UNSUPPORTED';

    //Params
    const P_VALUE       = 'VALUE';          //used byDTSTART
    const P_FREQ        = 'FREQ';           //used by RRULE
    const P_UNTIL       = 'UNTIL';          //used by RRULE
    const P_COUNT       = 'COUNT';          //used by RRULE

    //param-value
    const PV_DATE       = 'DATE';           //uses by P_VALUE

    //freq table - converts iCal freqs to event freqs
    public static $freq_table = array(  'SECONDLY'   => Event::R_DAY,
                                        'MINUTELY'  => Event::R_DAY,
                                        'DAILY'     => Event::R_DAY,
                                        'WEEKLY'    => Event::R_WEEK,
                                        'MONTHLY'   => Event::R_MONTH,
                                        'YEARLY'    => Event::R_YEAR
                                        );


    private $cal        = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cal = Calendar::getInstance();
    }

    /**
     * Parses an VEVENT string or array
     */
    public function parse(&$string)
    {
        $event = $string;
        if(!is_array($event))
        {
            $event = iCalendar::splitFile($event);
            iCalendar::unfolding($event);
        }
        reset($event);


        //Check Begin, End
        if(current($event) != self::BEGIN.':VEVENT')
            throw new ParseException(self::BEGIN.':VEVENT expected');
        if(end($event) != self::END.':VEVENT')
            throw new ParseException(self::END.':VEVENT expected');

        //Parse lines
        foreach($event AS $line)
        {
            $line = iCalendar::splitContentline($line);
            switch($line[iCalendar::NAME])
            {
                case self::BEGIN:
                case self::END:
                    continue;
                case self::ATTENDEE:
                case self::DESCRIPTION:
                case self::DTSTAMP:
                case self::DTSTART:
                case self::DURATION:
                case self::LOCATION:
                case self::RRULE:
                case self::SUMMARY:
                case self::UID:
                    $this[$line[iCalendar::NAME]] = $line;
                    break;
                default:
                {
                    if(!isset($this[self::UNSUPPORTED]))
                        $this[self::UNSUPPORTED] = array();
                    $this[self::UNSUPPORTED][$line[iCalendar::NAME]] = $line;
                }
            }

        }
    }

    /**
     * Creates an evenc Event
     * @retuen Event
     * @throws Exception
     */
    public function toEvent()
    {
        $e = new Event();
        //set simple param
        if(isset($this[self::LOCATION]))
            $e->setLocation($this[self::LOCATION][iCalendar::VALUE]);
        if(isset($this[self::SUMMARY]))
            $e->setName($this[self::SUMMARY][iCalendar::VALUE]);
        if(isset($this[self::DESCRIPTION]))
            $e->setText($this[self::DESCRIPTION][iCalendar::VALUE]);
        //set complex param
        $this->setEventDTSTART($e);
        $this->setEventRRULE($e);
        return $e;
    }

    /**
     * Convertes the DTSTART-Param to an Event-Param
     * @param $e valid Event
     * @throws Exception
     */
    private function setEventDTSTART(Event &$e)
    {
        if($dtstart = $this[self::DTSTART])
        {
            //check params
            if(array_key_exists(self::P_VALUE, $dtstart[iCalendar::PARAM]) &&
                in_array(self::PV_DATE, $dtstart[iCalendar::PARAM][self::P_VALUE]))
            {
                    //convert time
                    $this->cal->setDateiCal($dtstart[iCalendar::VALUE]);
                    $e->setStartDate($this->cal->getDate());
            }
            else
            {
                //convert time
                $this->cal->setDateiTimeCal($dtstart[iCalendar::VALUE]);
                $e->setStartDate($this->cal->getDate());
                $e->setTime($this->cal->getTime());
            }
        }
    }

    /**
     * Computes repeat and enddate
     * @param $e valid Event
     * @throws Exception
     */
    private function setEventRRULE(&$e)
    {
        if($rrule = $this[self::RRULE])
        {
            //split values
            $values = explode(';',$rrule[iCalendar::VALUE]);
            //split name = value
            foreach($values AS &$val)
            {
                $val = explode('=', $val);
                $val = array($val[0] => $val[1]);
            }
            //raise level
            foreach($values AS $key => &$val)
            {
                if(is_array($val))
                {
                    $values[key($val)] = current($val);
                    unset($values[$key]);
                }
            }

            //check frequency
            if(array_key_exists(self::P_FREQ,&$values))
            {
                //check, if we can convert this value
                if(array_key_exists($values[self::P_FREQ], &self::$freq_table))
                {
                    $e->setRepeat(self::$freq_table[$values[self::P_FREQ]]);
                }
            }
            //check enddate
            if(array_key_exists(self::P_UNTIL,&$values))
            {
                $this->cal->setDateiTimeCal($values[self::P_UNTIL]);
                $e->setEndDate($this->cal->getDate());
            }
            elseif(array_key_exists(self::P_COUNT,&$values))
            {
                throw new ImplementationException('iCalenderEvent::P_COUNT');
            }

        }
    }

}
?>
