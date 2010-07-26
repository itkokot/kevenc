<?
/* evenc - a simple event calendar
 * Copyright (c) 2010 Andreas Volk
 * Licensed under the MIT License
 */

/**
 * An users account.
 * An Account with ID = 0 is a Wildcard.
*/
class Account
{
    // Internal data
    private $m_Data;

    /**
     * Creates a password hash.
    */
    public static function createPasswordHash($Password)
    {
        return md5($Password);
    }

    /**
     * Constructor
    */
    public function __construct()
    {
        $this->Clear();
    }

    /**
     * Clears all internal data
    */
    public function Clear()
    {
        $this->m_Data = array();
        $this->m_Data['ID'] = 0;
        $this->m_Data['Login'] = 'none';
        $this->m_Data['EMail'] = 'none';
        $this->m_Data['Name'] = 'not set';
        $this->m_Data['Password'] = '';
    }

    /**
     * Setter functions
    */
    public function SetID($ID)
    {
        if(!Check::ID($ID))
            throw new FormatException('ID');
        $this->m_Data['ID'] = $ID;
    }

    public function SetLogin($Login)
    {
        if(!Check::Login($Login))
            throw new FormatException('Login');
        $this->m_Data['Login'] = $Login;
    }

    public function SetEMail($EMail)
    {
        if(!Check::EMail($EMail))
            throw new FormatException('EMail');
        $this->m_Data['EMail'] = $EMail;
    }

    public function SetName($Name)
    {
        if(!Check::Name($Name))
            throw new FormatException('Name');
        $this->m_Data['Name'] = $Name;
    }

    public function SetPassword($Password)
    {
        if(!Check::PasswordHash($Password))
            throw new FormatException('Password');
        $this->m_Data['Password'] = $Password;
    }

    /**
     * Getter functions
    */
    public function GetID()
    {
        return (int) $this->m_Data['ID'];
    }

    public function GetLogin()
    {
        return Validate::TextEx($this->m_Data['Login']);
    }

    public function GetEMail()
    {
        return Validate::TextEx($this->m_Data['EMail']);
    }

    public function GetName()
    {
        return Validate::TextEx($this->m_Data['Name']);
    }

    public function GetPassword()
    {
        return Validate::TextEx($this->m_Data['Password']);
    }

    public function GetRawID()      {return $this->GetID();}
    public function GetRawLogin()   {return $this->m_Data['Login'];}
    public function GetRawEMail()   {return $this->m_Data['EMail'];}
    public function GetRawName()    {return $this->m_Data['Name'];}
    public function GetRawPassword(){return $this->m_Data['Password'];}
}

?>
