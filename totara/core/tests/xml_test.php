<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/*
 * ---- XML CODING GUIDELINES ----
 *
 * Read the following, to the end of this comment block, if you are adding/changing any code that uses XML
 * that may have come from an external source.
 *
 *
 * ---- Which XML library to use... ----
 *
 * There may be other reasons that determine which you need to use, for example XMLReader may use less
 * memory if large XML documents are used. But if there is no preference, DOMDocument seems to be the
 * most widely used (and therefore more chance of bugs being found) and has less gotchas than others in regard
 * to security. simplexml_load_string() should also be fine if this has the functionality you need.
 *
 *
 * ---- For any of the libraries... ----
 *
 * - It's safest to use the following code before loading XML:
 * $default = libxml_disable_entity_loader(false);
 *
 * - And then the following once you have finished completely with the XML:
 * libxml_disable_entity_loader($default);
 *
 * - libxml_disable_entity_loader(false) disables external entities, in fact seemingly any external file access
 * by the XML libraries whatsoever. This is known to have issues with not being thread-safe.
 * For example, on servers that use fcgi, this setting may disrupt other sites on that server.
 * Following the other recommendations in these guidleines should mean you don't have to use this function.
 * That's assuming that the methods only load external entities when certain options are set. Which seems to be
 * the case with recent versions of php and libxml.
 *
 * - Avoid writing code that relies on loading external entities. If for some reason, you really must allow it, it
 * has to be completely trusted. Do not allow it for any user-supplied documents.
 *
 *
 * ---- About the options/constants... ----
 *
 * - The constants most frequently used are here: http://php.net/manual/en/libxml.constants.php
 *
 * - LIBXML_NOENT allows the loading of external entities (assuming the entity loader is not disabled globally).
 * To be clear, it ALLOWS loading of external entities. It's name is misleading, it may be called NOENT because it
 * substitutes 'entities' with 'values', so there are NO ENTities left? Either way, this allows substitution of
 * external entities, so don't use it.
 *
 * - LIBXML_NONET will not disable entity substitution if it has already been enabled, unless they come from a network
 * location. So could still include local files, which is the most dangerous info to include.
 *
 * - LIBXML_NOXMLDECL is only for use when saving a document (as per the php documentation), that may even be for a feature
 * that has not been implemented anyway.  Do not use it when loading xml, as it has the same value as LIBXML_NOENT
 * and will therefore enable external entity substitution.
 *
 * - Be careful about which constant should be used for which function. Some methods use their own set of constants
 * and with different values for the same kind of settings (e.g. XMLReader:setParserProperty). Avoid these. Even if you
 * use the correct settings, the next developer may not. In the guidelines below for each library, I've suggested
 * which method is best to use for loading.
 *
 *
 * ---- If using the DOMDocument class... ----
 *
 * - Use the LoadXML or LoadHTML methods to load any external XML/HTML into the object. These take a string rather
 * than a file and so they still work for loading the initial XML while the entity loader is disabled globally.
 * (However, you will not be able to load dtd files, avoid using those as well anyway).
 *
 * - If you're only dealing with HTML, use the LoadHTML function. This won't allow external entities. But, that's
 * something that may not have always been the case in older versions of the library, so don't rely on it.
 *
 * - If you really must include external entities for some reason, set that option during the call to LoadXML
 * (i.e. use LIBXML_NOENT in the $options argument) - this then only applies to that one call to that
 * method.
 *
 * - Do not set the 'substituteEntities' property to true at any point. It's too easy to make false assumptions
 * about the behaviour of this setting.
 *
 * - The 'substituteEntities' property is not required for substituting html entities, e.g. '&lt;' for '<', so
 * this is good because you don't have to enable external loading to get this feature. But also remember that
 * this means escaped user input could be turned into valid html tags and therefore could be an XSS risk if you're
 * printing to the screen without further escaping/cleaning.
 *
 *
 * ---- If using the simplexml functions... ----
 *
 * - Use the simplexml_load_string() function. This takes a string rather than filename and therefore can work
 * with entities disabled globally, at least for loading the initial file.
 *  (However, you will not be able to load dtd files, avoid using those as well anyway).
 *
 * - If you want to use simplexml_import_dom(), make sure the DOMDocument object that you are supplying to it
 * is set up securely. The security here rests complete on the DOMDocument object, so if that's loading entities,
 * they will also be returned by this function.
 *
 * - If you must load external entities here, add them in the $options argument in the call to implexml_load_string().
 *
 *
 * ---- If using the XMLReader class... ----
 *
 * - Use the XMLReader::XML method. This takes a string rather than a filename so will work when entity loading
 * is disabled globally.
 *  (However, you will not be able to load dtd files, avoid using those as well anyway).
 *
 * - Be aware that external entity loading needs to be disabled when you call XML::read() for the disabling to be
 * effective. That's when it actually processes the file. You should really keep entity loading disabled on this until
 * you are finished reading the xml document.
 *
 * - If you must add any options, whether it's loading external entities or even just
 * adding an option to validate the file against the dtd. Add them as part of the $options argument in the call
 * to XMLReader::XML(). And use the correct constants (http://php.net/manual/en/libxml.constants.php), see next point.
 *
 * - When adding options to XMLReader::XML(), DO NOT use the 'XMLReader Parser Options' (constants such as XMLReader::VALIDATE),
 * as these have different values and you could inadvertently enable something else. Those are intended for the
 * XMLReader::setParserProperty() method.
 *
 * - Do not use the XMLReader::setParserProperty() method. These use a different set of constants as used elsewhere
 * which could so easily lead to the wrong options being set
 *
 */

/**
 * Class totara_core_xml_testcase
 *
 * Security issues tested here need to cover all ways that xml can be loaded from a potentially
 * user-defined source and all ways that external files could subsequently be loaded from that. This
 * will include:
 * - All functions/methods that load pre-defined xml.
 * - The use of options that allow loading of entities via the above functions. Specifically LIBXML_NOENT.
 * - Use of other options that allow access to files, e.g. LIBXML_DTDLOAD (should be for dtd files only).
 * - Enabling and disabling of the entity loading using libxml_disable_entity_loader.
 */
class totara_core_xml_testcase extends advanced_testcase {

    private $defaultloadersetting;

    public function setUp() {
        parent::setUp();

        $this->defaultloadersetting = libxml_disable_entity_loader(false);
    }

    public function tearDown() {
        libxml_disable_entity_loader($this->defaultloadersetting);
        $this->defaultloadersetting = null;
        parent::tearDown();
    }

    /*
     * DOMDocument tests
     */

    /**
     * Iterates through each node looking for $text.
     *
     * We could just search for where we think the text is using getElementsByTagName() or
     * an XPath, but this is a more reliable way of checking in case the text has ended up
     * somewhere unexpected.
     *
     * @param DOMDocument $dom
     * @param string $text - the text we're searching for.
     * @return bool true if text is found.
     */
    private function searchDOMDocument($dom, $text) {
        $found = false;
        /** @var DOMElement $childNode */
        foreach($dom->childNodes as $childNode) {
            if (strpos($childNode->nodeValue, $text) !== false) {
                $found = true;
                break;
            }
            if ($childNode->hasChildNodes()) {
                if ($found = $this->searchDOMDocument($childNode, $text)) {
                    break;
                }
            }
        }
        return $found;
    }

    /**
     * Tests DOMDocument::load().
     *
     * Tests that external entities can't be used to load files by default
     * in DOMDocument instances.
     */
    public function test_domdocument_load() {
        global $CFG;

        $dom = new DOMDocument();
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml");
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));

        // Using internal entities, no options need to be specified.
        $dom = new DOMDocument();
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/intentities.xml");
        $this->assertTrue($this->searchDOMDocument($dom, 'replacedtext'));

        // The following ensures that the above were returning false due to the
        // options not being provided and not some other external factor.
        $dom = new DOMDocument();
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", LIBXML_NOENT);
        $this->assertTrue($this->searchDOMDocument($dom, 'filetext'));
        // To validate our tests below, this shows that xml nodes were loaded.
        $this->assertGreaterThan(0, $dom->childNodes->length);

        // Run the above again with the entity loader disabled. The load() method simply doesn't work.
        libxml_disable_entity_loader(true);

        $dom = new DOMDocument();
        @$dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml");
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(0, $dom->childNodes->length); // This means that nothing was loaded.

        $dom = new DOMDocument();
        @$dom->load($CFG->dirroot . "/totara/core/tests/fixtures/intentities.xml");
        $this->assertFalse($this->searchDOMDocument($dom, 'replacedtext'));
        $this->assertEquals(0, $dom->childNodes->length); // This means that nothing was loaded.

        $dom = new DOMDocument();
        @$dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", LIBXML_NOENT);
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(0, $dom->childNodes->length); // This means that nothing was loaded.
    }

    /**
     * Tests DOMDocument::loadXML()
     *
     * Tests that external entities can't be used to load files by default
     * in DOMDocument instances when xml is supplied as a string.
     */
    public function test_domdocument_loadXML() {
        global $CFG;

        // We can't use a relative path in this case.
        $extxmlstring = file_get_contents($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml");
        $pathtotextfile = $CFG->dirroot . "/totara/core/tests/fixtures/test.txt";
        $pathtotextfile = str_replace('\\', '/', $pathtotextfile);
        $extxmlstring = str_replace("test.txt", $pathtotextfile, $extxmlstring);

        $dom = new DOMDocument();
        $dom->loadXML($extxmlstring);
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));

        $dom = new DOMDocument();
        $dom->loadXML(file_get_contents($CFG->dirroot . "/totara/core/tests/fixtures/intentities.xml"));
        $this->assertTrue($this->searchDOMDocument($dom, 'replacedtext'));

        // Validating the test.
        $dom = new DOMDocument();
        $dom->loadXML($extxmlstring, LIBXML_NOENT);
        $this->assertTrue($this->searchDOMDocument($dom, 'filetext'));
        // To validate our tests below, this shows that xml nodes were loaded.
        $this->assertGreaterThan(0, $dom->childNodes->length);

        // Run the above again with the entity loader disabled.
        // loadXML() only fails when the XML contains an external entity.
        libxml_disable_entity_loader(true);

        $dom = new DOMDocument();
        $dom->loadXML($extxmlstring);
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(2, $dom->childNodes->length); // The other nodes were loaded though.

        $dom = new DOMDocument();
        $dom->loadXML(file_get_contents($CFG->dirroot . "/totara/core/tests/fixtures/intentities.xml"));
        $this->assertTrue($this->searchDOMDocument($dom, 'replacedtext')); // Internal entities are still replaced.
        $this->assertEquals(2, $dom->childNodes->length);

        $dom = new DOMDocument();
        @$dom->loadXML($extxmlstring, LIBXML_NOENT);
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(0, $dom->childNodes->length); // This means that no nodes at all were loaded.
    }

    /**
     * Tests DOMDocument::loadHTML()
     *
     * Basically entities can't be declared in doctype for html, at least not according to the DOMDocument class.
     *
     * If we were to allow errors, we should see something along the lines of the following:
     * DOMDocument::loadHTML(): DOCTYPE improperly terminated in Entity
     */
    public function test_domdocument_loadhtml() {
        global $CFG;

        $dom = new DOMDocument();
        $dom->loadHTML(file_get_contents($CFG->dirroot . "/totara/core/tests/fixtures/noentities.html"));
        $this->assertTrue($this->searchDOMDocument($dom, 'normaltext'));

        $htmlstring = file_get_contents($CFG->dirroot . "/totara/core/tests/fixtures/extentities.html");
        $pathtotextfile = $CFG->dirroot . "/totara/core/tests/fixtures/test.txt";
        $pathtotextfile = str_replace('\\', '/', $pathtotextfile);
        $htmlstring = str_replace("test.txt", $pathtotextfile, $htmlstring);

        $dom = new DOMDocument();
        @$dom->loadHTML($htmlstring);
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(2, $dom->childNodes->length); // The other nodes were loaded.

        $dom = new DOMDocument();
        @$dom->loadHTML($htmlstring, LIBXML_NOENT);
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(2, $dom->childNodes->length); // The other nodes were loaded.

        $dom = new DOMDocument();
        $dom->substituteEntities = true;
        @$dom->loadHTML($htmlstring);
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(2, $dom->childNodes->length); // The other nodes were loaded.

        // Run the above again with the entity loader disabled.
        // The loadHTML() method will complain about an incorrect DOCTYPE declaration.
        libxml_disable_entity_loader(true);

        $dom = new DOMDocument();
        @$dom->loadHTML(file_get_contents($CFG->dirroot . "/totara/core/tests/fixtures/noentities.html"));
        $this->assertTrue($this->searchDOMDocument($dom, 'normaltext'));
        $this->assertEquals(2, $dom->childNodes->length); // The other nodes were loaded.

        $dom = new DOMDocument();
        @$dom->loadHTML($htmlstring);
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(2, $dom->childNodes->length); // The other nodes were loaded.

        $dom = new DOMDocument();
        @$dom->loadHTML($htmlstring, LIBXML_NOENT);
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(2, $dom->childNodes->length); // The other nodes were loaded.

        $dom = new DOMDocument();
        $dom->substituteEntities = true;
        @$dom->loadHTML($htmlstring);
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(2, $dom->childNodes->length); // The other nodes were loaded.
    }

    /**
     * Tests DOMDocument::loadHTMLFile().
     *
     * Tests that external entities can't be used to load files by default
     * in DOMDocument instances.
     */
    public function test_domdocument_loadhtmlfile() {
        global $CFG;

        $dom = new DOMDocument();
        $dom->loadHTMLFile($CFG->dirroot . "/totara/core/tests/fixtures/noentities.html");
        $this->assertTrue($this->searchDOMDocument($dom, 'normaltext'));

        $dom = new DOMDocument();
        @$dom->loadHTMLFile($CFG->dirroot . "/totara/core/tests/fixtures/extentities.html");
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(2, $dom->childNodes->length); // The other nodes were loaded.

        $dom = new DOMDocument();
        @$dom->loadHTMLFile($CFG->dirroot . "/totara/core/tests/fixtures/extentities.html", LIBXML_NOENT);
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(2, $dom->childNodes->length); // The other nodes were loaded.

        $dom = new DOMDocument();
        $dom->substituteEntities = true;
        @$dom->loadHTMLFile($CFG->dirroot . "/totara/core/tests/fixtures/extentities.html");
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(2, $dom->childNodes->length); // The other nodes were loaded.

        // Run the above again with the entity loader disabled. The loadHTMLFile() method simply doesn't work.
        libxml_disable_entity_loader(true);

        $dom = new DOMDocument();
        @$dom->loadHTMLFile($CFG->dirroot . "/totara/core/tests/fixtures/noentities.html");
        $this->assertFalse($this->searchDOMDocument($dom, 'normaltext'));
        $this->assertEquals(0, $dom->childNodes->length); // Did not load any nodes.

        $dom = new DOMDocument();
        @$dom->loadHTMLFile($CFG->dirroot . "/totara/core/tests/fixtures/extentities.html");
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(0, $dom->childNodes->length); // Did not load any nodes.

        $dom = new DOMDocument();
        @$dom->loadHTMLFile($CFG->dirroot . "/totara/core/tests/fixtures/extentities.html", LIBXML_NOENT);
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(0, $dom->childNodes->length); // Did not load any nodes.

        $dom = new DOMDocument();
        $dom->substituteEntities = true;
        @$dom->loadHTMLFile($CFG->dirroot . "/totara/core/tests/fixtures/extentities.html");
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(0, $dom->childNodes->length); // Did not load any nodes.
    }

    /**
     * Tests the 'substituteEntities' property in the DOMDocument class.
     *
     * I believe this essentially sets LIBXML_NOENT internally prior to loading, or does something similar. And that
     * isn't overridden if you set different options later.
     *
     * If you did want to load entities, the idea seems to be that you can choose this property
     * or use the LIBXML_NOENT option.
     *
     * We should avoid using this in live code as it could be easy to make false assumptions about its behaviour.
     */
    public function test_domdocument_substitute_entities() {
        global $CFG;

        $dom = new DOMDocument();
        // It defaults to false. Literally false and not a falsey value like null. So that means any default
        // behaviour we see in DOMDocument should be with this already set to false.
        $this->assertTrue(($dom->substituteEntities === false));

        // Below we test a range of combinations involving this property and the LIBXML_NOENT option.

        $dom = new DOMDocument();
        $dom->substituteEntities = false;
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml");
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));

        // We don't need to set LIBXML_NOENT if we've set substituteEntities to true.
        $dom = new DOMDocument();
        $dom->substituteEntities = true;
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml");
        $this->assertTrue($this->searchDOMDocument($dom, 'filetext'));

        $dom = new DOMDocument();
        $dom->substituteEntities = false;
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", LIBXML_NOENT);
        // The LIBXML_NOENT option will override substituteEntities = false.
        $this->assertTrue($this->searchDOMDocument($dom, 'filetext'));

        $dom = new DOMDocument();
        $dom->substituteEntities = true;
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", LIBXML_NOENT);
        $this->assertTrue($this->searchDOMDocument($dom, 'filetext'));

        $dom = new DOMDocument();
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", LIBXML_NOENT);
        // NOENT does not change the substituteEntities value.
        $this->assertTrue(($dom->substituteEntities === false));
        // substituteEntities has no effect once the XML is already loaded.
        $dom->substituteEntities = false;
        $this->assertTrue($this->searchDOMDocument($dom, 'filetext'));

        $dom = new DOMDocument();
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", LIBXML_NOENT);
        $dom->substituteEntities = true;
        $this->assertTrue($this->searchDOMDocument($dom, 'filetext'));

        // When we add options like LIBXML_NOENT, we're supplying a bitwise value. So if I specify another
        // constant that isn't supposed to allow entities, does it override substituteEntities?

        // First of all, test the other constant we'll use without anything that should allow entities.
        // This assures us that the binary digit for LIBXML_NOENT is equal to 0 in the other constant.
        $this->assertNotEquals((LIBXML_NOENT & LIBXML_PARSEHUGE), LIBXML_PARSEHUGE);
        $dom = new DOMDocument();
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", LIBXML_PARSEHUGE);
        // LIBXML_PARSEHUGE is for things like depth of xml and entity recursion,
        // but does not allow external entities on its own.
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));

        $dom = new DOMDocument();
        $dom->substituteEntities = true;
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", LIBXML_PARSEHUGE);
        // CAUTION: Setting a new option without LIBXML_NOENT does NOT reverse the effect of substituteEntities = true.
        $this->assertTrue($this->searchDOMDocument($dom, 'filetext'));

        // I shouldn't have to test something like this, but just in case...
        $dom = new DOMDocument();
        $dom->substituteEntities = true;
        $dom->substituteEntities = false;
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml");
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
    }

    /**
     * Some more tests with substituteEntities as it relates to html entities.
     */
    public function test_domdocument_substitute_entities_html() {
        global $CFG;

        $html = "<html><head></head><body><p>Text containing &gt; &lt; &quot;</p></body></html>";

        // HTML entities are replaced without setting substituteEntities.
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $this->assertFalse($this->searchDOMDocument($dom, '&gt;'));
        $this->assertTrue($this->searchDOMDocument($dom, '>'));
        $this->assertFalse($this->searchDOMDocument($dom, '&lt;'));
        $this->assertTrue($this->searchDOMDocument($dom, '<'));
        $this->assertFalse($this->searchDOMDocument($dom, '&quot;'));
        $this->assertTrue($this->searchDOMDocument($dom, '"'));

        // Entities are still replaced when set to true.
        $dom = new DOMDocument();
        $dom->substituteEntities = true;
        $dom->loadHTML($html);
        $this->assertFalse($this->searchDOMDocument($dom, '&gt;'));
        $this->assertTrue($this->searchDOMDocument($dom, '>'));
        $this->assertFalse($this->searchDOMDocument($dom, '&lt;'));
        $this->assertTrue($this->searchDOMDocument($dom, '<'));
        $this->assertFalse($this->searchDOMDocument($dom, '&quot;'));
        $this->assertTrue($this->searchDOMDocument($dom, '"'));

        // HTML entities are replaced without setting substituteEntities, even when loaded with XML function.
        $dom = new DOMDocument();
        $dom->loadXML($html);
        $this->assertFalse($this->searchDOMDocument($dom, '&gt;'));
        $this->assertTrue($this->searchDOMDocument($dom, '>'));
        $this->assertFalse($this->searchDOMDocument($dom, '&lt;'));
        $this->assertTrue($this->searchDOMDocument($dom, '<'));
        $this->assertFalse($this->searchDOMDocument($dom, '&quot;'));
        $this->assertTrue($this->searchDOMDocument($dom, '"'));

        // Entities are still replaced when set to true, even when loaded with XML function.
        $dom = new DOMDocument();
        $dom->substituteEntities = true;
        $dom->loadXML($html);
        $this->assertFalse($this->searchDOMDocument($dom, '&gt;'));
        $this->assertTrue($this->searchDOMDocument($dom, '>'));
        $this->assertFalse($this->searchDOMDocument($dom, '&lt;'));
        $this->assertTrue($this->searchDOMDocument($dom, '<'));
        $this->assertFalse($this->searchDOMDocument($dom, '&quot;'));
        $this->assertTrue($this->searchDOMDocument($dom, '"'));

        // But we should be careful when loading with XML, as doctypes called "html" don't get any special treatment
        // when loaded using the loadXML function.
        $dom = new DOMDocument();
        $dom->substituteEntities = true;
        $doctype = '<!DOCTYPE html [ <!ENTITY file SYSTEM "' . $CFG->dirroot . '/totara/core/tests/fixtures/test.txt"> ]>';
        $doctype = str_replace('\\', '/', $doctype);
        $newhtml = str_replace('Text', '&file;', $html);
        $dom->loadXML($doctype.$newhtml);
        $this->assertFalse($this->searchDOMDocument($dom, '&gt;'));
        $this->assertTrue($this->searchDOMDocument($dom, '>'));
        $this->assertFalse($this->searchDOMDocument($dom, '&lt;'));
        $this->assertTrue($this->searchDOMDocument($dom, '<'));
        $this->assertFalse($this->searchDOMDocument($dom, '&quot;'));
        $this->assertTrue($this->searchDOMDocument($dom, '"'));
        // Our trick worked, we got the contents of the file in an "html" document.
        $this->assertTrue($this->searchDOMDocument($dom, 'filetext'));

        // Similar tests are done elsewhere, but just so we have a clear inverse of our test above:
        // Using loadHTML means no external files are subbed in. Basically it doesn't allow external entities.
        $dom = new DOMDocument();
        $dom->substituteEntities = true;
        @$dom->loadHTML($doctype.$newhtml);
        $this->assertFalse($this->searchDOMDocument($dom, '&gt;'));
        $this->assertTrue($this->searchDOMDocument($dom, '>'));
        $this->assertFalse($this->searchDOMDocument($dom, '&lt;'));
        $this->assertTrue($this->searchDOMDocument($dom, '<'));
        $this->assertFalse($this->searchDOMDocument($dom, '&quot;'));
        $this->assertTrue($this->searchDOMDocument($dom, '"'));
        // No luck this time.
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
    }

    /**
     * Confirm that libxml_disable_entity_loader(true) does not prevent replacement of
     * html entities ('&quot;' for '"' or '&gt;' for '>' etc.)
     */
    public function test_domdocument_entity_loader_html() {

        libxml_disable_entity_loader(true);

        $html = "<html><head></head><body><p>Text containing &gt; &lt; &quot;</p></body></html>";

        $dom = new DOMDocument();
        $dom->loadXML($html);
        $this->assertFalse($this->searchDOMDocument($dom, '&gt;'));
        $this->assertTrue($this->searchDOMDocument($dom, '>'));
        $this->assertFalse($this->searchDOMDocument($dom, '&lt;'));
        $this->assertTrue($this->searchDOMDocument($dom, '<'));
        $this->assertFalse($this->searchDOMDocument($dom, '&quot;'));
        $this->assertTrue($this->searchDOMDocument($dom, '"'));
    }

    /**
     * Tests some specific cases using the LIBXML_DTDLOAD in DOMDocument.
     * This is intended for loading an external dtd file.
     */
    public function test_domdocument_dtdload_constant() {
        global $CFG;

        $dom = new DOMDocument();
        @$dom->load($CFG->dirroot . "/totara/core/tests/fixtures/withextentitydtd.xml");
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(2, $dom->childNodes->length); // The xml was still loaded (but without the dtd).

        $dom = new DOMDocument();
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/withextentitydtd.xml", LIBXML_DTDLOAD);
        // No errors are thrown as the dtd file could be loaded,
        // but it has not have been allowed to load the text file.
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(2, $dom->childNodes->length); // The other nodes in the xml were still loaded.

        $dom = new DOMDocument();
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/withextentitydtd.xml", LIBXML_DTDLOAD | LIBXML_NOENT);
        $this->assertTrue($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(2, $dom->childNodes->length);

        $dom = new DOMDocument();
        @$dom->load($CFG->dirroot . "/totara/core/tests/fixtures/falsedtd.xml", LIBXML_DTDLOAD);
        // This had a txt file where you'd expect a dtd file. It shouldn't have worked and loaded no nodes.
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(0, $dom->childNodes->length);

        $xmlstring = file_get_contents($CFG->dirroot . "/totara/core/tests/fixtures/withextentitydtd.xml");
        $pathtodtdfile = $CFG->dirroot . "/totara/core/tests/fixtures/withexternal.dtd";
        $pathtodtdfile = str_replace('\\', '/', $pathtodtdfile);
        $xmlstring = str_replace("withexternal.dtd", $pathtodtdfile, $xmlstring);

        $dom = new DOMDocument();
        $dom->loadXML($xmlstring, LIBXML_DTDLOAD);
        // The outcome should be the same as the similar case above using $dom->load()
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(2, $dom->childNodes->length);

        libxml_disable_entity_loader(true);

        $dom = new DOMDocument();
        // It fails to load the external dtd but the other nodes are loaded.
        @$dom->loadXML($xmlstring, LIBXML_DTDLOAD);
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(2, $dom->childNodes->length);

        $dom = new DOMDocument();
        // It fails to load the external dtd but the other nodes are loaded.
        @$dom->loadXML($xmlstring, LIBXML_DTDLOAD | LIBXML_NOENT);
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(2, $dom->childNodes->length);

        $falsedtdxmlstring = file_get_contents($CFG->dirroot . "/totara/core/tests/fixtures/falsedtd.xml");
        $pathtodtdfile = $CFG->dirroot . "/totara/core/tests/fixtures/test.txt";
        $pathtodtdfile = str_replace('\\', '/', $pathtodtdfile);
        $falsedtdxmlstring = str_replace("test.txt", $pathtodtdfile, $falsedtdxmlstring);

        $dom = new DOMDocument();
        @$dom->loadXML($falsedtdxmlstring, LIBXML_DTDLOAD);
        // This had a txt file where you'd expect a dtd file. It should have seen it as an external entity and
        // not loaded it.
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
        $this->assertEquals(2, $dom->childNodes->length);
    }

    /*
     * SimpleXML tests
     */

    /**
     * @param stdClass|SimpleXMLElement $xml - an object returned by a simplexml function.
     * @param string $text - the text we're searching for.
     * @return bool true is $text is found.
     */
    private function searchSimpleXML($xml, $text) {
        $found = false;
        foreach ($xml->childnode as $childNode) {
            if (strpos($childNode, $text) !== false) {
                $found = true;
                break;
            }
            if (!empty($xml->childnode)) {
                if ($found = $this->searchSimpleXML($childNode, $text)) {
                    break;
                }
            }
        }
        return $found;
    }

    /**
     * Tests simplexml_load_file().
     *
     * Tests that external entities can't be used to load files by default
     * via simplexml functions.
     */
    public function test_simplexml_load_file() {
        global $CFG;

        $xml = simplexml_load_file($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml");
        $this->assertFalse($this->searchSimpleXML($xml, 'filetext'));

        // To make sure the above tests are valid.
        $xml = simplexml_load_file($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", null, LIBXML_NOENT);
        $this->assertTrue($this->searchSimpleXML($xml, 'filetext'));

        $xml = simplexml_load_file($CFG->dirroot . "/totara/core/tests/fixtures/noentities.xml");
        $this->assertNotEmpty($xml);

        // Loading files simply won't work after this, entities or not.
        libxml_disable_entity_loader(true);

        $xml = @simplexml_load_file($CFG->dirroot . "/totara/core/tests/fixtures/noentities.xml");
        $this->assertEmpty($xml);

        $xml = @simplexml_load_file($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml");
        $this->assertEmpty($xml);

        $xml = @simplexml_load_file($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", null, LIBXML_NOENT);
        $this->assertEmpty($xml);
    }

    /**
     * Tests simplexml_import_dom().
     *
     * An extra case for simplexml_import_dom. Note that this function doesn't have its own
     * options parameter and inherits from the options supplied to DOMDocument.
     *
     * Still, files should only be included if a relevant option was supplied to DOMDocument.
     */
    public function test_simplexml_import_dom() {
        global $CFG;

        $dom = new DOMDocument();
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml");
        $xml = simplexml_import_dom($dom);
        $this->assertFalse($this->searchSimpleXML($xml, 'filetext'));

        // Add the option to include external files with entities. This only needs to be added to the DOMDocument.
        $dom = new DOMDocument();
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", LIBXML_NOENT);
        $xml = simplexml_import_dom($dom);
        $this->assertTrue($this->searchSimpleXML($xml, 'filetext'));

        // Import entities with DOMDocument. They can then be loaded into a simplexml object, even after
        // entity loading is disabled.
        $dom = new DOMDocument();
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", LIBXML_NOENT);

        libxml_disable_entity_loader(true);
        $xml = simplexml_import_dom($dom);
        $this->assertTrue($this->searchSimpleXML($xml, 'filetext'));
    }

    /**
     * Tests simplexml_load_string().
     *
     * Tests that external entities can't be used to load files by default
     * via simplexml functions.
     */
    public function test_simplexml_load_string() {
        global $CFG;

        $noentities = file_get_contents($CFG->dirroot . "/totara/core/tests/fixtures/noentities.xml");
        $extentities = file_get_contents($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml");
        $pathtotextfile = $CFG->dirroot . "/totara/core/tests/fixtures/test.txt";
        $pathtotextfile = str_replace('\\', '/', $pathtotextfile);
        $extentities = str_replace("test.txt", $pathtotextfile, $extentities);

        $xml = simplexml_load_string($extentities);
        $this->assertFalse($this->searchSimpleXML($xml, 'filetext'));

        // To make sure the above tests are valid.
        $xml = simplexml_load_string($extentities, null, LIBXML_NOENT);
        $this->assertTrue($this->searchSimpleXML($xml, 'filetext'));

        $xml = simplexml_load_string($noentities);
        $this->assertNotEmpty($xml);

        // The load string function itself will continue to work.
        libxml_disable_entity_loader(true);

        $xml = simplexml_load_string($noentities);
        $this->assertNotEmpty($xml);

        // So this file is still loaded, it just doesn't get the external file.
        $xml = simplexml_load_string($extentities);
        $this->assertNotEmpty($xml);
        $this->assertFalse($this->searchSimpleXML($xml, 'filetext'));

        // But if we add the NOENT option, it doesn't load the file at all.
        $xml = @simplexml_load_string($extentities, null, LIBXML_NOENT);
        $this->assertEmpty($xml);
    }

    /**
     * Tests some specific cases using the LIBXML_DTDLOAD in SimpleXML.
     * This is intended for loading an external dtd file.
     */
    public function test_simplexml_load_string_dtdload_constant() {
        global $CFG;

        $xmlstring = file_get_contents($CFG->dirroot . "/totara/core/tests/fixtures/withextentitydtd.xml");
        $pathtodtdfile = $CFG->dirroot . "/totara/core/tests/fixtures/withexternal.dtd";
        $pathtodtdfile = str_replace('\\', '/', $pathtodtdfile);
        $xmlstring = str_replace("withexternal.dtd", $pathtodtdfile, $xmlstring);

        $falsedtd = file_get_contents($CFG->dirroot . "/totara/core/tests/fixtures/falsedtd.xml");
        $pathtotextfile = $CFG->dirroot . "/totara/core/tests/fixtures/test.txt";
        $pathtotextfile = str_replace('\\', '/', $pathtotextfile);
        $falsedtd = str_replace("withexternal.dtd", $pathtotextfile, $falsedtd);

        // Will throw an error saying that &entitytext; is not defined (that is defined in the dtd file, but it can't
        // load that without the constant. Still, it does load the other nodes in the file though.
        $xml = @simplexml_load_string($xmlstring);
        $this->assertNotEmpty($xml);
        $this->assertFalse($this->searchSimpleXML($xml, 'filetext'));
        $this->assertTrue($this->searchSimpleXML($xml, 'normaltext'));

        $xml = simplexml_load_string($xmlstring, null, LIBXML_DTDLOAD);
        $this->assertNotEmpty($xml);
        $this->assertFalse($this->searchSimpleXML($xml, 'filetext'));
        $this->assertTrue($this->searchSimpleXML($xml, 'normaltext'));

        $xml = simplexml_load_string($xmlstring, null, LIBXML_DTDLOAD | LIBXML_NOENT);
        $this->assertNotEmpty($xml);
        $this->assertTrue($this->searchSimpleXML($xml, 'filetext'));
        $this->assertTrue($this->searchSimpleXML($xml, 'normaltext'));

        // Now try the xml where we try to trick it into loading a text file as a dtd. It throws an error but
        // everything else still works.
        $xml = @simplexml_load_string($falsedtd, null, LIBXML_DTDLOAD);
        $this->assertNotEmpty($xml);
        $this->assertFalse($this->searchSimpleXML($xml, 'filetext'));
        $this->assertTrue($this->searchSimpleXML($xml, 'normaltext'));

        libxml_disable_entity_loader(true);

        // Errors because it can't load the dtd with the entity loader disabled.
        $xml = @simplexml_load_string($xmlstring, null, LIBXML_DTDLOAD);
        $this->assertNotEmpty($xml);
        $this->assertFalse($this->searchSimpleXML($xml, 'filetext'));
        $this->assertTrue($this->searchSimpleXML($xml, 'normaltext'));

        // Errors because it can't load the dtd with the entity loader disabled.
        $xml = @simplexml_load_string($xmlstring, null, LIBXML_DTDLOAD | LIBXML_NOENT);
        $this->assertNotEmpty($xml);
        $this->assertFalse($this->searchSimpleXML($xml, 'filetext'));
        $this->assertTrue($this->searchSimpleXML($xml, 'normaltext'));

        $xml = @simplexml_load_string($falsedtd, null, LIBXML_DTDLOAD);
        $this->assertNotEmpty($xml);
        $this->assertFalse($this->searchSimpleXML($xml, 'filetext'));
        $this->assertTrue($this->searchSimpleXML($xml, 'normaltext'));
    }

    /*
     * XMLReader tests.
     */

    /**
     * Reads XML looking for a string.
     *
     * You can't use this function twice in a row as there doesn't seem to be any clear way to reset
     * the pointer back to the first element.
     *
     * If you want to look for a second string on the one xml object. Use $xml->close() and then
     * $xml->open($file, null, $options) or $xml->XML($string, null, $options) again to load as new.
     *
     * @param XMLReader $xml
     * @param string $text - the text we're searching for.
     * @return bool true if $text is found.
     */
    private function searchXMLReader($xml, $text) {
        $found = false;
        while ($xml->read()) {
            if (strpos($xml->readString(), $text) !== false) {
                $found = true;
                break;
            }
        }

        return $found;
    }

    /**
     * Tests XMLReader::open().
     *
     * Tests that external entities can't be used to load files by default
     * via XMLReader.
     */
    public function test_xml_reader_open() {
        global $CFG;

        $xml = new XMLReader();
        $outcome = $xml->open($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml");
        $this->assertTrue($outcome);
        $this->assertFalse($this->searchXMLReader($xml, 'filetext'));

        // Now testing with defaults overridden.
        $xml = new XMLReader();
        $outcome = $xml->open($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", null, LIBXML_NOENT);
        $this->assertTrue($outcome);
        $this->assertTrue($this->searchXMLReader($xml, 'filetext'));

        libxml_disable_entity_loader(true);

        $xml = new XMLReader();
        $outcome = @$xml->open($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml");
        $this->assertFalse($outcome);

        $xml = new XMLReader();
        $outcome = @$xml->open($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", null, LIBXML_NOENT);
        $this->assertFalse($outcome);
    }

    /**
     * Tests XMLReader::xml().
     *
     * Tests that external entities can't be used to load files by default
     * via XMLReader.
     */
    public function test_xml_reader_xml() {
        global $CFG;

        $extentities = file_get_contents($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml");
        $pathtotextfile = $CFG->dirroot . "/totara/core/tests/fixtures/test.txt";
        $pathtotextfile = str_replace('\\', '/', $pathtotextfile);
        $extentities = str_replace("test.txt", $pathtotextfile, $extentities);

        $xml = new XMLReader();
        $outcome = $xml->XML($extentities);
        $this->assertTrue($outcome);
        $this->assertFalse($this->searchXMLReader($xml, 'filetext'));

        // Now testing with defaults overridden.
        $xml = new XMLReader();
        $outcome = $xml->XML($extentities, null, LIBXML_NOENT);
        $this->assertTrue($outcome);
        $this->assertTrue($this->searchXMLReader($xml, 'filetext'));

        libxml_disable_entity_loader(true);

        $xml = new XMLReader();
        $outcome = @$xml->XML($extentities);
        $this->assertTrue($outcome);
        $this->assertFalse($this->searchXMLReader($xml, 'filetext'));

        $xml = new XMLReader();
        $outcome = @$xml->XML($extentities, null, LIBXML_NOENT);
        $this->assertTrue($outcome);
        @$this->assertFalse($this->searchXMLReader($xml, 'filetext'));

        // Notice how we had to suppress the error when reading, not loading in the above test.
        // Next we'll load the xml while the entity loader is disabled, but then enable it before we start reading.

        $xml = new XMLReader();
        $outcome = @$xml->XML($extentities, null, LIBXML_NOENT);
        $this->assertTrue($outcome);
        libxml_disable_entity_loader(false);
        // So the entity loader must disabled during reading as well when using XMLReader.
        $this->assertTrue($this->searchXMLReader($xml, 'filetext'));
    }

    /**
     * Tests some specific cases using the LIBXML_DTDLOAD in XMLReader.
     * This is intended for loading an external dtd file.
     */
    public function test_xmlreader_xml_dtdload_constant() {
        global $CFG;

        $xmlstring = file_get_contents($CFG->dirroot . "/totara/core/tests/fixtures/withextentitydtd.xml");
        $pathtodtdfile = $CFG->dirroot . "/totara/core/tests/fixtures/withexternal.dtd";
        $pathtodtdfile = str_replace('\\', '/', $pathtodtdfile);
        $xmlstring = str_replace("withexternal.dtd", $pathtodtdfile, $xmlstring);

        $falsedtd = file_get_contents($CFG->dirroot . "/totara/core/tests/fixtures/falsedtd.xml");
        $pathtotextfile = $CFG->dirroot . "/totara/core/tests/fixtures/test.txt";
        $pathtotextfile = str_replace('\\', '/', $pathtotextfile);
        $falsedtd = str_replace("withexternal.dtd", $pathtotextfile, $falsedtd);

        // This will throw errors about entitytext not being defined both when its loaded and when we try to read it.
        $xml = new XMLReader();
        $outcome = $xml->XML($xmlstring);
        $this->assertTrue($outcome);
        $this->assertFalse(@$this->searchXMLReader($xml, 'filetext'));
        $xml->close();
        $xml->XML($xmlstring);
        $this->assertTrue(@$this->searchXMLReader($xml, 'normaltext'));

        $xml = new XMLReader();
        $outcome = $xml->XML($xmlstring, null, LIBXML_DTDLOAD);
        $this->assertTrue($outcome);
        $this->assertFalse($this->searchXMLReader($xml, 'filetext'));
        $xml->close();
        $xml->XML($xmlstring, null, LIBXML_DTDLOAD);
        $this->assertTrue($this->searchXMLReader($xml, 'normaltext'));

        $xml = new XMLReader();
        $outcome = $xml->XML($xmlstring, null, LIBXML_DTDLOAD | LIBXML_NOENT);
        $this->assertTrue($outcome);
        $this->assertTrue($this->searchXMLReader($xml, 'filetext'));

        // Now try the xml where we try to trick it into loading a text file as a dtd.
        $xml = new XMLReader();
        $outcome = $xml->XML($falsedtd, null, LIBXML_DTDLOAD | LIBXML_NOENT);
        $this->assertTrue($outcome);
        $this->assertFalse(@$this->searchXMLReader($xml, 'filetext'));
        $xml->close();
        $xml->XML($falsedtd);
        $this->assertTrue(@$this->searchXMLReader($xml, 'normaltext'));

        libxml_disable_entity_loader(true);

        $xml = new XMLReader();
        $outcome = $xml->XML($xmlstring, null, LIBXML_DTDLOAD);
        $this->assertTrue($outcome);
        $this->assertFalse(@$this->searchXMLReader($xml, 'filetext'));
        $xml->close();
        $xml->XML($xmlstring, null, LIBXML_DTDLOAD);
        $this->assertTrue(@$this->searchXMLReader($xml, 'normaltext'));

        // No error when we load the string on this one, but there is when we try to read it.
        $xml = new XMLReader();
        $outcome = $xml->XML($xmlstring, null, LIBXML_DTDLOAD | LIBXML_NOENT);
        $this->assertTrue($outcome);
        $this->assertFalse(@$this->searchXMLReader($xml, 'filetext'));
        $xml->close();
        $xml->XML($xmlstring, null, LIBXML_DTDLOAD | LIBXML_NOENT);
        $this->assertTrue(@$this->searchXMLReader($xml, 'normaltext'));

        // Now try the xml where we try to trick it into loading a text file as a dtd.
        $xml = new XMLReader();
        $outcome = $xml->XML($falsedtd, null, LIBXML_DTDLOAD | LIBXML_NOENT);
        $this->assertTrue($outcome);
        $this->assertFalse(@$this->searchXMLReader($xml, 'filetext'));
        $xml->close();
        $xml->XML($falsedtd, null, LIBXML_DTDLOAD | LIBXML_NOENT);
        $this->assertTrue(@$this->searchXMLReader($xml, 'normaltext'));
    }

    /**
     * XMLReader::setParserProperty takes options for whether to load dtds and substitute entities
     * and a bool for whether you want them enabled or not.
     *
     * This uses it's own set of constants, e.g. XMLReader::SUBST_ENTITIES instead of LIBXML_NOENT.
     * They have different values, for example the values for entities and dtds seem to have been
     * swapped around.
     */
    public function test_xmlreader_setparserproperty() {
        global $CFG;

        $extentities = file_get_contents($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml");
        $pathtotextfile = $CFG->dirroot . "/totara/core/tests/fixtures/test.txt";
        $pathtotextfile = str_replace('\\', '/', $pathtotextfile);
        $extentities = str_replace("test.txt", $pathtotextfile, $extentities);

        $this->assertNotEquals(LIBXML_NOENT, XMLReader::SUBST_ENTITIES);
        $this->assertEquals(LIBXML_DTDLOAD, XMLReader::SUBST_ENTITIES);

        $xml = new XMLReader();
        $outcome = $xml->XML($extentities);
        $this->assertTrue($outcome);
        $xml->setParserProperty(XMLReader::SUBST_ENTITIES, true);
        $this->assertTrue($this->searchXMLReader($xml, 'filetext'));

        $xml = new XMLReader();
        $outcome = $xml->XML($extentities);
        $this->assertTrue($outcome);
        $xml->setParserProperty(XMLReader::SUBST_ENTITIES, false);
        $this->assertFalse($this->searchXMLReader($xml, 'filetext'));

        $xml = new XMLReader();
        $outcome = $xml->XML($extentities, null, LIBXML_NOENT);
        $this->assertTrue($outcome);
        $xml->setParserProperty(XMLReader::SUBST_ENTITIES, false);
        $this->assertFalse($this->searchXMLReader($xml, 'filetext'));

        $xml = new XMLReader();
        $outcome = $xml->XML($extentities);
        $this->assertTrue($outcome);
        $xml->setParserProperty(XMLReader::LOADDTD, true);
        $this->assertFalse($this->searchXMLReader($xml, 'filetext'));

        $xml = new XMLReader();
        $outcome = $xml->XML($extentities);
        $this->assertTrue($outcome);
        $xml->setParserProperty(XMLReader::LOADDTD, false);
        $this->assertFalse($this->searchXMLReader($xml, 'filetext'));

        $xml = new XMLReader();
        $outcome = $xml->open($CFG->dirroot . "/totara/core/tests/fixtures/withextentitydtd.xml");
        $this->assertTrue($outcome);
        $xml->setParserProperty(XMLReader::LOADDTD, true);
        $this->assertFalse($this->searchXMLReader($xml, 'filetext'));

        $xml = new XMLReader();
        $outcome = $xml->open($CFG->dirroot . "/totara/core/tests/fixtures/withextentitydtd.xml");
        $this->assertTrue($outcome);
        $xml->setParserProperty(XMLReader::LOADDTD, true);
        $xml->setParserProperty(XMLReader::SUBST_ENTITIES, true);
        $this->assertTrue($this->searchXMLReader($xml, 'filetext'));

        $xml = new XMLReader();
        $outcome = $xml->open($CFG->dirroot . "/totara/core/tests/fixtures/falsedtd.xml");
        $this->assertTrue($outcome);
        $xml->setParserProperty(XMLReader::LOADDTD, true);
        $this->assertFalse(@$this->searchXMLReader($xml, 'filetext'));
    }

    /*
     * XML Parser tests.
     */

    /**
     * For searching arrays generated from XML Parser to find a string.
     * @param array $xml
     * @param string $text
     */
    public function search_parsed_xml($xml, $text) {
        $found = false;
        foreach($xml as $childnode) {
            if (is_array($childnode)) {
                if ($found = $this->search_parsed_xml($childnode, $text)) {
                    break;
                }
            } else if (strpos($childnode, $text) !== false) {
                $found = true;
                break;
            }
        }

        return $found;
    }

    /**
     * The XML Parser functions do not include any option to allow external entities via a setting on its own.
     *
     * But you can change the handlers for different aspects of the xml parsing, for example there
     * is xml_set_external_entity_ref_handler, xml_set_unparsed_entity_decl_handler or xml_set_element_handler.
     * Whether setting these creates a vulnerability depends on what (probably custom) function you set as the handler.
     */
    public function test_xml_parser() {
        global $CFG;

        $extentities = file_get_contents($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml");
        $pathtotextfile = $CFG->dirroot . "/totara/core/tests/fixtures/test.txt";
        $pathtotextfile = str_replace('\\', '/', $pathtotextfile);
        $extentities = str_replace("test.txt", $pathtotextfile, $extentities);

        $parsed = array();
        $xmlparser = xml_parser_create('UTF-8');
        xml_parse_into_struct($xmlparser, $extentities, $parsed);
        $this->assertFalse($this->search_parsed_xml($parsed, 'filetext'));
        $this->assertTrue($this->search_parsed_xml($parsed, 'normaltext'));

        $parsed = array();
        $xmlparser = xml_parser_create('UTF-8');
        xml_parser_set_option($xmlparser, XML_OPTION_CASE_FOLDING, 1);
        xml_parse_into_struct($xmlparser, $extentities, $parsed);
        $this->assertFalse($this->search_parsed_xml($parsed, 'filetext'));
        $this->assertTrue($this->search_parsed_xml($parsed, 'normaltext'));

        $parsed = array();
        $xmlparser = xml_parser_create('UTF-8');
        xml_parser_set_option($xmlparser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($xmlparser, $extentities, $parsed);
        $this->assertFalse($this->search_parsed_xml($parsed, 'filetext'));
        $this->assertTrue($this->search_parsed_xml($parsed, 'normaltext'));

        $parsed = array();
        $xmlparser = xml_parser_create('UTF-8');
        xml_parser_set_option($xmlparser, XML_OPTION_SKIP_TAGSTART, 1);
        xml_parse_into_struct($xmlparser, $extentities, $parsed);
        $this->assertFalse($this->search_parsed_xml($parsed, 'filetext'));
        $this->assertTrue($this->search_parsed_xml($parsed, 'normaltext'));
    }

    /*
     * CONSTANTS tests
     */

    /**
     * Test some specific aspects of the LIBXML_NOENT constant.
     */
    public function test_noent_constant() {
        global $CFG;

        $dom = new DOMDocument();
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", LIBXML_NOENT);
        $this->assertTrue($this->searchDOMDocument($dom, 'filetext'));

        // Note that I'm not creating a new DOMDocument. I'm loading xml again (which should drop the old xml) and
        // I'm not using the constant this time. Did it persist from when I set it last?
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml");
        // No, it doesn't. So as long as you haven't set substituteEntities = true, you need to specify the option
        // on each call (as would be expected).
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
    }

    /**
     * Test some specific aspects of the LIBXML_NONET constant.
     *
     * The documentation describes this as disabling network access when loading documents. We won't attempt
     * any network access in this test, but will confirm that it doesn't disable local file access.
     */
    public function test_nonet_constant() {
        global $CFG;

        // It should not enable local file access on it's own.
        $dom = new DOMDocument();
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", LIBXML_NONET);
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));

        // But it doesn't not disable local access that was enabled by NOENT.
        $dom = new DOMDocument();
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", LIBXML_NOENT | LIBXML_NONET);
        $this->assertTrue($this->searchDOMDocument($dom, 'filetext'));
    }

    /**
     * The documentation for LIBXML_NOXMLDECL says 'Drop the XML declaration when saving a document'.
     *
     * The key word in there is 'saving', although it's use doesn't seem to have actually been implemented
     * into the existing save functions anyway.
     *
     * What if someone doesn't quite get that and uses it when loading xml? Well, LIBXML_NOXMLDECL has
     * the same value as, of all things, LIBXML_NOENT.
     */
    public function test_noxmldecl_constant() {
        global $CFG;

        $this->assertEquals(LIBXML_NOENT, LIBXML_NOXMLDECL);

        $dom = new DOMDocument();
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", LIBXML_NOXMLDECL);
        $this->assertTrue($this->searchDOMDocument($dom, 'filetext'));

        $newdom = new DOMDocument();
        $newdom->loadXML("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<root/>\n");
        $returnedxml = $newdom->saveXML(null, LIBXML_NOXMLDECL);
        // This constant doesn't work anywhere, at least not here. See https://bugs.php.net/bug.php?id=47137
        $this->assertEquals("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<root/>\n", $returnedxml);

        // We'll make sure that option doesn't persist after using the save method.
        $newdom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml");
        // No, it doesn't.
        $this->assertFalse($this->searchDOMDocument($newdom, 'filetext'));
    }

    /**
     * The php documentation for LIBXML_BIGLINES says 'Allows line numbers greater than 65535 to be reported correctly.'
     *
     * PHPStorm was showing this to be equal to 65535,  for which the binary digit corresponding
     * to LIBXML_NOENT is equal to 1. However, that number seems to be incorrect,
     * the actual integer in PHP seems to be 419304, which is fine.
     *
     * Still, let's just play it safe and test this in case they were referencing some version of PHP.
     */
    public function test_biglines_constant() {
        global $CFG;

        if (!defined('LIBXML_BIGLINES')) {
            // This is actually only available as of PHP 7.0.0 with Libxml >= 2.9.0.
            $this->markTestSkipped();
        }

        $this->assertNotEquals((LIBXML_BIGLINES | LIBXML_NOENT), (LIBXML_BIGLINES));

        $dom = new DOMDocument();
        $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", LIBXML_BIGLINES);
        $this->assertFalse($this->searchDOMDocument($dom, 'filetext'));
    }

    /**
     * Does a quick test of any constant that doesn't require additional set up and is compatible with all supported
     * versions of PHP. Any not included here should have been tested above if they present any genuine risk.
     *
     * Runs these tests on functions across the 3 libraries that load XML in PHP.
     */
    public function test_additional_constants() {
        global $CFG;

        $constants = array(
            'LIBXML_COMPACT' => LIBXML_COMPACT,
            'LIBXML_DTDLOAD' => LIBXML_DTDLOAD,
            'LIBXML_DTDATTR' => LIBXML_DTDATTR,
            'LIBXML_NOBLANKS' => LIBXML_NOBLANKS,
            'LIBXML_NOCDATA' => LIBXML_NOCDATA,
            'LIBXML_NOEMPTYTAG' => LIBXML_NOEMPTYTAG,
            'LIBXML_NOERROR' => LIBXML_NOERROR,
            'LIBXML_NONET' => LIBXML_NONET,
            'LIBXML_NOWARNING' => LIBXML_NOWARNING,
            'LIBXML_NSCLEAN' => LIBXML_NSCLEAN,
            'LIBXML_XINCLUDE' => LIBXML_XINCLUDE
        );

        foreach($constants as $name => $constant) {
            $dom = new DOMDocument();
            $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", $constant);
            $this->assertFalse($this->searchDOMDocument($dom, 'filetext'),
                'DOMDocument loaded external file was loaded using constant: ' . $name);

            $xml = simplexml_load_file($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", null, $constant);
            $this->assertFalse($this->searchSimpleXML($xml, 'filetext'),
                'simplexml_load_file loaded external file was loaded using constant: ' . $name);

            $xml = new XMLReader();
            $outcome = $xml->open($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml", null, $constant);
            $this->assertTrue($outcome);
            $this->assertFalse($this->searchXMLReader($xml, 'filetext'),
                'XMLReader loaded external file was loaded using constant: ' . $name);
        }
    }
}