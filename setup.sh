#!/bin/bash -x
pear channel-discover pear.phpdoc.org
pear install phpdoc/phpDocumentor-alpha
/usr/bin/phpdoc run -f Bitly.class.php -t docs
