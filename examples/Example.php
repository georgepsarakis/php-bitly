<?php
require_once '../settings.php';
require_once '../Bitly.class.php';

try {
  $bitly = new Bitly();
  // set the api key and username from settings.php
  $bitly->setApiKey(BITLY_API_KEY);
  $bitly->setUsername(BITLY_USERNAME);
  $bitly->setFormat('xml'); //can be xml or json
  // shorten URL
  $bitly->get_short_url('http://gpsarakis.com');
  print $bitly;
  // get long URL from shortened version
  $bitly->get_long_url('http://bit.ly/nlishO');
  print $bitly;
} catch( BitlyException $e ) {
  print $e->getMessage();
}
