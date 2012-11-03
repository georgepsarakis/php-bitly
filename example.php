<?php
require_once 'settings.php';
require_once 'bitly.class.php';

try {
 $bitly = new Bitly();
 $bitly->setApiKey(BITLY_API_KEY);
 $bitly->setUsername(BITLY_USERNAME);
 $bitly->setFormat('xml'); //can be xml or json
 $bitly->get_short_url('http://gpsarakis.com');
  print $bitly;
  $bitly->get_long_url('http://bit.ly/nlishO');
  print $bitly;
} catch( BitlyException $e ) {
  print $e->getMessage();
}
