@mod @mod_facetoface @totara @javascript @totara_customfield
Feature: Add seminar attendess from csv file with custom fields
  In order to test the bulk add attendees from file
  As a site manager
  I need to create an event, create sign-up custom fields and upload csv file with custom fields using bulk add attendees from file.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | John1     | Smith1   | student1@example.com |
      | student2 | John2     | Smith2   | student2@example.com |
      | student3 | John3     | Smith3   | student3@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity   | name            | course | idnumber |
      | facetoface | Seminar TL-9159 | C1     | seminar  |
    And I log in as "admin"
    And I navigate to "Custom fields" node in "Site administration > Seminars"
    And I click on "Sign-up" "link"

    And I click on "Checkbox" "option"
    And I set the following fields to these values:
      | Full name                   | Event checkbox |
      | Short name (must be unique) | checkbox       |
    And I press "Save changes"

    And I click on "Date/time" "option"
    And I set the following fields to these values:
      | Full name                   | Event date/time |
      | Short name (must be unique) | datetime        |
    And I press "Save changes"


  @_file_upload
  Scenario: Login as manager, upload csv file with custom fields using bulk add attendees from file and check the result.

    And I click on "Menu of choices" "option"
    And I set the following fields to these values:
      | Full name                   | Event menu of choices |
      | Short name (must be unique) | menuofchoices         |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Apple
      Orange
      Banana
      """
    And I press "Save changes"

    And I click on "Multi-select" "option"
    And I set the following fields to these values:
      | Full name                   | Event multi select |
      | Short name (must be unique) | multiselect        |
      | multiselectitem[0][option]  | Tui                |
      | multiselectitem[1][option]  | Moa                |
      | multiselectitem[2][option]  | Tuatara            |
    And I press "Save changes"

    And I click on "Text area" "option"
    And I set the following fields to these values:
      | Full name                   | Event text area |
      | Short name (must be unique) | textarea        |
    And I press "Save changes"

    And I click on "Text input" "option"
    And I set the following fields to these values:
      | Full name                   | Event text input |
      | Short name (must be unique) | textinput        |
    And I press "Save changes"

    And I click on "URL" "option"
    And I set the following fields to these values:
      | Full name                   | Event address |
      | Short name (must be unique) | url           |
    And I press "Save changes"

    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Add a new event"
    And I press "Save changes"

    And I click on "Attendees" "link"
    And I click on "Add users via file upload" "option" in the "#menuf2f-actions" "css_element"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_customfields.csv" file to "CSV file" filemanager
    And I press "Continue"
    When I press "Confirm"
    Then I should see "Uploaded via csv file" in the "John1 Smith1" "table_row"
    And I should see "Yes" in the "John1 Smith1" "table_row"
    And I should see "2 Mar 2020" in the "John1 Smith1" "table_row"
    And I should see "Apple" in the "John1 Smith1" "table_row"
    And I should see "Tui, Moa" in the "John1 Smith1" "table_row"
    And I should see "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua." in the "John1 Smith1" "table_row"
    And I should see "Lorem ipsum dolor sit amet" in the "John1 Smith1" "table_row"
    And I should see "http://www.totaralearning.com" in the "John1 Smith1" "table_row"

    And I should see "Also uploaded via csv file" in the "John2 Smith2" "table_row"
    And I should see "Yes" in the "John2 Smith2" "table_row"
    And I should see "3 Apr 2021" in the "John2 Smith2" "table_row"
    And I should see "Orange" in the "John2 Smith2" "table_row"
    And I should see "Moa, Tuatara" in the "John2 Smith2" "table_row"
    And I should see "Consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua." in the "John2 Smith2" "table_row"
    And I should see "Consectetur adipisicing elit" in the "John2 Smith2" "table_row"
    And I should see "https://google.com" in the "John2 Smith2" "table_row"

    And I should see "More uploaded via csv file" in the "John3 Smith3" "table_row"
    And I should see "No" in the "John3 Smith3" "table_row"
    And I should see "4 May 2022" in the "John3 Smith3" "table_row"
    And I should see "Banana" in the "John3 Smith3" "table_row"
    And I should see "Tuatara" in the "John3 Smith3" "table_row"
    And I should see "Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua." in the "John3 Smith3" "table_row"
    And I should see "Sed do eiusmod tempor incididunt" in the "John3 Smith3" "table_row"
    And I should see "/mod/facetoface/view.php?id=1" in the "John3 Smith3" "table_row"

  @_file_upload
  Scenario: Invalid CSV format, where header and colums are missed

    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Add a new event"
    And I press "Save changes"

    And I click on "Attendees" "link"
    And I click on "Add users via file upload" "option" in the "#menuf2f-actions" "css_element"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_customfields_invalid_columns.csv" file to "CSV file" filemanager
    When I press "Continue"
    Then I should see "Invalid CSV file format - \"checkbox\" custom field does not exist"

  @_file_upload
  Scenario: Invalid CSV format, one of the custom field values is missed

    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Add a new event"
    And I press "Save changes"

    And I click on "Attendees" "link"
    And I click on "Add users via file upload" "option" in the "#menuf2f-actions" "css_element"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_customfields_invalid_columns2.csv" file to "CSV file" filemanager
    When I press "Continue"
    Then I should see "Invalid CSV file format - number of columns is not constant!"
