<?php
require_once 'settings.php';
require_once 'bitly.class.php';

if($_SERVER['DOCUMENT_ROOT'] == '')
  $cli = true;
else
  $cli = false;

$bitly = new bitly(BITLY_USERNAME, BITLY_API_KEY);
$bitly->format = 'xml'; //can be xml or json
try{
  if(!$cli) print '<pre>';
  $bitly->get_short_url('http://gpsarakis.com');
  var_dump($bitly->response);
  $bitly->get_long_url('http://bit.ly/nlishO');
  var_dump($bitly->response);
  if(!$cli) print '</pre>';
}catch(Exception $e){
  print 'Exception raised -> ' . $e->getMessage() . ($cli ? "\n" : '<br />');
}

?>
