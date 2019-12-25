Description of phpCAS 1.3.4 library import

* downloaded from http://downloads.jasig.org/cas-clients/php/current/

* MDL-59456 phpCAS library has been patched because of an authentication bypass security vulnerability.

* TL-14975 remove __autoload() from auth/cas/CAS/CAS/Autoload.php

* TL-19330 convert continue statements in switches into breaks for PHP 7.3 compatibility:
   - auth/cas/CAS/CAS/Client.php