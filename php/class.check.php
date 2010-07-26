<?
/* evenc - a simple event calendar
 * Copyright (c) 2010 Andreas Volk
 * Licensed under the MIT License
 */

/**
 Checks if a variable is valid.
*/
class Check
{
  /**
    Checks the id for correct format - Numeric
    @return bool true if the id is numeric, otherwise false
  */
    static function ID(&$ID)
    {
        return is_numeric($ID) && settype($ID, "integer");
    }

  /**
    Checks a name for correct format - Min:3 Max:60
    THIS DOES NOT MEAN THAT THE NAME IS VALIDE!!!
    @return bool False if wrong format.
  */
    static function Name(&$Name)
    {
        return preg_match('#^.{3,60}$#', $Name);
    }

    /**
     * Checks a login string for the correct -format: Min:3 Max: 60
     * Only: [a-z][0-9]_
    */
    static function Login(&$Login)
    {
        return preg_match('#^[a-z0-9_]{3,60}$#', $Login);
    }

    /**
     * Checks a password hash for the correct -format: md5(): 32chars
     * Only: [a-z][0-9]
    */
    static function PasswordHash(&$PasswordHash)
    {
        return preg_match('#^[a-z0-9]{32}$#', $PasswordHash);
    }

  /**
    Checks an eMail for correct format - user@subhost.host.tld - Min:0 Max:100
    @return bool False if wrong format.
  */
    static function EMail(&$EMail)
    {
        return preg_match("#^([a-z0-9_.]*)@([a-z0-9_]*)(.[a-z0-9_]*)*$#i", $EMail);
    }

  /**
    Checks the Day for correct format - Numeric, [1,31]
    @return bool true if the format is correct, otherwise false
  */
    static function Day(&$Day)
    {
        return (is_numeric($Day) && ($Day > 0) && ($Day < 32));
    }

  /**
    Checks the Month for correct format - Numeric, [1,12]
    @return bool true if the format is correct, otherwise false
  */
    static function Month(&$Month)
    {
        return (is_numeric($Month) && ($Month > 0) && ($Month < 13));
    }

  /**
    Checks the Year for correct format - Numeric, [1970,2100]
    @return bool true if the format is correct, otherwise false
  */
    static function Year(&$Year)
    {
        return (is_numeric($Year) && (($Year > 1969) && ($Year < 2101)) );
    }

  /**
    Checks the date for correct format - YYYY-MM-DD
    @return bool true if the format is correct, otherwise false
  */
    static function Date(&$Date)
    {
        return(preg_match("#^([1-2]{1}[09]{1}[0-9]{2})-([0-1]{1}[0-9]{1})-([0-3]{1}[0-9]{1})$#", $Date));
    }

  /**
    Checks the time for correct format - HH:MM
    @return bool true if the format is correct, otherwise false
  */
    static function Time(&$Time)
    {
        return (preg_match("#^([0-2]{1}[0-9]{1}):([0-6]{1}[0-9]{1})$#", $Time));
    }

  /**
    Checks the language for a valid one.
    @return bool true if an language-file was found, otherwise false
  */
    static function Language(&$Lang)
    {
        $file = "./lang/".$Lang.".lng";
        return is_file($file);
    }

    /**
    * Checks for a valid template
    * @return bool true if it is a valid template, otherwise false
    */
    static function Template(&$Tmpl)
    {
        $len = strlen($Tmpl);
        if($len < 1 || $len > 32)
            return false;
        $file = "./html/".$Tmpl."/index.html";
        return is_file($file);
    }

    /**
     * Checks for a valid session
     * @return bool true or false
    */
    static function Session(Session &$Session)
    {
        return $Session->IsValid();
    }

  /**
    Checks the EventLocation for correct format - Min:1 Max:21
    THIS DOES NOT MEAN THAT THE LOCATION IS VALIDE!!!
    @return bool False if wrong format.
  */
    static function EventLocation(&$Location)
    {
        return preg_match('#^()|(\S.{0,20})$#u',$Location);
    }

  /**
    Checks the EventName for correct format - Min:1 Max:25
    THIS MEANS NOT THAT THE NAME IS VALIDE!!!
    @return bool False if wrong format.
  */
    static function EventName(&$Name)
    {
        return preg_match('#^\S.{0,24}#u',$Name);
    }

  /**
    Checks the EventText for correct format - Min:0 Max:250
    THIS MEANS NOT THAT THE TEXT IS VALIDE!!!
    @return bool False if wrong format.
  */
    static function EventText(&$Text)
    {
        return preg_match('#^\S.{0,249}#u', $Text);
    }



  /**
    Checks
    @return bool true if the format is correct, otherwise false
  */
    static function EventRepeat(&$Repeat)
    {
        if($Repeat == Event::R_NONE ||
            $Repeat == Event::R_YEAR ||
            $Repeat == Event::R_MONTH ||
            $Repeat == Event::R_DAY ||
            $Repeat == Event::R_WEEK)
            return true;
        else
            return false;
    }


  /**
    Checks the CommentName for correct format - Min:0 Max:500
    THIS DOES NOT MEANS THAT THE NAME IS VALIDE!!!
    @return bool False if wrong format.
  */
    static function CommentText(&$Text)
    {
        return preg_match('#^\S.{0,499}#u', $Text);
    }

}
/**
 Tries to validate a text.
*/
class Validate
{
    static function Text(&$Text)
    {
        $Text = Validate::TextEx($Text);
    }

    /**
    * Returns the validated text
    */
    static function TextEx(&$Text)
    {
        $retval = htmlentities($Text, ENT_COMPAT,"utf-8");
        $retval = str_replace('[','&#91',$retval);
        $retval = str_replace(']','&#93',$retval);
        return $retval;
    }
};
?>
