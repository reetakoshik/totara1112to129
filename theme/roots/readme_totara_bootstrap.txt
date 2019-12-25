Totara changes:

The bootstrap.js file has been amended with security changes from Bootstrap (which they had made available in 3.4.0).

These were:
 * fix(tab): remove xss
        - https://github.com/twbs/bootstrap/commit/13bf8aeae3db71e28af69782328c22215795c169
 * Fix XSS in Alert, Carousel, Collapse, Dropdown and Modal
        - https://github.com/twbs/bootstrap/commit/29f9237f735b90dbc89e003db0c62dec2db0b308
 * alert: Avoid calling jQuery('#') - this one isn't security-related but was a prior change that comes with the above.
        - https://github.com/twbs/bootstrap/commit/1956146787d686f5771ae51f7b96f3a3ad62ce09
 * Fix/xss issues on data attributes
        - https://github.com/twbs/bootstrap/commit/2a5ba23ce8f041f3548317acc992ed8a736b609d
 * Fix/XSS issues on popover 
        - https://github.com/twbs/bootstrap/commit/2c8abb9a4393addc5ffb39e649e09391c2fee701

If upgrading to the js file to match Bootstrap 3.4.1 or any latest release above that (e.g. 4.3.1 or above), you do not
need to reapply these changes as they will already be there.
