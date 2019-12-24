Please place all non minified versions of javascript files in here, then run grunt to minify all files

Jquery DataTables
------------------
Current version 1.10.7 and includes SetfilteringDelay (from
https://code.google.com/p/spu-fortaleza/source/browse/trunk/public/js/plugins/dataTables/jquery.dataTables.setFilteringDelay.js?r=466)

Also has the following change due to introduction of requirejs in Moodle 2.9 / Totara 8.0 (which caused the plugin to switch to AMD mode):
diff --git a/totara/core/js/lib/jquery.dataTables.js b/totara/core/js/lib/jquery.dataTables.js
index 4440ba6..4f296fc 100644
--- a/totara/core/js/lib/jquery.dataTables.js
+++ b/totara/core/js/lib/jquery.dataTables.js
@@ -29,15 +29,7 @@
 (function( factory ) {
        "use strict";
 
-       if ( typeof define === 'function' && define.amd ) {
-               // Define as an AMD module if possible
-               define( 'datatables', ['jquery'], factory );
-       }
-    else if ( typeof exports === 'object' ) {
-        // Node/CommonJS
-        module.exports = factory( require( 'jquery' ) );
-    }
-       else if ( jQuery && !jQuery.fn.dataTable ) {
+       if ( jQuery && !jQuery.fn.dataTable ) {
                // Define using browser globals otherwise
                // Prevent multiple instantiations if the script is loaded twice
                factory( jQuery );
