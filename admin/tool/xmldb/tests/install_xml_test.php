<?php

class tool_xmldb_instal_xml_testcase extends advanced_testcase {

    public function test_all_install_xml_files_formatted_correctly() {
        global $CFG;

        require_once($CFG->dirroot . '/lib/adminlib.php');
        require_once($CFG->dirroot . '/admin/tool/xmldb/actions/XMLDBAction.class.php');
        require_once($CFG->dirroot . '/admin/tool/xmldb/actions/get_db_directories/get_db_directories.class.php');

        global $XMLDB;
        $XMLDB = new stdClass;

        $directories = new get_db_directories();
        $directories->invoke();

        $this->assertNotEmpty($XMLDB->dbdirs);

        foreach ($XMLDB->dbdirs as $directory) {

            if (!$directory->path_exists) {
                continue;
            }

            $file = $directory->path . '/install.xml';
            $xmldb_file = new xmldb_file($file);
            // Set the XML DTD and schema
            $xmldb_file->setDTD($CFG->dirroot . '/lib/xmldb/xmldb.dtd');
            $xmldb_file->setSchema($CFG->dirroot . '/lib/xmldb/xmldb.xsd');
            // Set dbdir as necessary
            if (!$xmldb_file->fileExists()) {
                continue;
            }
            // Load the XML contents to structure
            $this->assertTrue($xmldb_file->loadXMLStructure(), "XMLDB file '{$file}' cannot be loaded, check the structure returned by this call for the error.");
            $this->assertTrue($xmldb_file->isLoaded(), "XMLDB file '{$file}' did not load correctly");
            $structure = $xmldb_file->getStructure();
            $this->assertNotNull($structure, "XMLDB file '{$file}' structure could not be parsed");
            $this->assertInstanceOf('xmldb_structure', $structure);

            $xml = $structure->xmlOutput();
            $this->assertIsString($xml);
            $this->assertNotEmpty($xml);

            // If for any reason you want to bulk save XML files in the correct format just comment this.
            // =========================================================
            //      file_put_contents($file, $xml);
            // =========================================================
            // Of course the next test will pass ;)

            $this->assertTrue(file_get_contents($file) === $xml, "XMLDB file '{$file}' is different to the generated version, please edit the file in XMLDB editor and save it.");
        }

    }

}