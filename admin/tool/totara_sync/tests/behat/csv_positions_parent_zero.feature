@_file_upload @javascript @tool @totara @totara_hierarchy @tool_totara_sync
Feature: Verify that parentid is set correctly for position CSV uploads.

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And the following "position" frameworks exist:
      | fullname             | idnumber |
      | Position Framework 1 | PF1      |

    When I navigate to "Default settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | File access | Upload Files |
    And I press "Save changes"
    Then I should see "Settings saved"

    When I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "Position" HR Import element
    Then I should see "Element enabled"

    When I navigate to "Position" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV                         | 1   |
      | Source contains all records | Yes |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I navigate to "CSV" node in "Site administration > HR Import > Sources > Position"
    And I set the following fields to these values:
      | Parent | 1 |
    And I press "Save changes"
    Then I should see "Settings saved"

  Scenario: Verify positions CSV upload with a parent position id of 0.

    Given I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/positions_parent_zero_1.csv" file to "CSV" filemanager
    When I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    When I navigate to "Manage positions" node in "Site administration > Positions"
    And I follow "Position Framework 1"
    Then I should see these hierarchy items at the following depths:
      | Department Manager  | 1 |
      | A Team Leader       | 2 |
      | Position A1         | 3 |
      | Position A2         | 3 |
      | B Team Leader       | 2 |
      | Position B1         | 3 |
      | Position B2         | 3 |

  Scenario: Verify positions CSV upload deletes a record and updates the parentid appropriately.

    Given I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/positions_parent_zero_1.csv" file to "CSV" filemanager
    When I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    Given I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/positions_parent_zero_2.csv" file to "CSV" filemanager
    When I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    When I navigate to "Manage positions" node in "Site administration > Positions"
    And I follow "Position Framework 1"

    # Position 3 is in the wrong place. It should be under Team Leader 2. See TL-12671.
    Then I should see these hierarchy items at the following depths:
      | Department Manager  | 1 |
      | Team Leader 2       | 2 |
      | Position 3          | 3 |
      | Position 2          | 3 |
      | Team Leader 1       | 2 |
      | Position 1          | 3 |
    And I should not see "A Team Leader"

  Scenario: Verify positions CSV upload deletes a record and parentid appropriately.

    Given I navigate to "Position" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Empty string behaviour in CSV  | Empty strings erase existing data |
    When I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/positions_parent_zero_1.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    Given I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/positions_parent_zero_2.csv" file to "CSV" filemanager
    When I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    When I navigate to "Manage positions" node in "Site administration > Positions"
    And I follow "Position Framework 1"

    # The parentid for Position 3 has been deleted so it's at the top level.
    Then I should see these hierarchy items at the following depths:
      | Department Manager  | 1 |
      | Team Leader 2       | 2 |
      | Position 2          | 3 |
      | Team Leader 1       | 2 |
      | Position 1          | 3 |
      | Position 3          | 1 |
    And I should not see "A Team Leader"

  Scenario: Verify positions CSV upload with a parent position sanity checks correctly when source does not contain all records.
    Given I navigate to "Position" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Source contains all records | No  |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/positions_parent_sanity_check_1.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    # All the records should have been added correctly.
    When I navigate to "Manage positions" node in "Site administration > Positions"
    And I follow "Position Framework 1"
    Then I should see these hierarchy items at the following depths:
      | pos1  | 1 |
      | pos1a | 2 |
      | pos1b | 2 |
      | pos2  | 1 |

    # Now lets run a sync again where the parent exists, but not in the source.
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/positions_parent_sanity_check_2.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    # The record should have been added correctly.
    When I navigate to "Manage positions" node in "Site administration > Positions"
    And I follow "Position Framework 1"
    Then I should see these hierarchy items at the following depths:
      | pos1  | 1 |
      | pos1a | 2 |
      | pos1b | 2 |
      | pos2  | 1 |
      | pos2b | 2 |

    # Now lets run another sync where the parent does not exist.
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/positions_parent_sanity_check_3.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Error:pos - sanity check failed, aborting..."

    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I should see "parent P6 does not exist"

  Scenario: Verify positions CSV upload with a parent position sanity checks correctly when source contains all records.
    Given I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/positions_parent_sanity_check_4.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Error:pos - sanity check failed, aborting..."

    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I should see "parent P6 does not exist in HR Import file"

    # Lets run again with the missing parent within teh source to check the sync works.
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/positions_parent_sanity_check_5.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    # The records should have been added correctly.
    When I navigate to "Manage positions" node in "Site administration > Positions"
    And I follow "Position Framework 1"
    Then I should see these hierarchy items at the following depths:
      | pos1  | 1 |
      | pos1a | 2 |
      | pos1b | 2 |
      | pos2  | 1 |
      | pos3  | 1 |
      | pos3a | 2 |