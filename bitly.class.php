<?php
class bitly{
  const API_BASE_URL = 'http://api.bitly.com/v3/';
  public $apikey = '';
  public $username = '';
  public $format = 'json';
  public $response = null;
  public $raw_response = '';
  public $methods = array('shorten','expand','validate','countries','info'); 

  function __construct($user, $key){
    $this->apikey = $key;
    $this->username = $user; 
  }
  
  // unified version of the methods' execution process
  function execute_method($method, $params){
    $url = self::API_BASE_URL . $method . '/?' ;
    $url.= 'format=' . $this->format;
    foreach($params as $p => $v)
     $url .= '&' . $p . '=' . urlencode($v);
    $url.= '&' . $this->get_auth();
    $r = file_get_contents($url);
    $this->raw_response = $r;
    $this->analyze_response();  
  }
  
  function get_short_url($long_url){
    $params = array('longUrl' => $long_url);
    $this->execute_method('shorten', $params);
  }
  
  function get_long_url($short_url){
    $params = array('shortUrl' => $short_url);
    $this->execute_method('expand', $params);
  }
  
  // get the authorization part of the query string
  function get_auth(){
    return 'apiKey=' . urlencode($this->apikey) . '&login=' . urlencode($this->username);
  }
  
  // analyze the API response 
  // can handle both JSON and XML format
  function analyze_response(){
    $this->format = strtolower($this->format);
    if($this->format == 'json')
      $this->response = new bitlyResponse(json_decode($this->raw_response, true));
    else if($this->format == 'xml')
      $this->response = new bitlyResponse(json_decode(json_encode(simplexml_load_string($this->raw_response)),TRUE));
  }
}

/*
  A very short class to contain the processed response data.
  This may be expanded with some response processing functions
  in the future.
*/
class bitlyResponse{
  public $status_code = 0;
  public $status_txt = '';
  public $long_url = '';
  public $url = '';
  public $hash = '';
  
  function __construct($r){
    if(is_array($r['data']['entry']))
     $r['data'] = $r['data']['entry'];
    $this->status_txt = $r['status_txt'];
    $this->status_code = $r['status_code'];
    if(intval($this->status_code) != 200)
      throw new Exception('Bitly API Exception: Code '.$this->status_code . ', ' . $this->status_txt);
    $this->long_url = $r['data']['long_url'];
    $this->url = $r['data']['url'];
    $this->hash = $r['data']['hash'];
    if(array_key_exists('short_url', $r['data']))
      $this->short_url = $r['data']['short_url'];
    else
      $this->short_url = $this->url;
  }
}

?>
