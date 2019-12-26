@_file_upload @javascript @tool @totara @totara_hierarchy @tool_totara_sync
Feature: Verify that parentid is set correctly for organisation database import.

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And the following "organisation" frameworks exist:
      | fullname                 | idnumber |
      | Organisation Framework 1 | OF1      |
    And the following "organisation" HR Import database source exists:
      | idnumber | fullname         | frameworkidnumber | parentidnumber | timemodified |
      | 0        | Head Office      | OF1               |                | 0            |
      | 1        | Development Team | OF1               | 0              | 0            |
      | 11       | NZ Developers    | OF1               | 1              | 0            |
      | 12       | UK Developers    | OF1               | 1              | 0            |
      | 2        | Support Team     | OF1               | 0              | 0            |
      | 21       | NZ Support       | OF1               | 2              | 0            |
      | 22       | UK Support       | OF1               | 2              | 0            |

    When I navigate to "Default settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | File access | Upload Files |
    And I press "Save changes"
    Then I should see "Settings saved"

    When I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "Organisation" HR Import element
    Then I should see "Element enabled"

    When I navigate to "Organisation" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | External Database           | 1                 |
      | Source contains all records | Yes               |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I navigate to "External Database" node in "Site administration > HR Import > Sources > Organisation"
    And I press "Save changes"
    Then I should see "Settings saved"

  Scenario: Verify organisations database import with a parent organisation id of 0.

    Given I navigate to "Run HR Import" node in "Site administration > HR Import"
    When I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    When I navigate to "Manage organisations" node in "Site administration > Organisations"
    And I follow "Organisation Framework 1"
    Then I should see these hierarchy items at the following depths:
      | Head Office       | 1 |
      | Development Team  | 2 |
      | NZ Developers     | 3 |
      | UK Developers     | 3 |
      | Support Team      | 2 |
      | NZ Support        | 3 |
      | UK Support        | 3 |

  Scenario: Verify organisations database import deletes a record and updates the parentid appropriately.

    Given I navigate to "Run HR Import" node in "Site administration > HR Import"
    When I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    When the following "organisation" HR Import database source exists:
      | idnumber | fullname               | frameworkidnumber | parentidnumber | timemodified |
      | 0        | Head Office            | OF1               |                | 0            |
      | 11       | Development            | OF1               | 0              | 0            |
      | 21       | Support                | OF1               | 0              | 0            |
      | 2        | UK Office              | OF1               | 0              | 0            |
      | 12       | Development & Support  | OF1               | 2              | 0            |
      | 22       | Marketing              | OF1               |                | 0            |
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    When I navigate to "Manage organisations" node in "Site administration > Organisations"
    And I follow "Organisation Framework 1"
    Then I should see these hierarchy items at the following depths:
      | Head Office           | 1 |
      | UK Office             | 2 |
      | Development & Support | 3 |
      | Development           | 2 |
      | Support               | 2 |
      | Marketing             | 1 |
    And I should not see "Development Team"
