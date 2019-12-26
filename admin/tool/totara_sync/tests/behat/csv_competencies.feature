@_file_upload @javascript @tool @totara @totara_customfield @totara_hierarchy @tool_totara_sync
Feature: Upload competencies via HR Import using CSV file
  In order to test HR import of competencies
  I must log in as an admin and import from a CSV file

  Background:
    Given I am on a totara site
    And the following "competency" frameworks exist:
      | fullname               | idnumber |
      | Competency Framework 1 | compfw1  |
      | Competency Framework 2 | compfw2  |

    And I log in as "admin"
    And I navigate to "Manage types" node in "Site administration > Competencies"
    And I press "Add a new type"
    And I set the following fields to these values:
      | Type full name            | Competency type |
      | Competency type ID number | CompType        |
    And I press "Save changes"
    And I follow "Competency type"

    And I should see "Create a new custom field"
    And I set the field "datatype" to "Text input"
    And I should see "Editing custom field: Text input"
    And I set the following fields to these values:
      | fullname    | Custom field text input |
      | shortname   | cftext                  |
    And I press "Save changes"

    # HR Import configuration
    And I navigate to "Default settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
        | File access | Upload Files |
    And I press "Save changes"
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "Competency" HR Import element
    And I navigate to "Competency" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV                         | 1   |
      | Source contains all records | Yes |
    And I press "Save changes"
    And I navigate to "CSV" node in "Site administration > HR Import > Sources > Competency"
    And I set the following fields to these values:
     | Type                    | 1 |
     | Custom field text input | 1 |
    And I press "Save changes"

  Scenario: Upload competency CSV with customfield using HR Import
    And I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/competencies.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I should see "Running HR Import cron...Done!"
    And I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I should not see "Error" in the "#totarasynclog" "css_element"

    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    Then I should see "2" in the "Competency Framework 1" "table_row"
    And I should see "1" in the "Competency Framework 2" "table_row"

    When I follow "Competency Framework 1"
    And I follow "Competency 2"
    Then I should see "Other text"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    When I follow "Competency Framework 2"
    And I follow "Competency 3"
    Then I should see "So much text"
