@totara @tool @tool_totara_sync @javascript
Feature: Verify changing user element settings for CSV import.

  Background:
    Given I am on a totara site
    When I log in as "admin"
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
    Then I should see "Settings updated. The source settings for this element can be configured here."

  Scenario: Check the effect of changing the create option for the user element.

    # Check that the firstname and lastname fields are included in the import.
    # When 'create' is allowed, these should be automatically included as part
    # of the import fields.
    Given I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    Then I should see "\"firstname\",\"lastname\""
    And I should not see "import_firstname" in the "#id_importheader" "css_element"
    And I should not see "import_lastname" in the "#id_importheader" "css_element"

    When I navigate to "User" node in "Site administration > HR Import > Elements"
    And I click on "Create" "checkbox"
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    Then I should see "\"firstname\",\"lastname\""

    # Remove firstname and lastname. They are no longer included by default.
    When I click on "First name" "checkbox"
    And I click on "Last name" "checkbox"
    And I press "Save changes"
    Then I should see "Settings saved"
    Then I should not see "\"firstname\",\"lastname\""

  Scenario: Check the effect of changing the duplicate email option for the user element.

    # Check that duplicate emails is turned off.
    Given I navigate to "User" node in "Site administration > HR Import > Elements"
    Then the field "Allow duplicate emails" matches value "No"

    When I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    And I press "Save changes"
    Then I should see "Settings saved"

    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/user_duplicate_email_1.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done! However, there have been some problems"

    # Check that the import has failed because duplicate emails is not enabled.
    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    Then the following should exist in the "totarasynclog" table:
      | Element | Log type | Action      | Info                                                                  |
      | user    | Error    | checksanity | Duplicate users with email duplicate@example.com. Skipped user imp001 |
      | user    | Error    | checksanity | Duplicate users with email duplicate@example.com. Skipped user imp002 |

    # Confirm that no user records have been created.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    Then I should not see "Import User001"
    And I should not see "Import User002"

    # Turn on allow duplicate emails.
    Given I navigate to "User" node in "Site administration > HR Import > Elements"
    When I set the field "Allow duplicate emails" to "Yes"
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/user_duplicate_email_1.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"

    # Check that the duplicate emails have imported successfully.
    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    Then the following should exist in the "totarasynclog" table:
      | Element | Log type | Action     | Info                |
      | user    | Info     | createuser | created user imp001 |
      | user    | Info     | createuser | created user imp001 |

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I press "Edit this report"
    Then I should see "Edit Report 'Browse list of users'"

    # Check that the user have been created successfully.
    When I follow "View This Report"
    Then the following should exist in the "system_browse_users" table:
      | Username  | User's Email          |
      | import001 | duplicate@example.com |
      | import001 | duplicate@example.com |
