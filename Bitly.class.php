<?php
class Bitly {
  const API_BASE_URL   = 'http://api.bitly.com/v3/';
  const API_KEY_LENGTH = 34;
  const CONVERSION_SHORT_2_LONG = 1;
  const CONVERSION_LONG_2_SHORT = 2;

  private   $apikey = '';
  private   $username = '';
  protected $format = 'json';
  private   $permitted_formats = array('json', 'xml');
  private   $is_cli = FALSE;
  public    $response = null;
  protected $raw_response = '';
  protected $permitted_methods = array('shorten', 'expand', 'validate', 'countries', 'info'); 
  protected $_MESSAGES = array(
                           'INVALID_FORMAT'     => 'Valid formats are JSON & XML.',
                           'INVALID_KEY'        => 'Invalid key -> ',
                           'INVALID_API_METHOD' => 'Method not allowed.' 
                         );
  
  function __construct($user = '', $key = '') {
    if ( ($user != '') && ($key != '') ) {
      $this->setApiKey($key);
      $this->setUsername($user);
    }
    // Interface detection for prettier printing
    $this->is_cli = php_sapi_name() == 'cli'; 
  }
  
  public function setFormat($format) {
    $format = strtolower($format);
    if ( in_array($format, $this->permitted_formats) ) {
      $this->format = $format;
      return $this;
    } else {
      throw new BitlyException($this->_MESSAGES['INVALID_FORMAT']);
    }
  }

  public function getApiKey() {
    return $this->apikey;
  }
  
  public function isApiMethod($method) {
    $method = strtolower($method);
    return in_array($method, $this->permitted_methods);
  }
   
  public function isValidApiKey($apikey) {
    $is_valid = preg_match('/[^a-z0-9\_]/i', $apikey, $matches) == 0;
    $is_valid = $is_valid && ( strlen($apikey) == self::API_KEY_LENGTH );
    return $is_valid;
  }

  public function setApiKey($apikey) {
    if ( $this->isValidApiKey($apikey) ) {
      $this->apikey = $apikey;
      return $this;
    } else
      throw new BitlyException($this->_MESSAGES['INVALID_KEY'] . $apikey);
  }
  
  /* any kind of validation?? */
  public function setUsername($username) {
    $this->username = $username;
  }
  
  public function getUsername() {
    return $this->username;
  }
  // unified version of the methods' execution process
  private function execute_method($method, $params){
    $method = strtolower($method);
    if ( !in_array($method, $this->permitted_methods) ) 
      throw new BitlyException($this->_MESSAGES['INVALID_API_METHOD']);
    $url = self::API_BASE_URL . $method . '/?' ;
    $url.= 'format=' . $this->format;
    foreach($params as $p => $v)
     $url .= '&' . $p . '=' . urlencode($v);
    $url.= '&' . $this->get_auth();
    $r = file_get_contents($url);
    $this->raw_response = $r;
    $this->analyze_response();
    return $this;
  }
  
  public function get_short_url($long_url) {
    $params = array('longUrl' => $long_url);
    $this->execute_method('shorten', $params);
    return $this->response;
  }
  
  public function get_long_url($short_url){
    $params = array('shortUrl' => $short_url);
    $this->execute_method('expand', $params);
    return $this->response;
  }
  
  // get the authorization part of the query string
  private function get_auth(){
    return 'apiKey=' . urlencode($this->apikey) . '&login=' . urlencode($this->username);
  }
  
  // analyze the API response 
  // can handle both JSON and XML format
  private function analyze_response() {
    $this->format = strtolower($this->format);
    $this->response = new BitlyResponse($this->raw_response, $this->format);
    return $this;
  }
  
  public function __toString() {
    return $this->response->__toString();
  }
  
}

/**
 * A very short class to contain the processed response data.
 * This may be expanded with some response processing functions
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
     if ( $format == 'json' )
      $r = json_decode($r, TRUE);
    else if( $format == 'xml' )
      $r = json_decode( json_encode( simplexml_load_string($r) ), TRUE);   
    
    $this->response = $r;
    
    if ( array_key_exists('entry', $this->response['data']) )
      $this->response['data'] = $this->response['data']['entry'];
    if ( array_key_exists('expand', $this->response['data']) ) 
       $this->response['data'] = $this->response['data']['expand'][0];
   
    $this->status_txt = $this->response['status_txt'];
    $this->status_code = $this->response['status_code'];

    if( intval($this->status_code) != 200 )
      throw new BitlyException('Bitly API Exception raised with Code '.$this->status_code . ', ' . $this->status_txt);

    $this->long_url = rtrim($this->response['data']['long_url'], '/');
    
    if ( array_key_exists('url', $this->response['data']) )
      $this->url = $this->response['data']['url'];
    
    if ( array_key_exists('hash', $this->response['data']) )
      $this->hash = $this->response['data']['hash'];

    if( array_key_exists('short_url', $this->response['data']) )
      $this->short_url = $this->response['data']['short_url'];
    else
      $this->short_url = $this->url;
  }

  public function __toString() {
    return 'Long URL: ' . $this->long_url  . ' => Short URL: ' . $this->short_url . "\n" . print_r($this->response, TRUE);
  }
}

class BitlyException extends Exception {
  private $_TEMPLATES = array(
                                'web' => "<pre>\n{MESSAGE}<br />\n</pre>\n",
                                'cli' => "{MESSAGE}\n"
                             );
  private $interface_template = '';

  function __construct($message) {
    parent::__construct($message);
    $this->interface_template = (php_sapi_name() == 'cli') ? 'cli' : 'web';
  }

  public function __toString() {
    $message =  str_replace('{MESSAGE}', $this->message, $_this->_TEMPLATES[$this->interface_template]);
    return __CLASS__ . ": " . $message;
  }
}
