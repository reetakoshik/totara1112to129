@totara @tool @tool_totara_sync @_file_upload @javascript @profile_fields
Feature: User profile fields set as unique can be used via HR Import.

  Background:
    Given I am on a totara site

  Scenario: User profile fields set as unique do not include empty values in uniqueness check.

    Given I log in as "admin"
    And I set the following administration settings values:
      | csvdateformat           | d/m/Y  |

    # Create a user checkbox custom field set as unique.
    And I navigate to "User profile fields" node in "Site administration > Users"
    And I set the following fields to these values:
      | datatype   | checkbox |
    And I set the following fields to these values:
      | Name                        | Unique checkbox test |
      | Short name                  | checkbox1            |
      | Should the data be unique?  | Yes                  |
    And I press "Save changes"
    Then I should see "Unique checkbox test"

    # Create a user date (no timezone) custom field set as unique.
    When I set the following fields to these values:
      | datatype   | date |
    And I set the following fields to these values:
      | Name                        | Unique date (no timezone) test |
      | Short name                  | date1                          |
      | Should the data be unique?  | Yes                            |
    And I press "Save changes"
    Then I should see "Unique date (no timezone) test"

    # Create a user date/time custom field set as unique.
    When I set the following fields to these values:
      | datatype   | datetime |
    And I set the following fields to these values:
      | Name                        | Unique date/time test |
      | Short name                  | datetime1             |
      | Start year                  | 2017                  |
      | End year                    | 2050                  |
      | Should the data be unique?  | Yes                   |
    And I press "Save changes"
    Then I should see "Unique date/time test"

    # Create a user dropdown menu custom field set as unique.
    # Note: menu custom fields do not currently enforce uniqueness. Keeping this in though in case this changes.
    When I set the following fields to these values:
      | datatype   | menu |
    And I set the following fields to these values:
      | Name                        | Unique dropdown menu test |
      | Short name                  | menu1                     |
      | Should the data be unique?  | Yes                       |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Option 1
      Option 2
      Option 3
      """
    And I press "Save changes"
    Then I should see "Unique dropdown menu test"

    # Create a user textarea custom field set as unique.
    When I set the following fields to these values:
      | datatype   | textarea |
    And I set the following fields to these values:
      | Name                        | Unique textarea test |
      | Short name                  | textarea1            |
      | Should the data be unique?  | Yes                  |
    And I press "Save changes"
    Then I should see "Unique textarea test"

    # Create a user text input custom field set as unique.
    When I set the following fields to these values:
      | datatype | text |
    And I set the following fields to these values:
      | Name                        | Unique textinput test |
      | Short name                  | text1                 |
      | Should the data be unique?  | Yes                   |
    And I press "Save changes"
    Then I should see "Unique textinput test"

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
    And I click on "Unique checkbox test" "checkbox"
    And I click on "Unique date (no timezone) test" "checkbox"
    And I click on "Unique date/time test" "checkbox"
    And I click on "Unique dropdown menu test" "checkbox"
    And I click on "Unique textarea test" "checkbox"
    And I click on "Unique textinput test" "checkbox"
    And I press "Save changes"
    Then I should see "Settings saved"
    And I should see "\"customfield_checkbox1\""
    And I should see "\"customfield_date1\""
    And I should see "\"customfield_datetime1\""
    And I should see "\"customfield_menu1\""
    And I should see "\"customfield_textarea1\""
    And I should see "\"customfield_text1\""

    # Upload csv containing the text input custom field data.
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/user_profile_fields_unique_1.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"

    # Confirm the custom field data is saved against the user.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Upload User 1"
    And I should see "Unique checkbox test"
    And the "Unique checkbox test" "checkbox" should be disabled
    And I should see "Unique date (no timezone) test"
    And I should see "19 July 2034"
    And I should see "Unique date/time test"
    And I should see "20 August 2035"
    And I should see "Unique dropdown menu test"
    And I should see "Option 1"
    And I should see "Unique textarea test"
    And I should see "textarea data"
    And I should see "Unique textinput test"
    And I should see "text data"

    # Upload csv containing the text input custom field with blank empty data.
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
    And I should not see "Unique checkbox test"

    # TODO:
    # "Empty strings erase existing data" setting does not erase date and datetime CF data. See TL-12770
    # There is a bug where date and datetime fields do not check unique values correctly. See TL-8741
    # When these are fixed the below can be un-commented.
    # And I should not see "Unique date (no timezone) test"
    # And I should not see "Date not set"
    # And I should not see "Unique date/time test"
    # And I should not see "Date not set"

    And I should not see "Unique dropdown menu test"
    And I should not see "menuoptiontwo"
    And I should not see "Unique textarea test"
    And I should not see "textarea data"

    # Upload another user with a empty custom field data to ensure empty is not counted towards the unique check.
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/user_profile_fields_unique_4.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
