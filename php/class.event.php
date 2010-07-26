<?
/* evenc - a simple event calendar
 * Copyright (c) 2010 Andreas Volk
 * Licensed under the MIT License
 */
require_once('class.calendar.php');
require_once('class.check.php');

/**
    Basic event class.
*/
class Event
{
  /**
    Internal Data
  */
    private $m_Data;

    /// Single
    const R_NONE    = 'NONE';
    /// Repeat daily
    const R_DAY     = 'DAY';
    /// Repeat weekly
    const R_WEEK    = 'WEEK';
    /// Repeat monthly
    const R_MONTH   = 'MONTH';
    /// Repeat every year
    const R_YEAR    = 'YEAR';
    const R_DECADE  = 'DECADE';

  /**
    Constructor
  */
    public function __construct()
    {
        $this->Clear();
    }

  /**
    Sets all Vars to their defaults.
  */
    public function Clear()
    {
        $this->m_Data = array();
        $this->m_Data["ID"]         = 0;
        $this->m_Data["StartDate"]  = Calendar::GetDateNow();
        $this->m_Data["EndDate"]    = Calendar::FOREVER;
        $this->m_Data["Time"]       = Calendar::GetTimeNow();
        $this->m_Data["Location"]   = '';
        $this->m_Data["Name"]       = '';
        $this->m_Data["Text"]       = '';
        $this->m_Data["Repeat"]    = Event::R_NONE;
    }

  /**
    Sets the ID; will be checked.
    @throws FormatException
  */
    public function SetID($ID)
    {
        if(!Check::ID($ID))
            throw new FormatException("ID");
        $this->m_Data["ID"] = $ID;
    }


  /**
    @param Date Format 2006-12-31
    @throws FormatException
  */
    public function SetStartDate($Date)
    {
        if(!Check::Date($Date))
            throw new FormatException("[_l_EVENT_DATE_]");
        $this->m_Data["StartDate"] = $Date;
    }

  /**
    @param Date Format 2006-12-31
    @throws FormatException
  */
    public function SetEndDate($Date)
    {
        if(!Check::Date($Date))
            throw new FormatException("[_l_EVENT_DATE_]");
        $this->m_Data["EndDate"] = $Date;
    }

    /**
     * @throws FormatException
    */
    public function SetRepeat($Repeat)
    {
        if(!Check::EventRepeat($Repeat))
            throw new FormatException("Repeat");
        $this->m_Data["Repeat"] = $Repeat;
    }

  /**
    @param Time Format 21:45
    @throws FormatException
  */
    public function SetTime($Time)
    {
        if(!Check::Time($Time))
            throw new FormatException("[_l_EVENT_TIME_]");
        $this->m_Data["Time"] = $Time;
    }

    /**
     * @throws FormatException
    */
    public function SetLocation($Location)
    {
        $Location = trim($Location);
        if(!Check::EventLocation($Location))
            throw new FormatException("[_l_EVENT_LOCATION_]");
        $this->m_Data["Location"] = $Location;
    }

    /**
     * @throws FormatException
    */
    public function SetName($Name)
    {
        $Name = trim($Name);
        if(!Check::EventName($Name))
            throw new FormatException("[_l_EVENT_NAME_]");
        $this->m_Data["Name"] = $Name;
    }

    /**
     * @throws FormatException
    */
    public function SetText($Text)
    {
        $Text = trim($Text);
        if(!Check::EventText($Text))
            throw new FormatException("[_l_EVENT_TEXT_]");
        $this->m_Data["Text"] = $Text;
    }


    public function SetLog(&$Log)
    {
        Validate::Text($Log);
        $this->m_Data["Log"] = $Log;
    }

  /**
    @return $VAR
  */
    public function GetID()
    {
        return Validate::TextEx($this->m_Data["ID"]);
    }

    public function GetStartDate()
    {
        return Validate::TextEx($this->m_Data["StartDate"]);
    }

    public function GetEndDate()
    {
        return Validate::TextEx($this->m_Data["EndDate"]);
    }

    public function GetTime()
    {
        return Validate::TextEx($this->m_Data["Time"]);
    }

    public function GetLocation()
    {
        return Validate::TextEx($this->m_Data["Location"]);
    }

    public function GetName()
    {
        return Validate::TextEx($this->m_Data["Name"]);
    }

    public function GetRepeat()
    {
        return Validate::TextEx($this->m_Data["Repeat"]);
    }

    public function GetText()
    {
        return Validate::TextEx($this->m_Data["Text"]);
    }



    public function GetLog()
    {
        return Validate::TextEx($this->m_Data["Log"]);
    }

    //raw vars block ... yes, it's a hack .. :(
    public function GetRawID()          {return $this->m_Data['ID'];}
    public function GetRawStartDate()   {return $this->m_Data['StartDate'];}
    public function GetRawEndDate()     {return $this->m_Data['EndDate'];}
    public function GetRawTime()        {return $this->m_Data['Time'];}
    public function GetRawLocation()    {return $this->m_Data['Location'];}
    public function GetRawName()        {return $this->m_Data['Name'];}
    public function GetRawRepeat()      {return $this->m_Data['Repeat'];}
    public function GetRawText()        {return $this->m_Data['Text'];}
    public function GetRawType()        {return $this->m_Data['Type'];}
    public function GetRawLog()         {return $this->m_Data['Log'];}

  /**
    Prints the Event
  */
    public function Out()
    {
      krumo($this->m_Data);
    }

};

?>
