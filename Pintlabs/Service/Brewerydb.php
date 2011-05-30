<?php
/**
 * Provides a service to simplify communication with the the Brewery DB API.
 *
 * @see    http://brewerydb.com/api/documentation
 * @author Garrison Locke - http://pintlabs.com - @gplocke
 *
 */
class Pintlabs_Service_Brewerydb
{
    /**
     * Base URL for the Brewerydb API
     *
     * @var string
     */
    const BASE_URL = 'http://www.brewerydb.com/api';

    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    
    /**
     * API key
     *
     * @var string
     */
    protected $_apiKey = '';

    /**
     * Response format
     *
     * @var string
     */
    protected $_format = 'json';

    /**
     * Stores the last parsed response from the server
     *
     * @var stdClass
     */
    protected $_lastParsedResponse = null;

    /**
     * Stores the last raw response from the server
     *
     * @var string
     */
    protected $_lastRawResponse = null;

    /**
     * Stores the last requested URI
     *
     * @var string
     */
    protected $_lastRequestUri = null;
    
    /**
     * Transfer type (POST, GET, PUT, DELETE)
     */
    protected $_transferType = self::GET;

    /**
     * Constructor
     *
     * @param string $apiKey Brewerydb API key
     */
    public function __construct($apiKey)
    {
        $this->_apiKey = (string) $apiKey;
    }
    
    /**
     * Sets the response format.  Must be either 'xml' or 'json'.  Will set
     * to 'json' by default if something invalid is specified.
     *
     * @return string
     */
    public function setFormat($format)
    {

        $format = strtolower($format);

        if ($format != 'xml' && $format != 'json') {
            $this->_format = 'json';
        } else {
            $this->_format = $format;
        }

        return $this;
    }
    
    /**
     * Gets the currently set response format.
     */
    public function getFormat()
    {
        return $this->_format;
    }

    /**
     * Returns a list of breweries with the given criteria
     *
     * @param int $page The page number to get (results are returned 50 at a time)
     * @param bool $metadata Whether or not to return metadata about the brewery
     * @param int $since Only return breweries created since the given date
     *                   requires [UTC date in YYYY-MM-DD format]
     *
     * @throws Pintlabs_Service_Brewerydb_Exception
     *
     * @return stdClass object from the request
     *
     */
    public function getBreweries($page = 1, $metadata = true, $since = null, $geo = false, $lat = null, $lng = null, $radius = 50, $units = 'miles')
    {
        if ($geo == true) {
            if (is_null($lat) || is_null($lng)) {
                require_once 'Bn/Service/Brewerydb/Exception.php';
                throw new Pintlabs_Service_Brewerydb_Exception('If doing a geo search, lat and lng values are required');
            }
        }

        $args = array(
            'page'     => $page,
            'metadata' => $metadata
        );

        if (!is_null($since)) {
            $args['since'] = $since;
        }

        if ($geo == true) {
            $args['geo']    = 1;
            $args['lat']    = $lat;
            $args['lng']    = $lng;
            $args['radius'] = $radius;
            $args['units']  = $units;
        }

        return $this->_request('breweries', $args);
    }
    
    /**
     * Returns a list of breweries with the given criteria
     *
     * @param int $boxes The array of bounding boxes
     * @param bool $metadata Whether or not to return metadata about the brewery
     *
     * @throws Pintlabs_Service_Brewerydb_Exception
     *
     * @return stdClass object from the request
     *
     */
    public function getBreweriesByBoundingBoxes($boxes, $metadata = true)
    {
        if (empty($boxes)) {
            require_once 'Bn/Service/Brewerydb/Exception.php';
            throw new Pintlabs_Service_Brewerydb_Exception('If doing a map route search, an array of lat and lng bounds are required');
        }
        
        $this->_transferType = self::POST;

        $args = array(
            'b'        => $boxes,
            'metadata' => $metadata
        );

        return $this->_request('maproute', $args);
    }
    

    /**
     * Returns info about a single brewery
     *
     * @param int $breweryId The id of the brewery to return
     * @param bool $metadata Whether or not to return metadata about the brewery
     *
     *
     * @throws Pintlabs_Service_Brewerydb_Exception
     *
     * @return stdClass object from the request
     *
     */
    public function getBrewery($breweryId = 1, $metadata = true)
    {

        $args = array(
            'metadata'  => $metadata
        );

        return $this->_request('breweries/' . $breweryId, $args);
    }


    /**
     * Returns the list of beer for a given brewery ID
     *
     * @param int $breweryId The id of the brewery to get the beers for
     * @param int $page The page number to get (results are returned 50 at a time)
     * @param bool $metadata Whether or not to return metadata about the brewery
     * @param int $since Only return breweries created since the given date
     *
     * @throws Pintlabs_Service_Brewerydb_Exception
     *
     * @return stdClass object from the request
     *
     */
    public function getBeersForBrewery($breweryId, $page = 1, $metadata = true, $since = null)
    {
        $args = array(
            'brewery_id' => $breweryId,
            'page'       => $page,
            'metadata'   => $metadata
        );

        if (!is_null($since)) {
            $args['since'] = $since;
        }

        return $this->_request('beers', $args);
    }

    /**
     * Returns a list of beers
     *
     * @param int $page The page number to get (results are returned 50 at a time)
     * @param bool $metadata Whether or not to return metadata about the brewery
     * @param int $since Only return breweries created since the given date
     *
     * @throws Pintlabs_Service_Brewerydb_Exception
     *
     * @return stdClass object from the request
     *
     */
    public function getAllBeers($page = 1, $metadata = true, $since = null)
    {
        $args = array(
            'page'       => $page,
            'metadata'   => $metadata
        );

        if (!is_null($since)) {
            $args['since'] = $since;
        }

        return $this->_request('beers', $args);
    }
    
    /**
     * Returns info about a single beer
     * 
     * @param int $beerId The id of the Beer to return
     * @param bool $metadata Whether or not to return metadata about the beer
     * 
     *
     * @throws Pintlabs_Service_Brewerydb_Exception
     *
     * @return array from the request
     *
     */
	public function getBeer($beerId = 1, $metadata = true)
    {

        $args = array(
            'metadata'  => $metadata
        );

        return $this->_request('beers/' . $beerId, $args);
    }

    /**
     * Returns the list of all beer styles
     *
     * @throws Pintlabs_Service_Brewerydb_Exception
     *
     * @return array from the request
     *
     */
    public function getAllStyles()
    {
        $args = array();

        return $this->_request('styles', $args);
    }


    /**
     * Returns a single beer style
     *
     * @param int $styleId The id of the style to get
     *
     * @throws Pintlabs_Service_Brewerydb_Exception
     *
     * @return array from the request
     *
     */
    public function getStyle($styleId)
    {
        $args = array();

        return $this->_request('styles/' . $styleId, $args);
    }

    /**
     * Returns the list of all beer categories
     *
     * @throws Pintlabs_Service_Brewerydb_Exception
     *
     * @return array from the request
     *
     */
    public function getAllCategories()
    {
        $args = array();

        return $this->_request('categories', $args);
    }


    /**
     * Returns a single beer category
     *
     * @param int $categoryId The id of the category to get
     *
     * @throws Pintlabs_Service_Brewerydb_Exception
     *
     * @return array from the request
     *
     */
    public function getCategory($categoryId)
    {
        $args = array();

        return $this->_request('categories/' . $categoryId, $args);
    }


    /**
     * Returns the list of all types of glassware
     *
     * @throws Pintlabs_Service_Brewerydb_Exception
     *
     * @return array from the request
     *
     */
    public function getAllGlassware()
    {
        $args = array();

        return $this->_request('glassware', $args);
    }

    /**
     * Returns info about a single type of glassware
     *
     * @param int $glasswareId The id of the glassware to get
     *
     * @throws Pintlabs_Service_Brewerydb_Exception
     *
     * @return array from the request
     *
     */
    public function getGlassware($glasswareId)
    {
        $args = array();

        return $this->_request('glassware/' . $glasswareId, $args);
    }

    /**
     * Searches the api for the given query
     *
     * @param int $query The query string to search for
     *
     * @throws Pintlabs_Service_Brewerydb_Exception
     *
     * @return array from the request
     *
     */
    public function search($query, $type = '', $metadata = true, $page = 1)
    {

        $type = strtolower($type);

        if ($type != '' && $type != 'beer' && $type != 'brewery') {
            require_once 'Bn/Service/Brewerydb/Exception.php';
            throw new Pintlabs_Service_Brewerydb_Exception('Type must be either "beer", "brewery", or empty');
        }

        $args = array(
            'q'        => $query,
            'page'     => $page,
            'metadata' => $metadata
        );

        if ($type != '') {
            $args['type'] = $type;
        }

        return $this->_request('search/', $args);
    }
    
    /**
     * Returns featured brewery and beer id
     *
     * @throws Pintlabs_Service_Brewerydb_Exception
     *
     * @return stdClass object from the request
     *
     */
    public function getFeatured()
    {
        $args = array();

        return $this->_request('featured/', $args);
    }


    /**
     * Sends a request using curl to the required endpoint
     *
     * @param string $endpoint The BreweryDb endpoint to use
     * @param array $args key value array of arguments
     *
     * @throws Pintlabs_Service_Brewerydb_Exception
     *
     * @return array
     */
    protected function _request($endpoint, $args)
    {
        $this->_lastRequestUri = null;
        $this->_lastRawResponse = null;
        $this->_lastParsedResponse = null;

        // Append the API key to the args passed in the query string
        $args['apikey'] = $this->_apiKey;
        $args['format'] = $this->_format;


        // Clean up the empty args so they'll return the API's default
        foreach ($args as $key => $value) {
            if ($value == '') {
                unset($args[$key]);
            }
        }        
        
        // Set curl options and execute the request
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                
        if ($this->_transferType == self::GET) {
                
            $this->_lastRequestUri = self::BASE_URL . '/' . $endpoint . '/?' . http_build_query($args);
            curl_setopt($ch, CURLOPT_URL, $this->_lastRequestUri);
            
        } else if ($this->_transferType == self::POST) {
            
            curl_setopt($ch, CURLOPT_POST, true);
            
            $this->_lastRequestUri = self::BASE_URL . '/' . $endpoint . '/';
            curl_setopt($ch, CURLOPT_URL, $this->_lastRequestUri);
            
            $body = http_build_query($args);
            
            curl_setopt ($ch, CURLOPT_POSTFIELDS, $body);
            
        } else if ($this->_transferType == self::PUT) {
            
            require_once 'Bn/Service/Brewerydb/Exception.php';
            throw new Pintlabs_Service_Brewerydb_Exception('PUT not supported');
            
        } else if ($this->_transferType == self::DELETE) {
            
            require_once 'Bn/Service/Brewerydb/Exception.php';
            throw new Pintlabs_Service_Brewerydb_Exception('DELETE not supported');
        }
        
        $this->_lastRawResponse = curl_exec($ch);
        
        if ($this->_lastRawResponse === false) {

            $this->_lastRawResponse = curl_error($ch);
            require_once 'Bn/Service/Brewerydb/Exception.php';
            throw new Pintlabs_Service_Brewerydb_Exception('CURL Error: ' . curl_error($ch));
        }

        curl_close($ch);
        
        // Response comes back as either JSON or XML, so we decode it into a stdClass object
        if ($args['format'] == 'xml') {
            $this->_lastParsedResponse = simplexml_load_string($this->_lastRawResponse);
            $this->_lastParsedResponse = self::_convertSimpleXmlElementObjectIntoArray($this->_lastParsedResponse);
        } else {
            $this->_lastParsedResponse = json_decode($this->_lastRawResponse, true);
        }

        // Server provides error messages in http_code and error vars.  If not 200, we have an error.
        if (isset($this->_lastParsedResponse['error'])) {
            require_once 'Bn/Service/Brewerydb/Exception.php';
            throw new Pintlabs_Service_Brewerydb_Exception('Brewerydb Service Error: ' .
                    $this->_lastParsedResponse['error']['message']);
        }
        
        // if it was xml, we'll remove the attributes that were attached to the 
        // root element if they exist as they were meaningless
        if ($this->_format == 'xml') {
            if (isset($this->_lastParsedResponse['@attributes'])) {
                unset($this->_lastParsedResponse['@attributes']);
            }
        }
        
        // reset the transfer type if it was changed for a particular request
        $this->_transferType = self::GET;

        return $this->getLastParsedResponse();
    }

    /**
     * Gets the last parsed response from the service
     *
     * @return null|array
     */
    public function getLastParsedResponse()
    {
        return $this->_lastParsedResponse;
    }

    /**
     * Gets the last raw response from the service
     *
     * @return null|json string|xml string
     */
    public function getLastRawResponse()
    {
        return $this->_lastRawResponse;
    }

    /**
     * Gets the last request URI sent to the service
     *
     * @return null|string
     */
    public function getLastRequestUri()
    {
        return $this->_lastRequestUri;
    }
    
    protected static function _convertSimpleXmlElementObjectIntoArray($simpleXmlElementObject, &$recursionDepth=0) {
      // Keep an eye on how deeply we are involved in recursion.

      // only allow recursion to get to 25 levels before we say nevermind.
      if ($recursionDepth > 25) {
        // Fatal error. Exit now.
        return(null);
      }

      if ($recursionDepth == 0) {
          
        if (!$simpleXmlElementObject instanceof SimpleXMLElement) {
          // If the external caller doesn't call this function initially
          // with a SimpleXMLElement object, return now.
          return(null);
        } else {
          // Store the original SimpleXmlElementObject sent by the caller.
          // We will need it at the very end when we return from here for good.
          $callerProvidedSimpleXmlElementObject = $simpleXmlElementObject;
        }
      } // End of if ($recursionDepth == 0) {


      if ($simpleXmlElementObject instanceof SimpleXMLElement) {
        // Get a copy of the simpleXmlElementObject
        $copyOfsimpleXmlElementObject = $simpleXmlElementObject;
        // Get the object variables in the SimpleXmlElement object for us to iterate.
        $simpleXmlElementObject = get_object_vars($simpleXmlElementObject);
      }


      // It needs to be an array of object variables.
      if (is_array($simpleXmlElementObject)) {
        // Initialize the result array.
        $resultArray = array();
        // Is the input array size 0? Then, we reached the rare CDATA text if any.
        if (count($simpleXmlElementObject) <= 0) {
          // Let us return the lonely CDATA. It could even be
          // an empty element or just filled with whitespaces.
          return (trim(strval($copyOfsimpleXmlElementObject)));
        }


        // Let us walk through the child elements now.
        foreach($simpleXmlElementObject as $key=>$value) {
          // When this block of code is commented, XML attributes will be
          // added to the result array.
          // Uncomment the following block of code if XML attributes are
          // NOT required to be returned as part of the result array.
          /*
          if((is_string($key)) && ($key == '@attributes')) {
            continue;
          }
          */

          // Let us recursively process the current element we just visited.
          // Increase the recursion depth by one.
          $recursionDepth++;
          $resultArray[$key] =
            self::_convertSimpleXmlElementObjectIntoArray($value, $recursionDepth);


          // Decrease the recursion depth by one.
          $recursionDepth--;
        } // End of foreach($simpleXmlElementObject as $key=>$value) {


        if ($recursionDepth == 0) {
          // That is it. We are heading to the exit now.
          // Set the XML root element name as the root [top-level] key of
          // the associative array that we are going to return to the caller of this
          // recursive function.
          $tempArray = $resultArray;
          $resultArray = array();
          $resultArray[$callerProvidedSimpleXmlElementObject->getName()] = $tempArray;
        }


        return ($resultArray);
      } else {
        // We are now looking at either the XML attribute text or
        // the text between the XML tags.
        return (trim(strval($simpleXmlElementObject)));
      } // End of else
    } // End of function convertSimpleXmlElementObjectIntoArray.
}
