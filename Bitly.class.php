<?php
class Bitly {
  const API_BASE_URL		= 'http://api.bitly.com/v3/';
  const API_KEY_LENGTH		= 34;
  const CONVERSION_SHORT_2_LONG = 1;
  const CONVERSION_LONG_2_SHORT = 2;

  public    $Response		= null;
  
  private   $_APIKEY		= '';
  private   $_USERNAME		= '';
  private   $_PERMITTED_FORMATS = array('json', 'xml');
  private   $_IS_CLI		= FALSE;
  
  protected $_FORMAT		= 'json';
  protected $_RAW_RESPONSE	= '';
  protected $_PERMITTED_METHODS = array('shorten', 'expand', 'validate', 'countries', 'info'); 
  protected $_MESSAGES = array(
                                'INVALID_FORMAT'     => 'Valid formats are JSON & XML.',
                                'INVALID_KEY'        => 'Invalid key -> ',
                                'INVALID_API_METHOD' => 'Method not allowed.' 
                              );
  
  function __construct($user = '', $key = '') {
    if ( ( $user != '' ) && ( $key != '' ) ) {
      $this->setAPIKey($key);
      $this->setUsername($user);
    }
    // Interface detection for prettier printing
    $this->_IS_CLI = php_sapi_name() == 'cli'; 
  }
  
  public function setFormat($format) {
    $format = strtolower($format);
    if ( in_array( $format, $this->_PERMITTED_FORMATS ) ) {
      $this->_FORMAT = $format;
      return $this;
    } else {
      throw new BitlyException($this->_MESSAGES['INVALID_FORMAT']);
    }
  }

  public function getAPIKey() {
    return $this->_APIKEY;
  }
  
  public function isAPIMethod($method) {
    $method = strtolower($method);
    return in_array($method, $this->_PERMITTED_METHODS);
  }
   
  public function isValidAPIKey($api_key) {
    $is_valid = (preg_match('/[^a-z0-9\_]/i', $api_key, $matches) === 0);
    return $is_valid && ( strlen($api_key) === self::API_KEY_LENGTH );
  }

  public function setAPIKey($api_key) {
    if ( $this->isValidAPIKey($api_key) ) {
      $this->_APIKEY = $api_key;
      return $this;
    } else
      throw new BitlyException($this->_MESSAGES['INVALID_KEY'] . $apikey);
  }
  
  /*  TODO: add valid characters test */
  public function setUsername($username) {
    $this->_USERNAME = $username;
  }
  
  public function getUsername() {
    return $this->_USERNAME;
  }

  // unified version of the methods execution process
  private function fetch($method, $params) {
    $method = strtolower($method);
    if ( !$this->isAPIMethod($method) ) 
      throw new BitlyException($this->_MESSAGES['INVALID_API_METHOD']);
    $url  = self::API_BASE_URL . $method . '/?format=' . $this->_FORMAT;
    $url .= '&' . http_build_query($params) . '&' . $this->_get_auth();
    $this->_RAW_RESPONSE = file_get_contents($url);
    $this->_analyze_response();
    return $this;
  }
  
  public function get_short_url($long_url) {
    $params = array(
                     'longUrl' => $long_url
		   );
    $this->fetch('shorten', $params);
    return $this->Response;
  }
  
  public function get_long_url($short_url){
    $params = array( 
                     'shortUrl' => $short_url
		   );
    $this->fetch('expand', $params);
    return $this->Response;
  }
  
  // get the authorization part of the query string
  private function _get_auth(){
    return 'apiKey=' . urlencode($this->_APIKEY) . '&login=' . urlencode($this->_USERNAME);
  }
  
  /**
   * Analyze the API Response. Response class BitlyResponse
   *
   */
  // analyze the API response 
  // can handle both JSON and XML format
  private function _analyze_response() {
    $this->Response = new BitlyResponse($this->_RAW_RESPONSE, $this->_FORMAT);
    return $this;
  }
  
  public function __toString() {
    return $this->Response->__toString();
  }
  
}

/**
 * A very short class to contain the processed response data.
 * This may be expanded with some processing functions
 * in the future.
 *
 * @author George Psarakis
 */
class BitlyResponse {
  /**
   * @var public $status_code The response status code
   */
  public    $status_code = 0;
  /**
   * @var public $status_txt Response status text
   */
  public    $status_txt  = '';
  /**
   * @var public $long_url Long URL
   */
  public    $long_url    = '';
  /**
   * @var public $url Shortened URL
   */
  public    $url         = '';
  /**
   * @var public $hash The URL hash code
   */
  public    $hash        = '';
  /**
   * @var public $response Array storing the entire response
   */
  public    $response    = array();
  
  function __construct($r, $format) {
     try {
       if ( $format == 'json' ) {
	$this->response = json_decode($r, TRUE);
      } else if( $format == 'xml' ) {
	$this->response = json_decode( json_encode( simplexml_load_string($r) ), TRUE);   
      }
       
      if ( array_key_exists('entry', $this->response['data']) )
	$this->response['data'] = $this->response['data']['entry'];
      if ( array_key_exists('expand', $this->response['data']) ) 
	 $this->response['data'] = $this->response['data']['expand'][0];
     
      $this->status_txt = $this->response['status_txt'];
      $this->status_code = intval($this->response['status_code']);

      if( $this->status_code != 200 ) {
	$message = 'Bitly API Response Error with Code ' . $this->status_code . ' (' . $this->status_txt . ')';
	throw new BitlyException( $message );
      }

      $this->long_url = rtrim($this->response['data']['long_url'], '/');    
      $this->url = $this->_array_get($this->response['data'], 'url', '');
      $this->hash = $this->_array_get($this->response['data'], 'hash', '');
      $this->short_url = $this->_array_get($this->response['data'], 'short_url', $this->url);
    } catch ( Exception $e ) {
      print $e;
    }
  }
  
  private function _array_get($arr, $key, $default = NULL) {
    return array_key_exists($key, $arr) ? $arr[$key] : $default;
  }

  public function __toString() {
    return 'Long URL: ' . $this->long_url  . ' => Short URL: ' . $this->short_url . "\n" . print_r($this->response, TRUE);
  }
}

/**
 *  Exception Class
 * 
 *
 */
class BitlyException extends Exception {
  private $_TEMPLATES = array(
                                'web' => "<pre>\n{MESSAGE}<br />\n</pre>\n",
                                'cli' => "{MESSAGE}\n"
                             );
  private $_INTERFACE_TEMPLATE = '';

  function __construct($message) {
    parent::__construct($message);
    $this->_INTERFACE_TEMPLATE = ( php_sapi_name() == 'cli' ) ? 'cli' : 'web';
  }

  public function __toString() {
    $message =  str_replace('{MESSAGE}', $this->message, $_this->_TEMPLATES[$this->_INTERFACE_TEMPLATE]);
    return __CLASS__ . ": " . $message;
  }
}
