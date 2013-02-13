<?php
  require_once 'PHPUnit/Framework.php';
  require_once '../Bitly.class.php';
  require_once '../settings.php';

class BitlyTest extends PHPUnit_Framework_TestCase {

    protected $b;

    protected function setUp() {
      $this->b = new Bitly(BITLY_USERNAME, BITLY_API_KEY);
    }

    public function testGetApiKey() {
      $this->assertEquals(BITLY_API_KEY, $this->b->getApiKey());
    }
    
    public function testIsValidApiKey() {

      $this->assertTrue($this->b->isValidApiKey($this->b->getApiKey()));
      // Testing for illegal character
      $sample_key = str_repeat('a', 33) . '*';
      $this->assertFalse($this->b->isValidApiKey( $sample_key ));
      // Testing for key length
      $sample_key = str_repeat('a', 35);
      $this->assertFalse($this->b->isValidApiKey( $sample_key ));

    }
    
    public function testGetShortURL() {
      $this->assertEquals('http://bit.ly/nlishO', $this->b->get_short_url('http://gpsarakis.com')->url);
    }

    public function testGetLongURL() {
      $this->assertEquals('http://gpsarakis.com', $this->b->get_long_url('http://bit.ly/nlishO')->long_url);
    }

    public function testIsApiMethod() {
      $this->assertFalse($this->b->isApiMethod('a random method'));
      $this->assertTrue($this->b->isApiMethod('shorten'));
    }

    public function testException() {
        try {
          $this->b->setFormat('JSONP');
        } catch (BitlyException $expected) {
          return;
        } 
        $this->fail('Unexpected exception raised.');
    }     
}
