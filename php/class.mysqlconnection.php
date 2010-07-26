<?
/* evenc - a simple event calendar
 * Copyright (c) 2010 Andreas Volk
 * Licensed under the MIT License
 */

require_once("./php/class.dbconnection.php");
/**
 *   Connection to a MySQL-Database
 * @todo account-login unique
*/
class MySQLConnection extends DBConnection
{

    private static $instance = null;

    public static function GetInst()
    {
        if(is_null(self::$instance))
        {
            self::$instance =& new MySQLConnection();
        }
        return self::$instance;
    }


  /**
    Internal link to db.
  */
    private $m_DB;
  /**
    Table prefix
  */
    private $m_TablePrefix;

    /**
     * Account, the user is logged in. An ID-0-Account is a wildcard to all data.
    */
    private $m_Acc;

    /**
     * Constructor
    */
    private function __construct()
    {
        $this->m_DB = null;
        $this->m_TablePrefix = "";
        $this->m_Acc = new Account(); // Caution: Wildcard!
    }

  /**
    Establish connection to database.
    @return bool Success
  */
    public function Connect($Host, $User, $Password, $Database)
    {
        $this->m_DB = mysql_connect($Host,$User,$Password);
        //Set connection to utf
        mysql_query('SET CHARACTER SET "utf8"');
        mysql_query('SET NAMES "utf8"');
        if(!mysql_select_db($Database))
            return false;
        if($this->m_DB)
            return true;
        else
            return false;

    }
  /**
    Closes the connection to the database.
  */
    public function Disconnect()
    {
        mysql_close($this->m_DB);
    }

  /**
    Sets the TablePrefix
  */
    public function SetTablePrefix($Prefix)
    {
        $this->m_TablePrefix = mysql_real_escape_string($Prefix);
    }

    /**
     * Sets the current account, the user is using.
    */
    public function SetCurrentAccount(Account &$Account)
    {
        $this->m_Acc = $Account;
    }

    public function GetCurrentAccount()
    {
        return $this->m_Acc;
    }

  /**
   * Fetches an Event from Database
   * @param ID Id of the eventh
   * @return Event or NULL
   * @throws FormatException
   * @throws SQLException
  */
    public function GetEvent($ID)
    {
        if(!Check::ID($ID))
            throw new FormatException("ID");

        $sql = 'SELECT
                    *
                FROM `'.$this->m_TablePrefix.'evenc_events`
                WHERE
                    `id` = "'.mysql_real_escape_string($ID).'"
                    AND
                    ( /* Select current account or use wildcard */
                        ( '.mysql_real_escape_string($this->m_Acc->GetID()).' != 0 AND `account` = '.mysql_real_escape_string($this->m_Acc->GetID()).' )
                            OR
                        ( '.mysql_real_escape_string($this->m_Acc->GetID()).' = 0)
                    )
                LIMIT 1';
        $res = mysql_query($sql);
        if(!$res)
            throw new SQLException($sql);

        if(mysql_num_rows($res) == 1)
        {
          $array = mysql_fetch_assoc($res);
          $event = new Event();
          $event->SetID($array["id"]);
          $event->SetStartDate($array["startdate"]);
          $event->SetEndDate($array["enddate"]);
          $event->SetTime(substr($array["time"],0,5));
          $event->SetLocation($array["location"]);
          $event->SetName($array["name"]);
          $event->SetText($array["text"]);
          $event->SetRepeat($array["repeat"]);
          $event->SetLog($array["log"]);
          return $event;
        }

        return NULL;
    }

    /**
     * Reads all events from an account
     * @return Event Array
     * @throws SQLException
    */
    public function GetAllEvents()
    {
        $sql = 'SELECT
                    *
                FROM `'.$this->m_TablePrefix.'evenc_events`
                WHERE
                    ( /* Select current account or use wildcard */
                        ( '.mysql_real_escape_string($this->m_Acc->GetID()).' != 0 AND `account` = '.mysql_real_escape_string($this->m_Acc->GetID()).' )
                            OR
                        ( '.mysql_real_escape_string($this->m_Acc->GetID()).' = 0)
                    )
                ORDER BY
                    `startdate` DESC,
                    `time` DESC
                ';
        $res = mysql_query($sql);
        if(!$res)
            throw new SQLException($sql);

        $retval =  array();
        while($row = mysql_fetch_assoc($res))
        {
            try
            {
                $event = new Event();
                $event->SetID($row["id"]);
                $event->SetStartDate($row["startdate"]);
                $event->SetEndDate($row["enddate"]);
                $event->SetTime(substr($row["time"],0,5));
                $event->SetLocation($row["location"]);
                $event->SetName($row["name"]);
                $event->SetText($row["text"]);
                $event->SetRepeat($row["repeat"]);
                $event->SetLog($row["log"]);

                array_push($retval, $event);
            }
            catch(Exception $e)
            {
                continue; // Ignore Errors
            }
        }
        return $retval;
    }

    /**
     * Returns a list of all comments from events from this account
     * @return Comment Array
     * @throws SQLException
    */
    public function GetAllComments()
    {
        $sql = 'SELECT
                    *
                FROM `'.$this->m_TablePrefix.'evenc_comments`
                WHERE
                    `eventid` IN ( /* only from events from this account */
                            SELECT
                                `id`
                            FROM `'.$this->m_TablePrefix.'evenc_events`
                            WHERE
                            ( /* Select current account or use wildcard */
                                ( '.mysql_real_escape_string($this->m_Acc->GetID()).' != 0 AND `account` = '.mysql_real_escape_string($this->m_Acc->GetID()).' )
                                OR
                                ( '.mysql_real_escape_string($this->m_Acc->GetID()).' = 0)
                                )
                            )
                ORDER BY
                    date DESC,
                    time DESC
                ';
        $res = mysql_query($sql);
        if(!$res)
            throw new SQLException($sql);

        $retval = array();
        while($row = mysql_fetch_assoc($res))
        {
            try
            {
                $cmt = new Comment();
                $cmt->SetID(        $row['id']);
                $cmt->SetEventID(   $row['eventid']);
                $cmt->SetDate(      $row['date']);
                $cmt->SetTime(      $row['time']);
                $cmt->SetName(      $row['name']);
                $cmt->SetEMail(     $row['email']);
                $cmt->SetText(      $row['text']);
                $cmt->SetLog(       $row['log']);
                array_push($retval, $cmt);
            }
            catch(Exception $e)
            {
                continue; //Ignore Errors
            }
        }
        return $retval;
    }

  /**
   * Number of events from this account
   * @return Number of events for the current account
   * @throws SQLException
  */
    public function NumEvents()
    {
        $sql = 'SELECT
                    COUNT(`id`)
                FROM
                    `'.$this->m_TablePrefix.'evenc_events`
                WHERE
                    ( /* Select current account or use wildcard */
                        ( '.mysql_real_escape_string($this->m_Acc->GetID()).' != 0 AND `account` = '.mysql_real_escape_string($this->m_Acc->GetID()).' )
                            OR
                        ( '.mysql_real_escape_string($this->m_Acc->GetID()).' = 0)
                    )';
        $res = mysql_query($sql);
        if(!$res)
            throw new SQLException($sql);
        return mysql_result($res,0,"COUNT(`id`)");
    }

    /**
     * Number of events at a date.
     * @param Date
     * @throws SQLException
    */
    public function NumEventsAtDate($Date)
    {
        if(!Check::Date($Date))
            throw new FormatException('Date');

        $Date = mysql_real_escape_string($Date);
        $sql = 'SELECT COUNT(`id`)
                FROM
                    `'.$this->m_TablePrefix.'evenc_events`
                WHERE
                (
                    (`startdate` = "'.$Date.'" AND `repeat` = "NONE")
                        OR
                    (`startdate` <= "'.$Date.'" AND  "'.$Date.'" <= `enddate` AND `repeat` = "DAY")
                        OR
                    (`startdate` <= "'.$Date.'" AND  "'.$Date.'" <= `enddate` AND DAYOFWEEK(`startdate`) = DAYOFWEEK("'.$Date.'") AND `repeat` = "WEEK")
                        OR
                    (`startdate` <= "'.$Date.'" AND  "'.$Date.'" <= `enddate` AND DAYOFMONTH(`startdate`) = DAYOFMONTH("'.$Date.'") AND `repeat` = "MONTH")
                        OR
                    (`startdate` <= "'.$Date.'" AND  "'.$Date.'" <= `enddate` AND DATE_FORMAT(`startdate`,"%m%d") = DATE_FORMAT("'.$Date.'","%m%d") AND `repeat` = "YEAR")
                )
                AND
                ( /* Select current account or use wildcard */
                    ( '.mysql_real_escape_string($this->m_Acc->getID()).' != 0 AND `account` = '.mysql_real_escape_string($this->m_Acc->getID()).' )
                        OR
                    ( '.mysql_real_escape_string($this->m_Acc->getID()).' = 0)
                )
        ';
        $res = mysql_query($sql);
        if(!$res)
            throw new SQLException($sql);

        if(mysql_num_rows($res))
            return mysql_result($res,0,"COUNT(`id`)");
        return 0;
    }

  /**
   * Gets all events from a day.
   * @return array or NULL
   * @throws SQLException
   * @throws FormatException
  */
    public function GetEventsForDate($Date)
    {
        $eventArray = array();
        if(!Check::Date($Date))
            throw new FormatException("Date");

        $Date = mysql_real_escape_string($Date);
        $sql = 'SELECT
                    *
                FROM
                    `'.$this->m_TablePrefix.'evenc_events`
                WHERE
                (
                    (`startdate` = "'.$Date.'" AND `repeat` = "NONE")
                        OR
                    (`startdate` <= "'.$Date.'" AND  "'.$Date.'" <= `enddate` AND `repeat` = "DAY")
                        OR
                    (`startdate` <= "'.$Date.'" AND  "'.$Date.'" <= `enddate` AND DAYOFWEEK(`startdate`) = DAYOFWEEK("'.$Date.'") AND `repeat` = "WEEK")
                        OR
                    (`startdate` <= "'.$Date.'" AND  "'.$Date.'" <= `enddate` AND DAYOFMONTH(`startdate`) = DAYOFMONTH("'.$Date.'") AND `repeat` = "MONTH")
                        OR
                    (`startdate` <= "'.$Date.'" AND  "'.$Date.'" <= `enddate` AND DATE_FORMAT(`startdate`,"%m%d") = DATE_FORMAT("'.$Date.'","%m%d") AND `repeat` = "YEAR")
                )
                    AND
                    ( /* Select current account or use wildcard */
                        ( '.mysql_real_escape_string($this->m_Acc->GetID()).' != 0 AND `account` = '.mysql_real_escape_string($this->m_Acc->GetID()).' )
                            OR
                        ( '.mysql_real_escape_string($this->m_Acc->GetID()).' = 0)
                    )
                ORDER BY
                    `time` ASC';

        $res = mysql_query($sql);
        if(!$res)
            throw new SQLException($sql);

        $num = mysql_num_rows($res);
        if($num)
        {
            for($i=0; $i<$num; $i++)
            {
                $event = new Event();
                try
                {
                    $arr = mysql_fetch_assoc($res);
                    $event->SetID($arr['id']);
                    $event->SetName($arr['name']);
                    $event->SetStartDate($arr['startdate']);
                    $event->SetEndDate($arr['enddate']);
                    $event->SetTime(substr($arr['time'],0,5));
                    $event->SetLocation($arr['location']);
                    $event->SetText($arr['text']);
                    $event->SetLog($arr['log']);
                    $event->SetRepeat($arr['repeat']);
                    array_push($eventArray,$event);
                }
                catch(FormatExceptionException $e)
                {
                    //
                    continue;
                }
            }
            return $eventArray;
        }

        return NULL;
    }

  /**
   * Searches the db for events containing the search string
   * @return array or NULL
   * @throws SQLException
  */
    public function SearchEvents($SearchString)
    {
        $eventArray = array();

        $SearchString = mysql_real_escape_string($SearchString);
        $sql = 'SELECT
                    *
                FROM
                `'.$this->m_TablePrefix.'evenc_events`
                WHERE
                    (
                    `text` LIKE "%'.$SearchString.'%"
                        OR
                    `name` LIKE "%'.$SearchString.'%"
                        OR
                    `location` LIKE "%'.$SearchString.'%"
                    )
                    AND
                    ( /* Select current account or use wildcard */
                        ( '.mysql_real_escape_string($this->m_Acc->GetID()).' != 0 AND `account` = '.mysql_real_escape_string($this->m_Acc->GetID()).' )
                            OR
                        ( '.mysql_real_escape_string($this->m_Acc->GetID()).' = 0)
                    )
                ORDER BY
                    `startdate` ASC,
                    `time` ASC';

        $res = mysql_query($sql);
        if(!$res)
            throw new SQLException($sql);
        $num = mysql_num_rows($res);
        if($num)
        {
            for($i=0; $i<$num; $i++)
            {
                try
                {
                    $event = new Event();
                    $event->SetID(mysql_result($res,$i, "id"));
                    $event->SetStartDate(mysql_result($res,$i, "startdate"));
                    $event->SetEnddAte(mysql_result($res,$i, "enddate"));
                    $event->SetTime(substr(mysql_result($res,$i, "time"),0,5));
                    $event->SetLocation(mysql_result($res,$i, "location"));
                    $event->SetName(mysql_result($res,$i, "name"));
                    $event->SetText(mysql_result($res,$i, "text"));
                    $event->SetRepeat(mysql_result($res,$i, "repeat"));
                    $event->SetLog(mysql_result($res,$i, "log"));
                    array_push($eventArray,$event);
                }
                catch(FormatException $f)
                {
                    continue; //Ignore errors
                }
            }
            return $eventArray;
        }
        return NULL;
    }

    /**
     * Fetches an comment from the db
     * @return Comment or NULL
     * @throws SQLException
     * @throws FormatException
    */
    public function GetComment($CommentID)
    {
        if(!Check::ID($CommentID))
            throw new FormatException("ID");

        $sql = 'SELECT
                    `'.$this->m_TablePrefix.'evenc_comments`.`id`       AS CommentID,
                    `'.$this->m_TablePrefix.'evenc_comments`.`eventid`  AS EventID,
                    `'.$this->m_TablePrefix.'evenc_comments`.`name`     AS CommentName,
                    `'.$this->m_TablePrefix.'evenc_comments`.`email`    AS CommentEMail,
                    `'.$this->m_TablePrefix.'evenc_comments`.`date`     AS CommentDate,
                    `'.$this->m_TablePrefix.'evenc_comments`.`time`     AS CommentTime,
                    `'.$this->m_TablePrefix.'evenc_comments`.`text`     AS CommentText,
                    `'.$this->m_TablePrefix.'evenc_comments`.`log`      AS CommentLog
                FROM
                    `'.$this->m_TablePrefix.'evenc_comments`,
                    `'.$this->m_TablePrefix.'evenc_events`
                WHERE
                    `'.$this->m_TablePrefix.'evenc_events`.`id` = `'.$this->m_TablePrefix.'evenc_comments`.`eventid`
                    AND
                    `'.$this->m_TablePrefix.'evenc_comments`.`id` = "'.mysql_real_escape_string($CommentID).'"
                    AND
                    ( /* Select current account or use wildcard */
                        ( '.mysql_real_escape_string($this->m_Acc->GetID()).' != 0 AND `'.$this->m_TablePrefix.'evenc_events`.`account` = '.mysql_real_escape_string($this->m_Acc->GetID()).' )
                            OR
                        ( '.mysql_real_escape_string($this->m_Acc->GetID()).' = 0)
                    )
                LIMIT 1';

        $res = mysql_query($sql);
        if(!$res)
            throw new SQLException($sql);

        if(mysql_num_rows($res) > 0)
        {
            $row = mysql_fetch_assoc($res);
            $c = new Comment();
            $c->SetID($row['CommentID']);
            $c->SetEventID($row['EventID']);
            $c->SetName($row['CommentName']);
            $c->SetEMail($row['CommentEMail']);
            $c->SetDate($row['CommentDate']);
            $c->SetTime($row['CommentTime']);
            $c->SetText($row['CommentText']);
            $c->SetLog($row['CommentLog']);
            return $c;
        }
        return NULL;
    }

    /**
     * Deletes an comment
     * @return bool true on success, false on failure
     * @throws SQLException
    */
    public function DeleteComment(Comment &$Comment)
    {
        $sql = 'DELETE FROM
                    `'.$this->m_TablePrefix.'evenc_comments`
                WHERE
                    `id` = "'.mysql_real_escape_string($Comment->GetRawID()).'"
                    AND
                    `eventid` IN
                        (
                            SELECT
                                `id`
                             FROM
                                `'.$this->m_TablePrefix.'evenc_events`
                             WHERE
                             /* Select current account or use wildcard */
                             ( '.mysql_real_escape_string($this->m_Acc->GetID()).' != 0 AND `account` = '.mysql_real_escape_string($this->m_Acc->GetID()).' )
                             OR
                            ( '.mysql_real_escape_string($this->m_Acc->GetID()).' = 0)
                        )
                LIMIT 1';
        $res = mysql_query($sql);
        if(!$res)
            throw new SQLException($sql);
        return mysql_affected_rows() > 0;
    }

    /**
     * Deletes all comments from an account
     * @return true on success, false on failure
     * @throws SQLException
    */
    public function DeleteAllComments()
    {
        $sql = 'DELETE FROM
                    `'.$this->m_TablePrefix.'evenc_comments`
                WHERE
                    `eventid` IN
                        (
                            SELECT
                                `id`
                             FROM
                                `'.$this->m_TablePrefix.'evenc_events`
                             WHERE
                             /* Select current account or use wildcard */
                             ( '.mysql_real_escape_string($this->m_Acc->GetID()).' != 0 AND `account` = '.mysql_real_escape_string($this->m_Acc->GetID()).' )
                             OR
                            ( '.mysql_real_escape_string($this->m_Acc->GetID()).' = 0)
                        )
                LIMIT 1';
        $res = mysql_query($sql);
        if(!$res)
            throw new SQLException($sql);
        return mysql_affected_rows() >= 0;
    }

  /**
   * Fetches all comments from an event.
   * @return bool true on success, false on failure
   * @throws SQLException
   * @todo Account check
  */
    public function GetCommentsForEvent(Event &$Event)
    {
        $commentArray = array();
        $sql = 'SELECT
                    *
                FROM
                    `'.$this->m_TablePrefix.'evenc_comments`
                WHERE
                    `eventid` = "'.mysql_real_escape_string($Event->GetID()).'"
                ORDER BY
                    `date` ASC,
                    `time` ASC';
        $res = mysql_query($sql);
        if(!$res)
            throw new SQLException($sql);
        $num = mysql_num_rows($res);
        if($num)
        {
            for($i=0; $i<$num; $i++)
            {
                try
                {
                    $comment = new Comment();
                    $comment->SetID(        mysql_result($res,$i,"id"));
                    $comment->SetEventID(   mysql_result($res,$i,"eventid"));
                    $comment->SetDate(      mysql_result($res,$i,"date"));
                    $comment->SetTime(      substr(mysql_result($res,$i,"time"),0,5));
                    $comment->SetName(      mysql_result($res,$i,"name"));
                    $comment->SetEMail(     mysql_result($res,$i,"email"));
                    $comment->SetText(      mysql_result($res,$i,"text"));
                    $comment->SetLog(       mysql_result($res,$i,"log"));
                    array_push($commentArray, $comment);
                }
                catch(FormatException $f)
                {
                    //Ignore
                    continue;
                }
            }
        }

        return $commentArray;
    }

    /**
     * Deletes all comments that belong to an event
     * @return boolean true on success, false on failure
     * @throws SQLException
     * @todo Account check
    */
    public function DeleteAllCommentsForEvent(Event &$Event)
    {
        $sql = 'DELETE FROM
                    `'.$this->m_TablePrefix.'evenc_comments`
                WHERE
                    `eventid` = "'.mysql_real_escape_string($Event->GetID()).'"';
        $res = mysql_query($sql);
        if(!$res)
            throw new SQLException($sql);
        return mysql_affected_rows() >= 0;
    }

  /**
   * Adds a new comment
   * @throws SQLException
  */
    public function AddComment(Comment &$Comment)
    {
        $sql = 'INSERT
                    `'.$this->m_TablePrefix.'evenc_comments`
                (`eventid`, `date`, `time`, `name`, `email`, `text`, `log`)
                VALUES (
                    "'.mysql_real_escape_string($Comment->GetRawEventID()).'",
                    "'.mysql_real_escape_string($Comment->GetRawDate()).'",
                    "'.mysql_real_escape_string($Comment->GetRawTime()).'",
                    "'.mysql_real_escape_string($Comment->GetRawName()).'",
                    "'.mysql_real_escape_string($Comment->GetRawEMail()).'",
                    "'.mysql_real_escape_string($Comment->GetRawText()).'",
                    "'.mysql_real_escape_string($Comment->GetRawLog()).'"
                )';

        $res = mysql_query($sql);
        if(!$res)
            throw new SQLException($sql);

        if(mysql_affected_rows() == -1) // => -1 last query failed
            return false;
        else
        {
            $Comment->SetID(mysql_insert_id());
            return true;
        }
    }


  /**
   * Adds a new event to the db. On Success the event-id will be set
   * @return bool true on success, false on failure
   * @throws SQLException
  */
    public function AddEvent(Event &$Event)
    {

        $sql = 'INSERT
                    `'.$this->m_TablePrefix.'evenc_events`
                (`account`,`name`, `startdate`, `enddate`, `time`, `repeat`, `text`, `location`, `log`)
                VALUES
                (
                    "'.mysql_real_escape_string($this->m_Acc->GetID()).'",
                    "'.mysql_real_escape_string($Event->GetRawName()).'",
                    "'.mysql_real_escape_string($Event->GetRawStartDate()).'",
                    "'.mysql_real_escape_string($Event->GetRawEndDate()).'",
                    "'.mysql_real_escape_string($Event->GetRawTime()).'",
                    "'.mysql_real_escape_string($Event->GetRawRepeat()).'",
                    "'.mysql_real_escape_string($Event->GetRawText()).'",
                    "'.mysql_real_escape_string($Event->GetRawLocation()).'",
                    "'.mysql_real_escape_string($Event->GetRawLog()).'"
                )';

        $res = mysql_query($sql);
        if(!$res)
            throw new SQLException($sql);

        if(mysql_affected_rows() == -1) // -1 => last query failed
        {
            return false;
        }
        else
        {
            $Event->SetID(mysql_insert_id());
            return true;
        }
    }

    /**
     * Updates an existing event
     * @param Event Event Target event
     * @return bool true on success, false on fail
     * @throws SQLException
    */
    public function UpdateEvent(Event &$Event)
    {
        $sql = 'UPDATE
                    `'.$this->m_TablePrefix.'evenc_events`
                SET
                    `name`      = "'.mysql_real_escape_string($Event->GetRawName()).'",
                    `startdate` = "'.mysql_real_escape_string($Event->GetRawStartDate()).'",
                    `enddate`   = "'.mysql_real_escape_string($Event->GetRawEndDate()).'",
                    `time`      = "'.mysql_real_escape_string($Event->GetRawTime()).'",
                    `repeat`    = "'.mysql_real_escape_string($Event->GetRawRepeat()).'",
                    `text`      = "'.mysql_real_escape_string($Event->GetRawText()).'",
                    `location`  = "'.mysql_real_escape_string($Event->GetRawLocation()).'",
                    `log`       = "'.mysql_real_escape_string($Event->GetRawLog()).'"
                WHERE
                    `id` = "'.mysql_real_escape_string($Event->GetRawID()).'"
                    AND
                    ( /* Select current account or use wildcard */
                        ( '.mysql_real_escape_string($this->m_Acc->GetID()).' != 0 AND `'.$this->m_TablePrefix.'evenc_events`.`account` = '.mysql_real_escape_string($this->m_Acc->GetID()).' )
                            OR
                        ( '.mysql_real_escape_string($this->m_Acc->GetID()).' = 0)
                    )
                LIMIT 1';
        $res = mysql_query($sql);

        if(!$res)
            throw new SQLException($sql);

        return mysql_affected_rows() >= 0;
    }

    /**
     * Removes an event from the db
     * @return bool true on success, false on failure
     * @throws SQLException
    */
    public function DeleteEvent(Event &$Event)
    {
        $sql = 'DELETE FROM
                    `'.$this->m_TablePrefix.'evenc_events`
                WHERE
                    `id` = "'.mysql_real_escape_string($Event->GetID()).'"
                    AND
                    ( /* Select current account or use wildcard */
                        ( '.mysql_real_escape_string($this->m_Acc->GetID()).' != 0 AND `'.$this->m_TablePrefix.'evenc_events`.`account` = '.mysql_real_escape_string($this->m_Acc->GetID()).' )
                            OR
                        ( '.mysql_real_escape_string($this->m_Acc->GetID()).' = 0)
                    )
                LIMIT 1';
        $res = mysql_query($sql);
        if(!$res)
            throw SQLException($sql);

        return mysql_affected_rows() > 0;
    }

    /**
     * Deletes all events from an account
     * @return true on success, false on failure
     * @throws SQLException
    */
    public function DeleteAllEvents()
    {
        $sql = 'DELETE FROM
                    `'.$this->m_TablePrefix.'evenc_events`
                WHERE
                    ( /* Select current account or use wildcard */
                        ( '.mysql_real_escape_string($this->m_Acc->GetID()).' != 0 AND `'.$this->m_TablePrefix.'evenc_events`.`account` = '.mysql_real_escape_string($this->m_Acc->GetID()).' )
                            OR
                        ( '.mysql_real_escape_string($this->m_Acc->GetID()).' = 0)
                    )';
        $res = mysql_query($sql);
        if(!$res)
            throw new SQLException($sql);
        return mysql_affected_rows() >= 0;
    }

    /**
     * Reads the settings from the database.
     * @throws SQLException
     * @throws FormatException
     * @todo return Settings
    */
    public function GetSettings(Settings &$Settings)
    {
        $set = array(Settings::LANGUAGE     => array(&$Settings, 'SetLang'),
                    Settings::TEMPLATE      => array(&$Settings, 'SetTemplate'),
                    Settings::BBCODE        => array(&$Settings, 'SetBBCode'),
                    Settings::REWRITEURL    => array(&$Settings, 'SetRewriteURL'),
                    Settings::PUBLICPOSTING => array(&$Settings, 'SetPublicPosting'),
                    Settings::COMMENTS      => array(&$Settings, 'SetComments'));

        $sql = 'SELECT
                    *
                FROM
                    `'.$this->m_TablePrefix.'evenc_settings`
                WHERE
                    `account` = '.mysql_real_escape_string($this->m_Acc->getID()).'
                ';

        $res = mysql_query($sql);
        if(!$res)
            throw new SQLException($sql);

        $num = mysql_num_rows($res);

        while($row = mysql_fetch_assoc($res))
        {
            //Function exists?
            if(isset($set[$row['key']]))
            {
                //Set value
                call_user_func($set[$row['key']], $row['value']);
            }
        }
    }

  /**
   * Saves the settings to the database.
   * @throws SQLException
  */
    public function SetSettings(Settings &$Settings)
    {
        $set = array(Settings::LANGUAGE         => $Settings->GetLang(),
                    Settings::TEMPLATE          => $Settings->GetTemplate(),
                    Settings::BBCODE            => $Settings->GetBBCode(),
                    Settings::REWRITEURL        => $Settings->GetRewriteURL(),
                    Settings::PUBLICPOSTING     => $Settings->GetPublicPosting(),
                    Settings::COMMENTS          => $Settings->GetComments());

        foreach($set AS $key => $val)
        {
            $sql = 'INSERT
                    INTO
                        `'.$this->m_TablePrefix.'evenc_settings`
                    (`key`, `value`, `account`)
                    VALUES
                        (
                        "'.mysql_real_escape_string($key).'",
                        "'.mysql_real_escape_string($val).'",
                        "'.mysql_real_escape_string($this->m_Acc->GetID()).'"
                        )
                    ON DUPLICATE KEY
                        UPDATE `value`="'.mysql_real_escape_string($val).'"';
            $res = mysql_query($sql);
            if(!$res)
                throw new SQLException($sql);
        }
    }

    /**
     * Removes all settings from an account
     * @return true on success, false on failure
     * @throws SQLException
    */
    public function DeleteAllSettings()
    {
        $sql = 'DELETE FROM
                    `'.$this->m_TablePrefix.'evenc_settings`
                WHERE
                    ( /* Select current account or use wildcard */
                        ( '.mysql_real_escape_string($this->m_Acc->GetID()).' != 0 AND `account` = '.mysql_real_escape_string($this->m_Acc->GetID()).' )
                            OR
                        ( '.mysql_real_escape_string($this->m_Acc->GetID()).' = 0)
                    )';
        $res = mysql_query($sql);
        if(!$res)
            throw new SQLException($sql);
        return mysql_affected_rows() >= 0;
    }

    /**
     * Searches an account by his name.
     * @throws FormatException
     * @throws SQLException
    */
    public function GetAccountByLogin(&$Login)
    {
        if(!Check::Login($Login))
            throw new FormatException('Login');

        $sql = 'SELECT
                    `id`,
                    `login`,
                    `email`,
                    `name`,
                    `password`
                FROM `'.$this->m_TablePrefix.'evenc_accounts`
                WHERE
                    `login` = "'.mysql_real_escape_string($Login).'"
                LIMIT 1';
        $res = mysql_query($sql);
        if(!$res)
            throw new SQLException($sql);
        if(mysql_num_rows($res))
        {
            $acc = new Account();
            $row = mysql_fetch_assoc($res);
            $acc->SetID(        $row['id']);
            $acc->SetLogin(     $row['login']);
            $acc->SetEMail(     $row['email']);
            $acc->SetName(      $row['name']);
            $acc->SetPassword(  $row['password']);
            return $acc;
        }
        else
        {
            return NULL;
        }
    }

    /**
     * Updates an existing Account
    */
    public function UpdateAccount(Account $Acc)
    {
        if(!Check::ID($Acc->GetID()))
            throw new FormatException('ID');

        $sql = 'UPDATE
                    `'.$this->m_TablePrefix.'evenc_accounts`
                SET
                    `email` = "'.mysql_real_escape_string($Acc->GetRawEMail()).'",
                    `name` = "'.mysql_real_escape_string($Acc->GetRawName()).'",
                    `password` = "'.mysql_real_escape_string($Acc->GetRawPassword()).'"
                WHERE
                    `id` = '.mysql_real_escape_string($Acc->GetRawID()).'
                Limit 1';
        $res = mysql_query($sql);
        if(!$res)
            throw new SQLException($sql);
    }

    /**
     * Removes an existing account
     * @return boolean true in success, false in failure
     * @throws SQLException
    */
    public function DeleteAccount(Account $Acc)
    {
        $sql = 'DELETE FROM
                    `'.$this->m_TablePrefix.'evenc_accounts`
                WHERE
                    `id` = "'.mysql_real_escape_string($Acc->GetID()).'"
                LIMIT 1';
        $res = mysql_query($sql);
        if(!$res)
            throw new SQLException($sql);
        return mysql_affected_rows() > 0;
    }

} // end class
?>
