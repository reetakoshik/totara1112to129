@totara @tool_totara_sync @javascript @_file_upload
Feature: An admin can import different CSV enconding through HR import

  Background:
    Given I log in as "admin"
    And I navigate to "Manage authentication" node in "Site administration > Plugins > Authentication"
    And I set the following fields to these values:
      | User deletion | Keep username, email and ID number (legacy) |
    And I press "Save changes"
    And I navigate to "Default settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
        | File access | Upload Files |
    And I press "Save changes"
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "User" HR Import element
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV | 1 |
    And I press "Save changes"

  Scenario: add an user from user utf8 bom enconding file
    Given I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    And I should see "\"firstname\""
    And I should see "\"lastname\""
    And I should see "\"email\""
    And I press "Save changes"
    And I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/user_utf8_bom.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I should see "Running HR Import cron...Done!"
    And I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I should not see "Error" in the "#totarasynclog" "css_element"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    Then I should see "bob marley"
    Then I should see "Žlutý Koníček"

  Scenario: add another user from user win1252 encoding file
    Given I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    And I set the field "CSV file encoding" to "WINDOWS-1250"
    And I press "Save changes"
    And I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/user_win1250.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I should see "Running HR Import cron...Done!"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    Then I should see "alice smith"
    Then I should see "Žlutý Koníček"
