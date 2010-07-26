<?
/* evenc - a simple event calendar
 * Copyright (c) 2010 Andreas Volk
 * Licensed under the MIT License
 */

/**
 * Keeps track of the users data
*/
class Session
{
    /**
     * Timeout after that a session will be automatically closed
    */
    const TIMEOUT = 900; //15 minutes

    /**
     * Constructor
    */
    function __construct()
    {
        ini_set("session.use_cookies", "0");
        session_name('easid');
        if(isset($_GET["easid"]))
            session_id($_GET["easid"]);
        session_start();
        //extend Session
        if($this->IsValid())
        {
            $_SESSION['timer'] = time()+Session::TIMEOUT;
        }
    }

    /**
     * Creates a new session for an account
     * @param Account Create session for this account
    */
    public function Login(Account &$Account)
    {
        session_regenerate_id(true);
        $_SESSION['account'] = $Account;
        $_SESSION['timer'] = time()+Session::TIMEOUT;
        $_SESSION['browser'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Destroy this session
    */
    public function Logout()
    {
        foreach($_SESSION AS $key => $val)
            unset($_SESSION[$key]);
        session_destroy();
        session_regenerate_id(true);
    }

    /**
     * Check, if the current session is still valid
     * @return bool true if we have a valid session, false if not
    */
    public function IsValid()
    {
        return (
                isset($_SESSION['account']) &&
                is_a($_SESSION['account'], "Account") &&
                !$this->TimedOut() &&
                isset($_SESSION['browser']) &&
                $_SERVER['HTTP_USER_AGENT'] == $_SESSION['browser'] &&
                isset($_SESSION['ip']) &&
                $_SERVER['REMOTE_ADDR'] == $_SESSION['ip']
                );
    }

    /**
     * Checks if an invalid session has timed out
    */
    public function TimedOut()
    {
        if(!isset($_SESSION['timer']))
            return false;
        return $_SESSION['timer'] < time();
    }

    /**
     * Returns the current 'connected' account
     * @return Valid Account or NULL
    */
    public function GetAccount()
    {
        if($this->IsValid())
            return $_SESSION['account'];
        return NULL;
    }

    /**
     * Returns a valid Session-String to append on any url
    */
    public function GetSessionURL()
    {
        if($this->IsValid())
            return '&amp;'.session_name().'='.session_id();
            return "";
    }
}

?>
