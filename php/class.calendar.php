<?
/* evenc - a simple event calendar
 * Copyright (c) 2010 Andreas Volk
 * Licensed under the MIT License
 */

/**
 A simple calendar class. Stores the date for the user.
 Singleton!
 */
class Calendar
{
    const FOREVER = '2999-12-31';

    //Singleton instance
    private static $inst = null;

    /**
     * Creates a new instance.
     */
    public static function getInstance()
    {
        if(is_null(self::$inst))
            self::$inst = new Calendar();
        return self::$inst;
    }

    /**
     Internal Data
     */
    private $m_Data;

    /**
     * Creates a timestamp representing the internal date/time-values
     */
    private function createTimestamp()
    {
        return mktime(  $this->m_Data['Hours'],
        $this->m_Data['Minutes'],
        0, //Seconds
        $this->m_Data['Month'],
        $this->m_Data['Day'],
        $this->m_Data['Year']);
    }

    /**
     Constructor
     */
    private function __construct()
    {
        $this->SetToday();
    }

    /**
     Sets the date to today.
     */
    public function SetToday()
    {
        $this->SetDate(Calendar::GetDateNow());
        $this->SetTime(Calendar::GetTimeNow());
    }

    /**
     Sets the current day
     @return bool Success
     */
    public function SetDay($Day)
    {
        if(Check::Day($Day))
        {
            $this->m_Data["Day"]  = $Day;
            if(strlen($this->m_Data["Day"])< 2)
            $this->m_Data["Day"] = "0".$this->m_Data["Day"];
            return true;
        }
        return false;
    }

    /**
     * Sets the current month
     */
    public function SetMonth($Month)
    {
        if(Check::Month($Month))
        {
            $this->m_Data["Month"]  = $Month;
            if(strlen($this->m_Data["Month"])<2)
            $this->m_Data["Month"]  = "0".$this->m_Data["Month"];
            return true;
        }
        return false;
    }

    /**
     * Sets the current year
     */
    public function SetYear($Year)
    {
        if(Check::Year($Year))
        {
            $this->m_Data["Year"]  = $Year;
            return true;
        }
        return false;
    }

    /**
     Sets a full date.
     @param Date Format 2006-12-31
     */
    public function SetDate($Date)
    {
        if(Check::Date($Date))
        {
            $this->m_Data["Year"] = substr($Date,0,4);
            $this->m_Data["Month"] = substr($Date,5,2);
            $this->m_Data["Day"] = substr($Date,8,2);


            if($this->m_Data["Year"] == 0 )
            $this->m_Data["Year"] = $this->GetYearToday();
            if($this->m_Data["Month"] == 0)
            $this->m_Data["Month"] = $this->GetMonthToday();
            if($this->m_Data["Day"] == 0)
            $this->m_Data["Day"] = $this->GetDayToday();

            return true;
        }
        return false;
    }

    /**
     * Parses an iCal-DateTime. Format: YYYYMMDDTHHMM00
     */
    public function SetDateiTimeCal($Date)
    {
        $res = array();
        if(preg_match("@([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2})([0-9]{2})00Z?@",$Date,$res))
        {
            return $this->setDate($res[1].'-'.$res[2].'-'.$res[3]) && $this->setTime($res[4].':'.$res[5]);
        }
        return false;
    }

    /**
     * Parses an iCal-DateTime. Format: YYYYMMDD
     */
    public function SetDateiCal($Date)
    {
        $res = array();
        if(preg_match("@([0-9]{4})([0-9]{2})([0-9]{2})@",$Date,$res))
        {
            return $this->setDate($res[1].'-'.$res[2].'-'.$res[3]);
        }
        return false;
    }

    /**
     * Sets the current time
     * @param Time Format 12:36
     */
    public function SetTime($Time)
    {
        if(Check::Time($Time))
        {
            $tmpval = explode(':', $Time);
            $this->m_Data['Hours'] = $tmpval[0];
            $this->m_Data['Minutes'] = $tmpval[1];
            return true;
        }
        return false;
    }

    /**
     * Returns 'MO','TU', 'WE', ...
     */
    public function GetShortDayUni()
    {
        return strtoupper(substr(date("D", $this->createTimestamp()),0,2));
    }

    /**
     Return the $VAR
     @return  $var
     */
    public function GetDay($AsString=false)
    {
        if(($AsString))
        return strftime("%A",$this->createTimestamp());
        else
        return $this->m_Data["Day"];
    }

    public function GetMonth($AsString=false)
    {
        if($AsString)
        return strftime("%B", $this->createTimestamp());
        else
        return $this->m_Data["Month"];
    }

    public function GetYear()
    {
        return $this->m_Data["Year"];
    }

    /**
     Returns a full date - YYYY-MM-DD
     */
    public function GetDate()
    {
        return $this->m_Data["Year"]."-".$this->m_Data["Month"]."-".$this->m_Data["Day"];
    }

    public function GetTime()
    {
        return $this->m_Data["Hours"].":".$this->m_Data["Minutes"];
    }

    /**
     * Returns a RFC-2822 formated date
     * Thu, 21 Dec 2000 16:01:07 +0200
     */
    public function GetDateRFC()
    {
        return date("r",$this->createTimestamp());
    }

    /**
     * Returns a ISO-8601 formated date
     * 2004-02-12T15:19:21+00:00
     */
    public function GetDateISO()
    {
        return date("c",$this->createTimestamp());
    }

    /**
     * Returns the date so it can be used in a ical-file
     * YYYYMMDDTHHMM00
     */
    public function GetDateiCal()
    {
        return strftime('%Y%m%dT%H%M00', $this->createTimestamp());
    }

    /**
     preferred date representation for the current locale
     */
    public function GetDateLocale()
    {
        return strftime("%x",$this->createTimestamp());
    }

    /**
     Returns the first Weekday of the currently selected month.
     01 -> Monday, 07->Sunday.
     */
    public function GetFirstWeekdayOfMonth()
    {
        return strftime("%u",mktime(0,0,0,$this->m_Data["Month"],"1",$this->m_Data["Year"]) );
    }

    /**
     Returns the number of days for the current month.
     */
    public function GetDaysOfMonth()
    {
        return date("t",$this->createTimestamp());
    }


    /*
     * Helper functions
     */


    /**
     Returns the current day
     */
    public static function GetDayToday()
    {
        return strftime("%d");
    }

    /**
     Returns the current month
     */
    public static function GetMonthToday()
    {
        return strftime("%m");
    }
    /**
     Returns the current year
     */
    public static function GetYearToday()
    {
        return strftime("%Y");
    }

    /**
     Returns the current date
     */
    public static function GetDateNow()
    {
        return strftime("%Y-%m-%d");
    }

    /**
     Returns the current time
     */
    public static function GetTimeNow()
    {
        return strftime("%H:%M");
    }

    /**
     Computes a EndDate for a given startdate and reccurence
     */
    public static function ComputeEndDate($StartDate, $Times, $Recurrence)
    {
        if(!Check::Date($StartDate))
        throw new FormatException("[_l_EVENT_DATE_]");
        if(!Check::EventRepeat($Recurrence))
        throw new FormatException("Repeat");
        if(!is_numeric($Times) || $Times < 2)
        throw new FormatException("Times");

        //Times must be increased by 1, because the Startdate is included
        $Times--;

        // Prepare date
        $date = split("-",$StartDate);
        $date['day'] = $date[2];
        $date['month'] = $date[1];
        $date['year'] = $date[0];

        switch($Recurrence)
        {
            case Event::R_DAY:
            {
                return strftime("%Y-%m-%d",mktime(0,0,0,$date['month'],$date['day'],$date['year'])+($Times*(60*60*24)));
            }
            case Event::R_WEEK:
            {
                return strftime("%Y-%m-%d",mktime(0,0,0,$date['month'],$date['day'],$date['year'])+($Times*(60*60*24*7)));
            }
            case Event::R_MONTH:
            {
                if(($date['month']+$Times) > 12)
                {
                    $date['year'] += (($date['month']+$Times)-(($date['month']+$Times)%12))/12;
                    $date['month'] = ($date['month']+$Times)%12;
                }
                else
                {
                    $date['month'] = $date['month']+$Times;
                }
                return strftime("%Y-%m-%d",mktime(0,0,0,$date['month'],$date['day'],$date['year']));
            }
            case Event::R_YEAR:
            {
                return strftime("%Y-%m-%d",mktime(0,0,0,$date['month'],$date['day'],$date['year']+$Times));
            }
            default:
            {
                return strftime("%Y-%m-%d",mktime(0,0,0,$date['month'],$date['day'],$date['year']));
            }
        }
    }

}
?>
