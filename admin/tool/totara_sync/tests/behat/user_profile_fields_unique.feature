@totara @tool @tool_totara_sync @_file_upload @javascript @profile_fields
Feature: User profile fields can be used via HR Import.

  Background:
    Given I am on a totara site

    When I log in as "admin"
    And I set the following administration settings values:
      | csvdateformat           | d/m/Y  |

    # Create a user checkbox custom field set as unique.
    And I navigate to "User profile fields" node in "Site administration > Users"
    And I set the following fields to these values:
      | datatype   | checkbox |
    And I set the following fields to these values:
      | Name                        | Checkbox 1 |
      | Short name                  | checkbox1  |
      | Should the data be unique?  | Yes        |
    And I press "Save changes"
    Then I should see "Checkbox 1"

    # Create a user date (no timezone) custom field set as unique.
    When I set the following fields to these values:
      | datatype   | date |
    And I set the following fields to these values:
      | Name                        | Date 1 |
      | Short name                  | date1  |
      | Should the data be unique?  | Yes    |
    And I press "Save changes"
    Then I should see "Date 1"

    # Create a user date/time custom field set as unique.
    When I set the following fields to these values:
      | datatype   | datetime |
    And I set the following fields to these values:
      | Name                        | Date/Time 1 |
      | Short name                  | datetime1   |
      | Start year                  | 2017        |
      | End year                    | 2050        |
      | Should the data be unique?  | Yes         |
      | Include time?               | Yes         |
    And I press "Save changes"
    Then I should see "Date/Time 1"

    # Create a user dropdown menu custom field set as unique.
    # Note: menu custom fields do not currently enforce uniqueness. Keeping this in though in case this changes.
    When I set the following fields to these values:
      | datatype   | menu |
    And I set the following fields to these values:
      | Name                        | Menu 1 |
      | Short name                  | menu1  |
      | Should the data be unique?  | Yes    |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Option 1
      Option 2
      Option 3
      """
    And I press "Save changes"
    Then I should see "Menu 1"

    # Create a user textarea custom field set as unique.
    When I set the following fields to these values:
      | datatype   | textarea |
    And I set the following fields to these values:
      | Name                        | Textarea 1 |
      | Short name                  | textarea1  |
      | Should the data be unique?  | Yes        |
    And I press "Save changes"
    Then I should see "Textarea 1"

    # Create a user text custom field set as unique.
    When I set the following fields to these values:
      | datatype | text |
    And I set the following fields to these values:
      | Name                        | Text 1 |
      | Short name                  | text1  |
      | Should the data be unique?  | Yes    |
    And I press "Save changes"
    Then I should see "Text 1"

    # Configure HR Import for csv.
    When I navigate to "Default settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | File access | Upload Files |
    And I press "Save changes"
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "User" HR Import element
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV                           | 1                                 |
      | Empty string behaviour in CSV | Empty strings erase existing data |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."
    When I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    And I click on "Checkbox 1" "checkbox"
    And I click on "Date 1" "checkbox"
    And I click on "Date/Time 1" "checkbox"
    And I click on "Menu 1" "checkbox"
    And I click on "Textarea 1" "checkbox"
    And I click on "Text 1" "checkbox"
    And I press "Save changes"
    Then I should see "Settings saved"
    And I should see "\"customfield_checkbox1\""
    And I should see "\"customfield_date1\""
    And I should see "\"customfield_datetime1\""
    And I should see "\"customfield_menu1\""
    And I should see "\"customfield_textarea1\""
    And I should see "\"customfield_text1\""

  Scenario: Verify unique user profile fields with values pass uniqueness check in HR Import.

    Given I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    When I upload "admin/tool/totara_sync/tests/fixtures/user_profile_fields_unique_1.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"

    # Confirm the custom field data is saved against the user.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Upload User 1"
    Then I should see "Checkbox 1"
    And the "Checkbox 1" "checkbox" should be disabled
    And I should see "Date 1"
    And I should see "19 July 2034"
    And I should see "Date/Time 1"
    And I should see "Monday, 20 August 2035, 12:00 AM"
    And I should see "Menu 1"
    And I should see "Option 1"
    And I should see "Textarea 1"
    And I should see "textarea data"
    And I should see "Text 1"
    And I should see "text data"

    # Upload a CSV containing unique values.
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/user_profile_fields_unique_2.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"

    # Confirm the user has been created as expected.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Upload User 2"
    Then I should see "Checkbox 1"
    And the "Checkbox 1" "checkbox" should be disabled
    And I should see "Date 1"
    And I should see "20 July 2034"
    And I should see "Date/Time 1"
    And I should see "Tuesday, 21 August 2035, 12:00 AM"
    And I should see "Menu 1"
    And I should see "Option 2"
    And I should see "Textarea 1"
    And I should see "textarea data 2"
    And I should see "Text 1"
    And I should see "text data 2"

  Scenario: Verify unique user profile fields with non-unique checkbox values fail uniqueness check in HR Import.

    Given I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    When I upload "admin/tool/totara_sync/tests/fixtures/user_profile_fields_unique_2.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"

    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/user_profile_fields_unique_2_checkbox.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done! However, there have been some problems"

    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    Then I should see "The value '1' for customfield_checkbox1 is a duplicate of existing data and must be unique. Skipped user"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    Then I should not see "Upload User 3"

  Scenario: Verify unique user profile fields with non-unique date values fail uniqueness check in HR Import.

    Given I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    When I upload "admin/tool/totara_sync/tests/fixtures/user_profile_fields_unique_2.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"

    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/user_profile_fields_unique_2_date.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done! However, there have been some problems"

    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    Then I should see "The date of 20/07/2034 (2036966400) for customfield_date1 is a duplicate of existing data and must be unique. Skipped user 3"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    Then I should not see "Upload User 3"

  Scenario: Verify unique user profile fields with non-unique datetime values fail uniqueness check in HR Import.

    Given I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    When I upload "admin/tool/totara_sync/tests/fixtures/user_profile_fields_unique_2.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"

    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/user_profile_fields_unique_2_datetime.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done! However, there have been some problems"

    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    Then I should see "The date of 21/08/2035 (2037052800) for customfield_datetime1 is a duplicate of existing data and must be unique. Skipped user 3"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    Then I should not see "Upload User 3"

  Scenario: Verify unique user profile fields with non-unique menu values fail uniqueness check in HR Import.

    Given I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    When I upload "admin/tool/totara_sync/tests/fixtures/user_profile_fields_unique_2.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"

    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/user_profile_fields_unique_2_menu.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done! However, there have been some problems"

    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    Then I should see "The value 'Option 2' for customfield_menu1 is a duplicate of existing data and must be unique. Skipped user 3"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    Then I should not see "Upload User 3"

  Scenario: Verify unique user profile fields with non-unique textarea values fail uniqueness check in HR Import.

    Given I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    When I upload "admin/tool/totara_sync/tests/fixtures/user_profile_fields_unique_2.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"

    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/user_profile_fields_unique_2_textarea.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done! However, there have been some problems"

    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    Then I should see "The value 'textarea data 2' for customfield_textarea1 is a duplicate of existing data and must be unique. Skipped user 3"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    Then I should not see "Upload User 3"

  Scenario: Verify unique user profile fields with non-unique text values fail uniqueness check in HR Import.

    Given I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    When I upload "admin/tool/totara_sync/tests/fixtures/user_profile_fields_unique_2.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"

    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/user_profile_fields_unique_2_text.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done! However, there have been some problems"

    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    Then I should see "The value 'text data 2' for customfield_text1 is a duplicate of existing data and must be unique. Skipped user 3"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    Then I should not see "Upload User 3"

  Scenario: Verify unique user profile fields with empty values are not included in uniqueness check in HR Import.

    Given I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    When I upload "admin/tool/totara_sync/tests/fixtures/user_profile_fields_unique_1.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"

    # Confirm the custom field data is saved against the user.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Upload User 1"
    Then I should see "Checkbox 1"
    And the "Checkbox 1" "checkbox" should be disabled
    And I should see "Date 1"
    And I should see "19 July 2034"
    And I should see "Date/Time 1"
    And I should see "20 August 2035"
    And I should see "Menu 1"
    And I should see "Option 1"
    And I should see "Textarea 1"
    And I should see "textarea data"
    And I should see "Text 1"
    And I should see "text data"

    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/user_profile_fields_unique_3.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"

    # Confirm the custom field empty data is saved against the user.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Upload User 1"
    And I should not see "Checkbox 1"

    # TODO: TL-12770.
    # "Empty strings erase existing data" setting does not erase date and datetime data. See TL-12770
    # When a fix for this issue is in place the line below can be uncommented..
    # And I should not see "Date 1"
    # And I should not see "Date not set"
    # And I should not see "Date/Time 1"
    # And I should not see "Date not set"

    And I should not see "Menu 1"
    And I should not see "Option 1"
    And I should not see "Textarea 1"
    And I should not see "textarea data"

    # Upload another user with a empty custom field data to ensure empty is not counted towards the unique check.
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/user_profile_fields_unique_4.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"

    # Confirm the new user is added.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    Then I should see "Upload User 2"
