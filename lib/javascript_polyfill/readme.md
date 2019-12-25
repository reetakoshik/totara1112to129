Description of import of polyfill libraries
===========================================

See https://help.totaralearning.com/display/DEV/ES+6+functionality for more details.


ES6 Promise
-----------

Promise polyfill is required in IE11 when not using jQuery.

1. Go to https://github.com/stefanpenner/es6-promise
2. Override existing file with downloaded es6-promise.auto.js
3. Do not change any whitespace or formatting
4. Update version in /lib/thirdpartylibs.xml
5. Use totara/core/dev/fix_file_permissions.php to fix file permissions


window.fetch
------------

Requires promise polyfil in IE11.

1. Download release from https://github.com/github/fetch
2. Override current file with fetch.js
3. Do not change any whitespace or formatting
4. Update version in /lib/thirdpartylibs.xml
5. Copy LICENSE file if updated
6. Use totara/core/dev/fix_file_permissions.php to fix file permissions


Other polyfills for IE11
------------------------

The polyfill code was copied from https://developer.mozilla.org/en-US/docs/

* CustomEvent
* Element - closest, matches, remove
* Object - assign
* String - startsWith, endsWith

1. copy public domain code from the Mozilla site into lib/javascript_polyfill/src/other_ie11.js
2. update list above if new polyfill added 
