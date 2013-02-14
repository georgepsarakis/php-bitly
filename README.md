## Description
PHP wrapper class for the _bit.ly_ URL shortening service.
I tried to make it compact but flexible. It allows both formats, JSON and XML for the response. 
Keep in mind that the service has *rate limiting* which resets every hour or so.
You can read the complete documentation at http://dev.bitly.com .
Any feedback will be much appreciated.

## Requirements
You will need a username and an API key from bitly to test this class, which you can easily obtain after registering.
Documentation is generated with [phpDocumentor](http://www.phpdoc.org) and for the tests you will need the PHPUnit, which can also be installed with PEAR.
