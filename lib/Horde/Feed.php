<?php
/**
 * $Horde: framework/Feed/lib/Horde/Feed.php,v 1.11 2008/09/28 04:27:03 chuck Exp $
 *
 * Portions Copyright 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Feed
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Feed
 */
class Horde_Feed
{
    /**
     * HTTP client object to use for accessing feeds
     * @var Horde_Http_Client
     */
    protected static $_httpClient = null;

    /**
     * Override HTTP PUT and DELETE request methods?
     * @var boolean
     */
    protected static $_httpMethodOverride = false;

    /**
     * Set the HTTP client instance
     *
     * Sets the HTTP client object to use for retrieving the feeds. If none
     * is set, the default Horde_Http_Client will be used.
     *
     * @param Horde_Http_Client $httpClient
     */
    public static function setHttpClient($httpClient)
    {
        self::$_httpClient = $httpClient;
    }

    /**
     * Gets the HTTP client object.
     *
     * @return Horde_Http_Client
     */
    public static function getHttpClient()
    {
        if (!self::$_httpClient) {
            self::$_httpClient = new Horde_Http_Client;
        }

        return self::$_httpClient;
    }

    /**
     * Toggle using POST instead of PUT and DELETE HTTP methods.
     *
     * Some feed implementations do not accept PUT and DELETE HTTP methods, or
     * they can't be used because of proxies or other measures. This allows
     * turning on using POST where PUT and DELETE would normally be used; in
     * addition, an X-Method-Override header will be sent with a value of PUT or
     * DELETE as appropriate.
     *
     * @param boolean $override  Whether to override PUT and DELETE.
     */
    public static function setHttpMethodOverride($override = true)
    {
        self::$_httpMethodOverride = $override;
    }

    /**
     * Get the HTTP override state
     *
     * @return boolean
     */
    public static function getHttpMethodOverride()
    {
        return self::$_httpMethodOverride;
    }

    /**
     * Create a Feed object based on a DOMDocument.
     *
     * @param DOMDocument $doc The DOMDocument object to import.
     *
     * @throws Horde_Feed_Exception
     *
     * @return Horde_Feed_Base The feed object imported from $doc
     */
    public static function create(DOMDocument $doc, $uri = null)
    {
        // Try to find the base feed element or a single <entry> of an
        // Atom feed.
        if ($feed = $doc->getElementsByTagName('feed')->item(0)) {
            // Return an Atom feed.
            return new Horde_Feed_Atom($feed, $uri);
        } elseif ($entry = $doc->getElementsByTagName('entry')->item(0)) {
            // Return an Atom single-entry feed.
            $feeddoc = new DOMDocument($doc->version,
                                       $doc->actualEncoding);
            $feed = $feeddoc->appendChild($feeddoc->createElement('feed'));
            $feed->appendChild($feeddoc->importNode($entry, true));

            return new Horde_Feed_Atom($feed, $uri);
        }

        // Try to find the base feed element of an RSS feed.
        if ($channel = $doc->getElementsByTagName('channel')->item(0)) {
            // Return an RSS feed.
            return new Horde_Feed_Rss($channel, $uri);
        }

        // Try to find an outline element of an OPML blogroll.
        if ($outline = $doc->getElementsByTagName('outline')->item(0)) {
            // Return a blogroll feed.
            return new Horde_Feed_Blogroll($doc->documentElement, $uri);
        }

        // $doc does not appear to be a valid feed of the supported
        // types.
        throw new Horde_Feed_Exception('Invalid or unsupported feed format: '
                                       . substr($doc->saveXML(), 0, 80) . '...');
    }

    /**
     * Reads a feed represented by $string.
     *
     * @param string $string The XML content of the feed.
     * @param string $uri The feed's URI location, if known.
     *
     * @throws Horde_Feed_Exception
     *
     * @return Horde_Feed_Base
     */
    public static function read($string, $uri = null)
    {
        // Load the feed as a DOMDocument object.
        libxml_use_internal_errors(true);
        $doc = new DOMDocument;
        $doc->recover = true;
        $loaded = $doc->loadXML($string);
        if (!$loaded) {
            $loaded = $doc->loadHTML($string);
            if (!$loaded) {
                throw new Horde_Feed_Exception('DOMDocument cannot parse XML: ', libxml_get_last_error());
            }
        }

        return self::create($doc);
    }

    /**
     * Read a feed located at $uri
     *
     * @param string $uri The URI to fetch the feed from.
     *
     * @throws Horde_Feed_Exception
     *
     * @return Horde_Feed_Base
     */
    public static function readUri($uri)
    {
        $client = self::getHttpClient();
        try {
            $response = $client->get($uri);
        } catch (Horde_Http_Client_Exception $e) {
            throw new Horde_Feed_Exception('Error reading feed: ' . $e->getMessage());
        }
        if ($response->code != 200) {
            throw new Horde_Feed_Exception('Unable to read feed, got response code ' . $response->code);
        }
        $feed = $response->getBody();
        return self::read($feed, $uri);
    }

    /**
     * Read a feed from $filename
     *
     * @param string $filename The location of the feed file on an accessible
     * filesystem or through an available stream wrapper.
     *
     * @throws Horde_Feed_Exception
     *
     * @return Horde_Feed_Base
     */
    public static function readFile($filename)
    {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument;
        $doc->recover = true;
        $loaded = $doc->load($filename);
        if (!$loaded) {
            $loaded = $doc->loadHTMLFile($filename);
            if (!$loaded) {
                throw new Horde_Feed_Exception('File could not be read or parsed: ', libxml_get_last_error());
            }
        }

        return self::create($doc);
    }

}
