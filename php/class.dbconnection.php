<?
/* evenc - a simple event calendar
 * Copyright (c) 2010 Andreas Volk
 * Licensed under the MIT License
 */

abstract class DBConnection
{
    //MYSQL-Database
    const MYSQL = 0;

    private static $instance = null;
    private static $instance_type = DBConnection::MYSQL;

    /**
    */
    public static function GetInstance($type = DBConnection::MYSQL)
    {
        if(is_null(self::$instance) || $type != self::$instance_type)
        {
            //create new instance
            switch($type)
            {
                default: //MYSQL
                {
                    self::$instance =& MySQLConnection::GetInst();
                    self::$instance_type = self::MYSQL;
                    break;
                }
            }
        }
        return self::$instance;
    }

    /**
     * Create Instance
    */
    public abstract static function getInst();

    public abstract function Connect ($Host, $User, $Password, $Database);
    public abstract function Disconnect ();
    public abstract function SetTablePrefix ($Prefix);
    public abstract function SetCurrentAccount (Account &$Account);
 	public abstract function SetSettings (Settings &$Settings);

 	public abstract function AddComment (Comment &$Comment);
 	public abstract function AddEvent (Event &$Event);

 	public abstract function DeleteAllComments ();
 	public abstract function DeleteAllCommentsForEvent (Event &$Event);
 	public abstract function DeleteAllEvents ();
 	public abstract function DeleteAllSettings ();
 	public abstract function DeleteComment (Comment &$Comment);
 	public abstract function DeleteEvent (Event &$Event);

 	public abstract function GetAllComments ();
 	public abstract function GetAllEvents ();
 	public abstract function GetComment ($CommentID);
 	public abstract function GetCommentsForEvent (Event &$Event);
 	public abstract function GetCurrentAccount ();
 	public abstract function GetEvent ($ID);
 	public abstract function GetEventsForDate ($Date);
 	public abstract function GetSettings (Settings &$Settings);

 	public abstract function NumEvents ();
 	public abstract function NumEventsAtDate ($Date);

 	public abstract function SearchEvents ($SearchString);



 	public abstract function UpdateEvent (Event &$Event);
}
?>
