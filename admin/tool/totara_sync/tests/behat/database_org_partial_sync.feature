@javascript @tool @totara @totara_hierarchy @tool_totara_sync
Feature: Verify that partial organisation sync works correctly for database import.

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And the following "organisation" frameworks exist:
      | fullname                 | idnumber |
      | Organisation Framework 1 | ORGF1    |
    And the following "organisation" HR Import database source exists:
      | idnumber | fullname       | frameworkidnumber | parentidnumber | timemodified |
      | 111      | Organisation 1 | ORGF1             |                | 0            |
      | 222      | Organisation 2 | ORGF1             |                | 0            |
      | 333      | Organisation 3 | ORGF1             |                | 0            |
      | 444      | Organisation 4 | ORGF1             |                | 0            |
      | 555      | Organisation 5 | ORGF1             |                | 0            |
      | 666      | Organisation 6 | ORGF1             |                | 0            |
      | 777      | Organisation 7 | ORGF1             |                | 0            |

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

  Scenario: Verify the organisations are imported and show on the Organisations admin page

    Given I navigate to "Run HR Import" node in "Site administration > HR Import"
    When I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    When I navigate to "Manage organisations" node in "Site administration > Organisations"
    And I follow "Organisation Framework 1"
    Then I should see these hierarchy items at the following depths:
      | Organisation 1 | 1 |
      | Organisation 2 | 1 |
      | Organisation 3 | 1 |
      | Organisation 4 | 1 |
      | Organisation 5 | 1 |
      | Organisation 6 | 1 |
      | Organisation 7 | 1 |

  Scenario: Verify that organisations "Source contains all records" setting "Yes" works correctly.

    Given I navigate to "Run HR Import" node in "Site administration > HR Import"
    When I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    # Set source contains all records to yes.
    When I navigate to "Organisation" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Source contains all records | Yes |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When the following "organisation" HR Import database source exists:
      | idnumber | fullname       | frameworkidnumber | parentidnumber | timemodified |
      | 111      | Organisation 1 | ORGF1             |                | 0            |
      | 222      | Organisation 2 | ORGF1             |                | 0            |
      | 333      | Organisation 3 | ORGF1             |                | 0            |
      | 444      | Organisation 4 | ORGF1             |                | 0            |

    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    When I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    When I navigate to "Manage organisations" node in "Site administration > Organisations"
    And I follow "Organisation Framework 1"
    Then I should see these hierarchy items at the following depths:
      | Organisation 1 | 1 |
      | Organisation 2 | 1 |
      | Organisation 3 | 1 |
      | Organisation 4 | 1 |

    And I should not see "Organisation 5"
    And I should not see "Organisation 6"
    And I should not see "Organisation 7"

  Scenario: Verify that organisations "Source contains all records" setting "No" works correctly.

    Given I navigate to "Run HR Import" node in "Site administration > HR Import"
    When I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    # Set source contains all records to yes.
    When I navigate to "Organisation" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
        | Source contains all records | No |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When the following "organisation" HR Import database source exists:
      | idnumber | fullname       | deleted | frameworkidnumber | parentidnumber | timemodified |
      | 111      | Organisation 1 | 0       | ORGF1             |                | 0            |
      | 222      | Organisation 2 | 0       | ORGF1             |                | 0            |
      | 333      | Organisation 3 | 0       | ORGF1             |                | 0            |
      | 444      | Organisation 4 | 1       | ORGF1             |                | 0            |
      | 555      | Organisation 5 | 1       | ORGF1             |                | 0            |

    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    When I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    When I navigate to "Manage organisations" node in "Site administration > Organisations"
    And I follow "Organisation Framework 1"
    Then I should see these hierarchy items at the following depths:
      | Organisation 1 | 1 |
      | Organisation 2 | 1 |
      | Organisation 3 | 1 |
      | Organisation 6 | 1 |
      | Organisation 7 | 1 |

    And I should not see "Organisation 4"
    And I should not see "Organisation 5"
