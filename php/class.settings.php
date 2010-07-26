<?
/* evenc - a simple event calendar
 * Copyright (c) 2010 Andreas Volk
 * Licensed under the MIT License
 */

/*
    Stores the internal settings.
*/
class Settings
{
  /**
    Internal Data
  */
    private $m_Data = array();
    private $m_account = null;

    const LANG_DE = 'de';
    const LANG_EN = 'en';
    const LANG_DUMMY = 'dummy';

    const BBCODE            = 'BBCode';
    const TEMPLATE          = 'Template';
    const PUBLICPOSTING     = 'PublicPosting';
    const COMMENTS          = 'Comments';
    const LANGUAGE          = 'Language';
    const DEBUG             = 'Debug';
    const REWRITEURL        = 'RewriteURL';
    ///Don't save this!
    const MULTIUSER         = 'MultiUser';

  /**
    Constructor
  */
    public function __construct(Account $A)
    {
        $this->m_account = $A;
        $this->SetDefaults();
    }

  /**
    Sets the settings to their defaults.
  */
    public function SetDefaults()
    {
        $this->m_Data[self::TEMPLATE]       = "blueweb";
        $this->m_Data[self::BBCODE]         = false;
        $this->m_Data[self::LANGUAGE]       = "de";
        $this->m_Data[self::DEBUG]          = false;
        $this->m_Data[self::REWRITEURL]     = false;
        $this->m_Data[self::MULTIUSER]      = false;
        //Allow everyone to add events
        $this->m_Data[self::PUBLICPOSTING]  = true;
        //Allow comments on events
        $this->m_Data[self::COMMENTS]       = true;
    }

  /**
   * Sets the template
   * User setting
    @param Name Name of the template
  */
    public function SetTemplate($Name)
    {
        if(!Check::Template($Name))
            throw new TemplateException($Name);
        $this->m_Data[self::TEMPLATE] = $Name;
    }

    public function GetTemplate()
    {
        return $this->m_Data[self::TEMPLATE];
    }

  /**
   * Sets whether the use of BBCode is allowed or not.
   * User setting
   * @param Value true or false
  */
    public function SetBBCode($Value = true)
    {
        $this->setBoolean(self::BBCODE, $Value);
    }

    public function GetBBCode()
    {
        return $this->m_Data[self::BBCODE];
    }

    /**
     * Sets the the language
     * @param value Language to use
    */
    public function SetLang($value)
    {
        if(!Check::Language($value))
            throw new LanguageException($value);

        switch($value)
        {
            case self::LANG_DE:
            {
                if(!setlocale(LC_TIME, 'de_DE.utf8', 'de', 'de_DE', 'de_DE@euro', 'ge' , 'german', 'deutsch'))
                    throw new LanguageException($value);
                break;
            }
            case self::LANG_EN:
            {
                if(!setlocale(LC_TIME, 'en_US.utf8', 'en', 'en_US', 'en_US.iso88591'))
                    throw new LanguageException($value);
                break;
            }
            case self::LANG_DUMMY:
            {
                break;
            }
            default:
            {
                if(!setlocale(LC_TIME, $value))
                    throw new LanguageException($value);
            }
        }
        $this->m_Data[self::LANGUAGE] = $value;
    }

    /**
    Returns the language
    @return language
    */
    public function GetLang()
    {
        return $this->m_Data[self::LANGUAGE];
    }


    /**
     * Debug output
     * User setting
    */
    public function SetDebug($boolean = true)
    {
        $this->setBoolean(self::DEBUG, $boolean);
    }

    /**
     * User setting
    */
    public function GetDebug()
    {
        return $this->m_Data[self::DEBUG];
    }

    /**
     * User setting
    */
    public function SetRewriteURL($boolean = true)
    {
        $this->setBoolean(self::REWRITEURL, $boolean);
    }

    /**
     * User setting
    */
    public function GetRewriteURL()
    {
        return $this->m_Data[self::REWRITEURL];
    }

    public function GetAccount()
    {
        return $this->m_account;
    }

    /**
     * Installation setting
    */
    public function SetMultiUser($boolean = true)
    {
        $this->setBoolean(self::MULTIUSER, $boolean);
    }

    /**
     * Installation setting
    */
    public function GetMultiUser()
    {
        return $this->m_Data[self::MULTIUSER];
    }

    /**
     * Enable or disable public posting
    */
    public function SetPublicPosting($boolean = true)
    {
        $this->setBoolean(self::PUBLICPOSTING, $boolean);
    }

    /**
     * Retuns the public posting statiu
    */
    public function GetPublicPosting()
    {
        return $this->m_Data[self::PUBLICPOSTING];
    }

    /**
     * Enable or disable comments on events
    */
    public function SetComments($boolean = true)
    {
        $this->setBoolean(self::COMMENTS, $boolean);
    }

    /**
     * Returns if comments are enabled or disabled
    */
    public function GetComments()
    {
        return $this->m_Data[self::COMMENTS];
    }


    /**
     * Sets an boolean value
    */
    private function setBoolean($name, $boolean)
    {
        if(settype($boolean, 'boolean') && $boolean)
            $this->m_Data[$name] = true;
        else
            $this->m_Data[$name] = false;
    }
}

?>
