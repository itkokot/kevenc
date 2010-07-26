<?
/* evenc - a simple event calendar
 * Copyright (c) 2010 Andreas Volk
 * Licensed under the MIT License
 */


/**
 * An RSS Feed - version 2.0
*/
class RSSFeed
{
    private $rss_version = "2.0";
    private $title  = "Feed Title";
    private $link   = "http://feed/link";
    private $description    = "Description";

    /// Feed item
    private $items  = array();

    /**
     * Creates an empty RSS Feed
     * @param Title Title of the feed
    */
    public function __construct($Title="")
    {
        $this->title = $Title;
    }

    /**
     * Adds a new item to the feed
     * @param Item RSSItem The item to add
    */
    public function addItem(RSSItem $Item)
    {
        $this->items[] = $Item;
    }

    /**
     * Sets the title for this feed
     * @param Title New title
    */
    public function setTitle($Title)
    {
        $this->title = $Title;
    }

    /**
     * Sets the link for this feed
     * @param Link New link
    */
    public function setLink($Link)
    {
        $this->link = $Link;
    }

    /**
     * Sets the description for this feed
     * @param Description New description
    */
    public function setDescription($Description)
    {
        $this->description = $Description;
    }

    /**
     * Returns the feed, including all items, as an [xml] string
    */
    public function prints()
    {
        $retval = "<?xml version='1.0'?>\n";
        $retval .= "<rss version='".htmlentities($this->rss_version)."'>\n";
        $retval .= "<channel>\n";
        $retval .= "<title>".htmlentities($this->title)."</title>\n";
        $retval .= "<link>".htmlentities($this->link)."</link>\n";
        $retval .= "<description>".htmlentities($this->description)."</description>\n";
        foreach($this->items AS $key => $val)
        {
            $retval .= $val->prints();
        }
        $retval .= "</channel>\n";
        $retval .= "</rss>\n";
        return $retval;
    }
}

/**
 * An Item for an RSS Feed
*/
class RSSItem
{
    private $title  = "Item Title";
    private $link   = "http://feed/link/item";
    private $description = "Description";
    private $guid   = "";
    private $pubDate = "";


    /**
     * Creates an new item
     * @param Title Title of the item
    */
    public function __construct($Title)
    {
        $this->title = $Title;
    }

    /**
     * Sets the title for this item
     * @param Title New title
    */
    public function setTitle($Title)
    {
        $this->title = $Title;
    }

    /**
     * Sets the link for this item
     * @param Link New URL
    */
    public function setLink($Link)
    {
        $this->link = $Link;
    }

    /**
     * Sets the description for this item
     * @param Description New description
    */
    public function setDescription($Description)
    {
        $this->description = $Description;
    }

    public function setGuid($Guid)
    {
        $this->guid = $Guid;
    }

    public function setPubDate($Date)
    {
        $this->pubDate = $Date;
    }

    /**
     * Returns the item as an xml string
    */
    public function prints()
    {
        $retval = "<item>\n";
        if($this->title)
            $retval .= "<title>".htmlentities($this->title)."</title>\n";
        if($this->link)
            $retval .= "<link>".htmlentities(strip_tags($this->link))."</link>\n";
        if($this->description)
            $retval .= "<description>".htmlentities($this->description)."</description>\n";
        if($this->guid)
            $retval .= "<guid isPermaLink='false' >".htmlentities($this->guid)."</guid>\n";
        if($this->pubDate)
            $retval .="<pubDate>".htmlentities($this->pubDate)."</pubDate>\n";
        $retval .= "</item>\n";
        return $retval;
    }
}
?>
