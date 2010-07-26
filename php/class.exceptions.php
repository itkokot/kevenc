<?
/* evenc - a simple event calendar
 * Copyright (c) 2010 Andreas Volk
 * Licensed under the MIT License
 */


class FormatException extends Exception
{
    public function __construct($message = null, $code = 0)
    {
        parent::__construct("[_l_FORMATEXCEPTION_] ".$message, $code);
    }
};

/**
 * @deprecated remove
*/
class DatabaseInsertException extends Exception
{};

class LanguageException extends Exception
{
    public function __construct($message = null, $code = 0)
    {
        parent::__construct("'".$message."' [_l_LANGUAGEEXCEPTION_] ", $code);
    }
};

class TemplateException extends Exception
{
    public function __construct($message = null, $code = 0)
    {
        parent::__construct("'".$message."' [_l_TEMPLATEEXCEPTION_] ", $code);
    }
}

/**
 * @deprecated ->QueryExepction
*/
class SQLException extends Exception
{
    public function __construct($message = null, $code = 0)
    {
        parent::__construct($message."\nmysql_error: ".mysql_error(), $code);
    }
}

/**
 * File not found or no access
*/
class FileException extends Exception
{
}

/**
 * Parsing a file failed
*/
class ParseException extends Exception
{
    const FILE = 'File';
    const LINE = 'Line';
    const POSITION = 'Position';

    public function __construct($exception, $code = null)
    {
        parent::__construct($exception, 0);
        $this->code = $code;
    }
}

/**
 * A feature is not yet implemented
 */
class ImplementationException extends Exception{
}

?>
