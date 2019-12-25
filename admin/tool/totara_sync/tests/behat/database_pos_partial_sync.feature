@javascript @tool @totara @totara_hierarchy @tool_totara_sync
Feature: Verify that partial position sync works correctly for database import.

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And the following "position" frameworks exist:
      | fullname             | idnumber |
      | Position Framework 1 | POSF1    |
    And the following "position" HR Import database source exists:
      | idnumber | fullname   | frameworkidnumber | parentidnumber | timemodified |
      | 111      | Position 1 | POSF1             |                | 0            |
      | 222      | Position 2 | POSF1             |                | 0            |
      | 333      | Position 3 | POSF1             |                | 0            |
      | 444      | Position 4 | POSF1             |                | 0            |
      | 555      | Position 5 | POSF1             |                | 0            |
      | 666      | Position 6 | POSF1             |                | 0            |
      | 777      | Position 7 | POSF1             |                | 0            |

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

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

  Scenario: Verify the positions are imported and show on the Positions admin page

    Given I navigate to "Manage positions" node in "Site administration > Positions"
    When I follow "Position Framework 1"
    Then I should see these hierarchy items at the following depths:
      | Position 1 | 1 |
      | Position 2 | 1 |
      | Position 3 | 1 |
      | Position 4 | 1 |
      | Position 5 | 1 |
      | Position 6 | 1 |
      | Position 7 | 1 |

  Scenario: Verify that positions "Source contains all records" setting "Yes" works correctly.

    # Set source contains all records to yes.
    Given I navigate to "Position" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
        | Source contains all records | Yes |
    When I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When the following "position" HR Import database source exists:
      | idnumber | fullname   | frameworkidnumber | parentidnumber | timemodified |
      | 111      | Position 1 | POSF1             |                | 0            |
      | 222      | Position 2 | POSF1             |                | 0            |
      | 333      | Position 3 | POSF1             |                | 0            |
      | 444      | Position 4 | POSF1             |                | 0            |

    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    When I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    When I navigate to "Manage positions" node in "Site administration > Positions"
    And I follow "Position Framework 1"
    Then I should see these hierarchy items at the following depths:
      | Position 1 | 1 |
      | Position 2 | 1 |
      | Position 3 | 1 |
      | Position 4 | 1 |

    And I should not see "Position 5"
    And I should not see "Position 6"
    And I should not see "Position 7"

  Scenario: Verify that positions "Source contains all records" setting "No" works correctly.

    # Set source contains all records to yes.
    Given I navigate to "Position" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Source contains all records | No |
    When I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When the following "position" HR Import database source exists:
      | idnumber | fullname   | deleted | frameworkidnumber | parentidnumber | timemodified |
      | 111      | Position 1 | 0       | POSF1             |                | 0            |
      | 222      | Position 2 | 0       | POSF1             |                | 0            |
      | 333      | Position 3 | 0       | POSF1             |                | 0            |
      | 444      | Position 4 | 1       | POSF1             |                | 0            |
      | 555      | Position 5 | 1       | POSF1             |                | 0            |

    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    When I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    When I navigate to "Manage positions" node in "Site administration > Positions"
    And I follow "Position Framework 1"
    Then I should see these hierarchy items at the following depths:
      | Position 1 | 1 |
      | Position 2 | 1 |
      | Position 3 | 1 |
      | Position 6 | 1 |
      | Position 7 | 1 |

    And I should not see "Position 4"
    And I should not see "Position 5"
