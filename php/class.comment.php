<?
/* evenc - a simple event calendar
 * Copyright (c) 2010 Andreas Volk
 * Licensed under the MIT License
 */

/**
 * A comment given by a user.
*/
class Comment
{
  /**
    Internal data
  */
    private $m_Data;

  /**
    Constructor
  */
    public function __construct()
    {
        $this->Clear();
    }

  /**
    Clears the comment - sets all vars to their defaults
  */
    public function Clear()
    {
        $this->m_Data = array();
        $this->m_Data["ID"] = 0;
        $this->m_Data["EventID"] = 0;
        $this->m_Data["Name"] = "";
        $this->m_Data["EMail"] = "";
        $this->m_Data["Date"] = "0000-00-00";
        $this->m_Data["Time"] = "00:00";
        $this->m_Data["Text"] = "";
        $this->m_Data["Log"] = "";
    }

  /**
    Sets the $VAR; will be checked.
    @throws FormatException
  */
    public function SetID($ID)
    {
        if(!Check::ID($ID))
            throw new FormatException("ID");
        $this->m_Data["ID"] = $ID;
    }
    /**
     * @throws FormatException
    */
    public function SetEventID($ID)
    {
        if(!Check::ID($ID))
            throw new FormatException("EventID");
        $this->m_Data["EventID"] = $ID;
    }

    /**
     * @throws FormatException
    */
    public function SetDate($Date)
    {
        if(!Check::Date($Date))
            throw new FormatException("Date");
        $this->m_Data["Date"] = $Date;
    }

    /**
     * @throws FormatException
    */
    public function SetTime($Time)
    {
        $Time = substr($Time,0,5);
        if(!Check::Time($Time))
            throw new FormatException("Time");
        $this->m_Data["Time"] = $Time;
    }

    /**
     * @throws FormatException
    */
    public function SetName(&$Name)
    {
        $Name = trim($Name);
        if(!Check::Name($Name))
            throw new FormatException("[_l_COMMENT_NAME_]");
        $this->m_Data["Name"] = $Name;
    }

    /**
     * @throws FormatException
    */
    public function SetEMail(&$EMail)
    {
        $EMail = trim($EMail);
        if(!Check::EMail($EMail))
            throw new FormatException("[_l_COMMENT_EMAIL_]");
        $this->m_Data["EMail"] = $EMail;
    }
    /**
     * @throws FormatException
    */
    public function SetText(&$Text)
    {
        $Text = trim($Text);
        if(!Check::CommentText($Text))
            throw new FormatException("[_l_COMMENT_TEXT_]");
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

    public function GetEventID()
    {
        return Validate::TextEx($this->m_Data["EventID"]);
    }

    public function GetDate()
    {
        return Validate::TextEx($this->m_Data["Date"]);
    }

    public function GetTime()
    {
        return Validate::TextEx($this->m_Data["Time"]);
    }

    public function GetName()
    {
        return Validate::TextEx($this->m_Data["Name"]);
    }

    public function GetEMail()
    {
        return Validate::TextEx($this->m_Data["EMail"]);
    }

    public function GetText()
    {
        return Validate::TextEx($this->m_Data["Text"]);
    }

    public function GetLog()
    {
        return Validate::TextEx($this->m_Data["Log"]);
    }

    public function GetRawID()      {return $this->m_Data['ID'];}
    public function GetRawEventID() {return $this->m_Data['EventID'];}
    public function GetRawDate()    {return $this->m_Data['Date'];}
    public function GetRawTime()    {return $this->m_Data['Time'];}
    public function GetRawName()    {return $this->m_Data['Name'];}
    public function GetRawEMail()   {return $this->m_Data['EMail'];}
    public function GetRawText()    {return $this->m_Data['Text'];}
    public function GetRawLog()     {return $this->m_Data['Log'];}

};
?>
