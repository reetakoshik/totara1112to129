Totara changes:

* all fonts are included - there will be hopefully admin setting for font selection soon
* extra fallback fonts added
* latest version at the time of release - we need things like SVG support and any bug fixes we can get
* do not forget to update core thirdpartylibs.xml
* fixed the test page http://127.0.0.1/lib/tests/other/pdflibtestpage.php to say Totara

Extra patches:

* support for inline SVG images in TCPDF::openHTMLTagHandler()
* TL-16004 - Suppressed php 7.2 deprecation message on all uses of each()

Petr Skoda
