@totara @tool @tool_totara_sync @_file_upload @javascript @profile_fields
Feature: User menu profile fields handle special characters via HR Import.

  Background:
    Given I am on a totara site

  Scenario: Verify special characters can be added to user profile fields via HR Import.

    Given I log in as "admin"
    When I navigate to "User profile fields" node in "Site administration > Users"
    And I set the following fields to these values:
      | datatype | menu |
    And I set the following fields to these values:
      | Name       | Menu Test |
      | Short name | menutest  |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Health & Safety
      > 10
      < 10
      """
    And I press "Save changes"
    Then I should see "Menu Test"

    # Configure HR Import for csv.
    When I navigate to "Default settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | File access | Upload Files |
    And I press "Save changes"
    And I should see "Settings saved"

    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "User" HR Import element
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV | 1 |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."
    When I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    And I click on "Menu Test" "checkbox"
    And I press "Save changes"
    Then I should see "Settings saved"
    And I should see "\"customfield_menutest\""

    # Upload csv containing the text input custom field data.
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/user_profile_fields_menu.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"

    # Confirm the custom field data is saved against the user.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Upload User 1"
    Then I should see "Health & Safety"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Upload User 2"
    Then I should see "> 10"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Upload User 3"
    Then I should see "< 10"
