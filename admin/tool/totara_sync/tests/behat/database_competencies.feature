@totara @tool @tool_totara_sync @totara_hierarchy @javascript
Feature: Test the competency database source.

  Background:
    Given I am on a totara site
    And the following "competency" frameworks exist:
      | fullname            | idnumber   |
      | Test Comp Framework | tstcompfw  |
    When I log in as "admin"
    And I navigate to "Default settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | File access | Upload Files |
    And I press "Save changes"
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "Competency" HR Import element
    And I navigate to "Competency" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | External Database | 1 |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

  Scenario: Test table exists check for the competency database source
    # Create the external database.
    Given the following "competency" HR Import database source exists:
      | idnumber | fullname   | deleted  | frameworkidnumber  | timemodified   | aggregationmethod |
      | 1        | comp1      | 0        | tstcompfw          | 0              | 1                 |
      | 2        | comp2      | 0        | tstcompfw          | 0              | all               |

    # Run the sync, all should be good.
    When I navigate to "External Database" node in "Site administration > HR Import > Sources > Competency"
    And I press "Save changes"
    Then I should see "Settings saved"
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"

    # Update the source database settings to set a non existent table name.
    When I navigate to "External Database" node in "Site administration > HR Import > Sources > Competency"
    And I set the following fields to these values:
      | Database table | behat_totara_sync_source_comp_Does_not_exist |
    And I press "Save changes"
    Then I should see "Settings saved"

    # Run the sync, we expect an error.
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Error:comp - Remote database table \"behat_totara_sync_source_comp_Does_not_exist\" does not exist"
    And I should see "Running HR Import cron...Done! However, there have been some problems"

  Scenario: Test table fields exist checks for the competency database source
    # Create the external database.
    Given the following "competency" HR Import database source exists:
      | idnumber | fullname   | deleted  | frameworkidnumber  | timemodified   | aggregationmethod |
      | 1        | comp1      | 0        | tstcompfw          | 0              | 2                 |
      | 2        | comp2      | 0        | tstcompfw          | 0              | off               |

    # Run the sync, all should be good.
    When I navigate to "External Database" node in "Site administration > HR Import > Sources > Competency"
    And I press "Save changes"
    Then I should see "Settings saved"
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"

    # Update the source database settings to map to non existent table fields.
    When I navigate to "External Database" node in "Site administration > HR Import > Sources > Competency"
    And I set the following fields to these values:
      | idnumber          | idnumber_does_not_exist          |
      | fullname          | fullname_does_not_exist          |
      | deleted           | deleted_does_not_exist           |
      | frameworkidnumber | frameworkidnumber_does_not_exist |
    And I press "Save changes"
    Then I should see "Settings saved"

    # Run the sync, we expect an error.
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Error:comp - Remote database table does not contain field(s) \"idnumber_does_not_exist, fullname_does_not_exist, deleted_does_not_exist, frameworkidnumber_does_not_exist\""
    And I should see "Running HR Import cron...Done! However, there have been some problems"
