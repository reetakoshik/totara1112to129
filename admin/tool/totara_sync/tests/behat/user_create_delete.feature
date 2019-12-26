@totara @tool @tool_totara_sync @_file_upload  @javascript
Feature: An admin can import users through HR import
  In order to test HR import of users
  I must log in as an admin and import from a CSV file

  Background:
    Given I log in as "admin"
    And I navigate to "Manage authentication" node in "Site administration > Plugins > Authentication"
    And I set the following fields to these values:
      | User deletion | Keep username, email and ID number (legacy) |
    And I press "Save changes"
    And I navigate to "Default settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
        | File access | Upload Files |
    And I press "Save changes"
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "User" HR Import element
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV | 1 |
    And I press "Save changes"
    And I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    And I should see "\"firstname\""
    And I should see "\"lastname\""
    And I should see "\"email\""
    And I set the following fields to these values:
      | City | 1 |
      | Country | 1 |
    And I press "Save changes"
    And I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/users.01.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I should see "Running HR Import cron...Done!"
    And I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I should not see "Error" in the "#totarasynclog" "css_element"

  Scenario: Import users through HR import.
    Given I navigate to "Browse list of users" node in "Site administration > Users"
    Then I should see "Import User001"
    And I should see "Import User002"
    And I should see "Import User003"

  Scenario: Reimport users through HR import.
    Given I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/users.01.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I should see "Running HR Import cron...Done!"
    And I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I should not see "Error" in the "#totarasynclog" "css_element"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    Then I should see "Import User001"
    And I should see "Import User002"
    And I should see "Import User003"

  Scenario: Import a deleted user through HR import so they are undeleted.
    Given I navigate to "Browse list of users" node in "Site administration > Users"
    And I set the following fields to these values:
      | user-fullname | 003 |
    And I press "id_submitgroupstandard_addfilter"
    And I should not see "Import User002"
    And I should not see "Import User002"
    And I should see "Import User003"
    And I follow "Delete"
    And I should see "Are you absolutely sure you want to completely delete 'Import User003'"
    And I press "Delete"
    And I press "Clear"
    And I follow "Show more..."
    And I set the following fields to these values:
      | User Status | Active |
    And I press "id_submitgroupstandard_addfilter"
    And I should see "Import User001"
    And I should see "Import User002"
    And I should not see "Import User003"
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/users.01.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I should see "Running HR Import cron...Done!"
    Then I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Show more..."
    And I set the following fields to these values:
      | User Status | Active |
    And I press "id_submitgroupstandard_addfilter"
    And I should see "Import User001"
    And I should see "Import User002"
    And I should see "Import User003"

  Scenario: Import a deleted user through HR import with configuration to prevent undelete.
    Given I navigate to "Browse list of users" node in "Site administration > Users"
    And I set the following fields to these values:
      | user-fullname | 003 |
    And I press "id_submitgroupstandard_addfilter"
    And I should not see "Import User001"
    And I should not see "Import User002"
    And I should see "Import User003"
    And I follow "Delete"
    And I should see "Are you absolutely sure you want to completely delete 'Import User003'"
    And I press "Delete"
    And I press "Clear"
    And I follow "Show more..."
    And I set the following fields to these values:
      | User Status | Active |
    And I press "id_submitgroupstandard_addfilter"
    And I should see "Import User001"
    And I should see "Import User002"
    And I should not see "Import User003"
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | allow_create | 0 |
    And I press "Save changes"
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/users.01.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I should see "Running HR Import cron...Done! However, there have been some problems"
    Then I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I should see "cannot undelete user imp003"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Show more..."
    And I set the following fields to these values:
      | User Status | Active |
    And I press "id_submitgroupstandard_addfilter"
    And I should see "Import User001"
    And I should see "Import User002"
    And I should not see "Import User003"

  Scenario: Import a deleted user through HR import with configuration to prevent undelete and complete sources.
    Given I navigate to "Browse list of users" node in "Site administration > Users"
    And I set the following fields to these values:
      | user-fullname | 003 |
    And I press "id_submitgroupstandard_addfilter"
    And I should not see "Import User001"
    And I should not see "Import User002"
    And I should see "Import User003"
    And I follow "Delete"
    And I should see "Are you absolutely sure you want to completely delete 'Import User003'"
    And I press "Delete"
    And I press "Clear"
    And I follow "Show more..."
    And I set the following fields to these values:
      | User Status | Active |
    And I press "id_submitgroupstandard_addfilter"
    And I should see "Import User001"
    And I should see "Import User002"
    And I should not see "Import User003"
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Source contains all records | Yes |
      | allow_create | 0 |
    And I press "Save changes"
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/users.01.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I should see "Running HR Import cron...Done! However, there have been some problems"
    Then I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I should see "cannot undelete user imp003"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Show more..."
    And I set the following fields to these values:
      | User Status | Active |
    And I press "id_submitgroupstandard_addfilter"
    And I should see "Import User001"
    And I should see "Import User002"
    And I should not see "Import User003"

  Scenario: Import a deleted user through HR import using full deletion of users.
    Given I navigate to "Manage authentication" node in "Site administration > Plugins > Authentication"
    And I set the following fields to these values:
      | User deletion | Full (legacy) |
    And I press "Save changes"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I set the following fields to these values:
      | user-fullname | 003 |
    And I press "id_submitgroupstandard_addfilter"
    And I should not see "Import User001"
    And I should not see "Import User002"
    And I should see "Import User003"
    And I follow "Delete"
    And I should see "Are you absolutely sure you want to completely delete 'Import User003'"
    And I press "Delete"
    And I press "Clear"
    And I should see "Import User001"
    And I should see "Import User002"
    And I should not see "Import User003"
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Source contains all records | Yes |
      | allow_create | 0 |
    And I press "Save changes"
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/users.01.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I should see "Running HR Import cron...Done!"
    Then I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I press "Clear all except latest records"
    And I press "Continue"
    And I should not see "user imp003"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Show more..."
    And I should see "Import User001"
    And I should see "Import User002"
    And I should not see "Import User003"

    When I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | allow_create | 1 |
    And I press "Save changes"
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/users.01.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I should see "Running HR Import cron...Done!"
    Then I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I press "Clear all except latest records"
    And I press "Continue"
    And I should see "created user imp003"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Show more..."
    And I should see "Import User001"
    And I should see "Import User002"
    And I should see "Import User003"
