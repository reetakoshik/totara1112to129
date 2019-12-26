@totara @tool_totara_sync @javascript @_file_upload
Feature: Verify different delimiters can be handled in Totara Sync

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And the following "organisation" frameworks exist:
      | fullname                 | idnumber |
      | Organisation Framework 1 | OF1      |
    And the following "position" frameworks exist:
      | fullname             | idnumber |
      | Position Framework 1 | PF1      |

    When I navigate to "Default settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | File access | Upload Files |
    And I press "Save changes"
    Then I should see "Settings saved"

  Scenario: Verify an organisation CSV that uses a comma delimiter can be uploaded.

    When I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "Organisation" HR Import element
    Then I should see "Element enabled"

    When I navigate to "Organisation" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV                         | 1   |
      | Source contains all records | Yes |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I navigate to "CSV" node in "Site administration > HR Import > Sources > Organisation"
    And I set the following fields to these values:
      | Delimiter | Comma (,) |
    And I press "Save changes"
    Then I should see "Settings saved"

    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/organisations-with-comma-delimiter.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    When I navigate to "Manage organisations" node in "Site administration > Organisations"
    And I follow "Organisation Framework 1"
    Then I should see "Organisation 1"
    And I should see "Organisation 2"
    And I should see "Organisation 3"

  Scenario: Verify an organisation CSV that uses a tab delimiter can be uploaded.

    When I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "Organisation" HR Import element
    Then I should see "Element enabled"

    When I navigate to "Organisation" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV                         | 1   |
      | Source contains all records | Yes |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I navigate to "CSV" node in "Site administration > HR Import > Sources > Organisation"
    And I set the following fields to these values:
      | Delimiter | Tab (\t) |
    And I press "Save changes"
    Then I should see "Settings saved"

    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/organisations-with-tab-delimiter.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    When I navigate to "Manage organisations" node in "Site administration > Organisations"
    And I follow "Organisation Framework 1"
    Then I should see "Organisation 1"
    And I should see "Organisation 2"
    And I should see "Organisation 3"

  Scenario: Verify a position CSV that uses a comma delimiter can be uploaded.

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
      | Delimiter | Comma (,) |
    And I press "Save changes"
    Then I should see "Settings saved"

    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/positions-with-comma-delimiter.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"

    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    When I navigate to "Manage positions" node in "Site administration > Positions"
    And I follow "Position Framework 1"
    Then I should see "Position 1"
    And I should see "Position 2"
    And I should see "Position 3"

  Scenario: Verify a position CSV that uses a tab delimiter can be uploaded.

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
      | Delimiter | Tab (\t) |
    And I press "Save changes"
    Then I should see "Settings saved"

    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/positions-with-tab-delimiter.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    When I navigate to "Manage positions" node in "Site administration > Positions"
    And I follow "Position Framework 1"
    Then I should see "Position 1"
    And I should see "Position 2"
    And I should see "Position 3"

  Scenario: Verify an user CSV that uses a tab delimiter can be uploaded.

    When I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "User" HR Import element
    Then I should see "Element enabled"

    When I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV                         | 1   |
      | Source contains all records | Yes |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    And I set the following fields to these values:
      | Delimiter | Tab (\t) |
    And I press "Save changes"
    Then I should see "Settings saved"

    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/users-with-tab-delimiter.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should not see "Error"
    And I should see "Running HR Import cron...Done!"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    Then I should see "Learner1 User1"
    And I should see "Learner2 User2"
    And I should see "Learner3 User3"
