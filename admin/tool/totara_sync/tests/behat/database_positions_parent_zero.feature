@_file_upload @javascript @tool @totara @totara_hierarchy @tool_totara_sync
Feature: Verify that parentid is set correctly for position database import.

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And the following "position" frameworks exist:
      | fullname                 | idnumber |
      | Position Framework 1 | PF1      |
    And the following "position" HR Import database source exists:
      | idnumber | fullname           | frameworkidnumber | parentidnumber | timemodified |
      | 0        | Department Manager | PF1               |                | 0            |
      | 1        | A Team Leader      | PF1               | 0              | 0            |
      | 11       | Position A1        | PF1               | 1              | 0            |
      | 12       | Position A2        | PF1               | 1              | 0            |
      | 2        | B Team Leader      | PF1               | 0              | 0            |
      | 21       | Position B1        | PF1               | 2              | 0            |
      | 22       | Position B2        | PF1               | 2              | 0            |

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
      | External Database           | 1                 |
      | Source contains all records | Yes               |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I navigate to "External Database" node in "Site administration > HR Import > Sources > Position"
    And I press "Save changes"
    Then I should see "Settings saved"

  Scenario: Verify positions database import with a parent position id of 0.

    Given I navigate to "Run HR Import" node in "Site administration > HR Import"
    When I press "Run HR Import"
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

  Scenario: Verify positions database import deletes a record and updates the parentid appropriately.

    Given I navigate to "Run HR Import" node in "Site administration > HR Import"
    When I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    When the following "position" HR Import database source exists:
      | idnumber | fullname               | frameworkidnumber | parentidnumber | timemodified |
      | 0        | Department Manager     | PF1               |                | 0            |
      | 11       | Team Leader 1          | PF1               | 0              | 0            |
      | 21       | Position 1             | PF1               | 11             | 0            |
      | 2        | Team Leader 2          | PF1               | 0              | 0            |
      | 12       | Position 2             | PF1               | 2              | 0            |
      | 22       | Position 3             | PF1               |                | 0            |
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    When I navigate to "Manage positions" node in "Site administration > Positions"
    And I follow "Position Framework 1"
    Then I should see these hierarchy items at the following depths:
      | Department Manager  | 1 |
      | Team Leader 2       | 2 |
      | Position 2          | 3 |
      | Team Leader 1       | 2 |
      | Position 1          | 3 |
      | Position 3          | 1 |
    And I should not see "A Team Leader"
