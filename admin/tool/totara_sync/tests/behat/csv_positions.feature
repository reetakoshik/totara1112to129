@_file_upload @javascript @tool @totara @totara_customfield @totara_hierarchy @tool_totara_sync
Feature: Use customfields in HR import position upload
  In order to test HR import of positions with customfields
  I must log in as an admin and import from a CSV file

  Background:
    Given I am on a totara site
    And the following "position" frameworks exist:
      | fullname             | idnumber |
      | Position Framework 1 | posfw1   |
      | Position Framework 2 | posfw2   |

    And I log in as "admin"
    And I navigate to "Manage types" node in "Site administration > Positions"
    And I press "Add a new type"
    And I set the following fields to these values:
      | Type full name          | Position type |
      | Position type ID number | PosType       |
    And I press "Save changes"
    And I follow "Position type"

    # Text
    And I should see "Create a new custom field"
    And I set the field "datatype" to "Text input"
    And I should see "Editing custom field: Text input"
    And I set the following fields to these values:
      | fullname    | Custom field text input |
      | shortname   | cftext                  |
    And I press "Save changes"

    # Location
    And I should see "Create a new custom field"
    And I set the field "datatype" to "Location"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Full name         | Custom field location   |
      | Short name        | cflocation              |
    And I press "Save changes"

    # URL
    And I should see "Create a new custom field"
    And I set the field "datatype" to "URL"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Full name         | Custom field URL        |
      | Short name        | cfurl                   |
    And I press "Save changes"

    # HR Import configuration
    And I navigate to "Default settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
        | File access | Upload Files |
    And I press "Save changes"
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "Position" HR Import element
    And I navigate to "Position" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV                         | 1   |
      | Source contains all records | Yes |
    And I press "Save changes"
    And I navigate to "CSV" node in "Site administration > HR Import > Sources > Position"
    And I set the following fields to these values:
     | Type                    | 1 |
     | Custom field text input | 1 |
     | Custom field location   | 1 |
     | Custom field URL        | 1 |
    And I press "Save changes"

  Scenario: Upload position CSV with customfield using HR Import
    And I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/positions_cf.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I should see "Running HR Import cron...Done!"
    And I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I should not see "Error" in the "#totarasynclog" "css_element"

    # Check
    When I navigate to "Manage positions" node in "Site administration > Positions"
    Then I should see "5" in the "Position Framework 1" "table_row"
    And I should see "6" in the "Position Framework 2" "table_row"

    When I follow "Position Framework 1"
    And I follow "Position 4"
    Then I should see "Her Majesty"
    And I should see "Buckingham Palace, London, England"
    And I should see "http://www.buckinghampalace.co.uk"
    And I navigate to "Manage positions" node in "Site administration > Positions"
    When I follow "Position Framework 2"
    And I follow "Position 6"
    Then I should see "Stonehenge"
    And I should see "Off A344 Road, Amesbury, Wiltshire, England"
    And I should see "http://www.stonehenge.co.uk"

    # Check URL customfield value is cleaned.
    When I navigate to "Manage positions" node in "Site administration > Positions"
    Then I follow "Position Framework 2"
    And I follow "Position 11"
    Then I should see "URL Hacker"
    And I should not see "gotcha"
