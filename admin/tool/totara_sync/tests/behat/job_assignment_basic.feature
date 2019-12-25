@_file_upload @javascript @tool @tool_totara_sync @totara @totara_job
Feature: Use user source to import basic job assignments data in HR sync
  In order to test HR import of job assignments
  I must log in as an admin and import from a CSV file

  Background:
    Given I log in as "admin"
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname        | idnumber |
      | Organisation FW | OFW001   |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | org_framework | fullname      | idnumber |
      | OFW001        | OrganisationX | orgx     |
      | OFW001        | OrganisationY | orgy     |
      | OFW001        | OrganisationZ | orgz     |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname    | idnumber |
      | Position FW | PFW001   |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | pos_framework | fullname  | idnumber |
      | PFW001        | PositionX | posx     |
      | PFW001        | PositionY | posy     |
      | PFW001        | PositionZ | posz     |

    # User source setup.
    When I navigate to "Default settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
        | File access | Upload Files |
    And I press "Save changes"
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "Job assignment" HR Import element
    And I navigate to "Job assignment" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV | 1 |
    And I press "Save changes"

    # Enable all job assignment fields.
    When I navigate to "CSV" node in "Site administration > HR Import > Sources > Job assignment"
    And I set the following fields to these values:
      | Full name    | 1 |
      | Start date   | 1 |
      | End date     | 1 |
      | Organisation | 1 |
      | Position     | 1 |
      | Manager      | 1 |
      | Appraiser    | 1 |
    And I press "Save changes"

  Scenario: Test common notifications in job assignment HR Import
    # This test saves us running should/not see various notifications or search results in each test.
    # We should see the required fields for the csv file after enabling them.
    Then I should see "\"idnumber\""
    And I should see "\"fullname\""
    And I should see "\"startdate\""
    And I should see "\"enddate\""
    And I should see "\"orgidnumber\""
    And I should see "\"posidnumber\""
    And I should see "\"manageridnumber\""
    And I should see "\"appraiseridnumber\""

    # We're just choosing an arbitrary file to import.
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/jobassignment/fulltest_updateidnumbers_on.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done! However, there have been some problems"

    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 1    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "HR Import finished" in the "#totarasynclog" "css_element"

