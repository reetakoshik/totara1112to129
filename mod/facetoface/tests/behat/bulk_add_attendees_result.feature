@mod @mod_facetoface @totara @javascript
Feature: Add seminar attendees in bulk and see results
  In order to test the bulk add attendees information result
  As admin
  I need to create an event, upload attendees through the bulk add attendees options.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | idnumber   |email                 |
      | student1 | John1     | Smith1   | I1         | student1@example.com |
      | student2 | John2     | Smith2   | I2         | student2@example.com |
      | student3 | John3     | Smith3   | I3         | student3@example.com |
      | student4 | John4     | Smith4   |            | student4@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity   | name    | course | idnumber |
      | facetoface | Seminar | C1     | seminar  |
    And I log in as "admin"
    And I am on "Course 1" course homepage

  @_file_upload
  Scenario: Conflict result when choosing Add users via file upload option
    Given I follow "View all events"
    And I follow "Add a new event"
    And I press "Save changes"

    And I click on "Attendees" "link"
    And I click on "Add users via file upload" "option" in the "#menuf2f-actions" "css_element"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees.csv" file to "CSV file" filemanager
    And I press "Continue"
    When I press "Confirm"
    Then I should see "Uploaded via csv file" in the "John1 Smith1" "table_row"
    And I should see "Also uploaded via csv file" in the "John2 Smith2" "table_row"
    And I click on "Add users via file upload" "option" in the "#menuf2f-actions" "css_element"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees.csv" file to "CSV file" filemanager
    And I press "Continue"
    Then I should see "2 problem(s) encountered during import."
    When I click on "View results" "link"
    Then the following should exist in the "generaltable" table:
      | ID number | Name          | Result                                                   |
      | I1        | John1 Smith1  | This user is already signed-up for this seminar activity |
      | I2        | John2 Smith2  | This user is already signed-up for this seminar activity |

  Scenario: Conflict result when choosing Add users via list of IDs option
    Given I follow "View all events"
    And I follow "Add a new event"
    And I press "Save changes"

    And I click on "Attendees" "link"
    And I click on "Add users via list of IDs" "option" in the "#menuf2f-actions" "css_element"
    And I set the field "idfield" to "ID number"
    And I set the field "csvinput" to "I3"
    And I press "Continue"
    And I press "Confirm"
    When I click on "Add users via list of IDs" "option" in the "#menuf2f-actions" "css_element"
    And I set the field "idfield" to "ID number"
    And I set the field "csvinput" to "I3"
    And I press "Continue"
    Then I should see "1 problem(s) encountered during import."
    When I click on "View results" "link"
    Then the following should exist in the "generaltable" table:
      | ID number | Name          | Result                                                   |
      | I3        | John3 Smith3  | This user is already signed-up for this seminar activity |

  @_file_upload
  Scenario: Success result when choosing Add users via file upload option
    Given I follow "View all events"
    And I follow "Add a new event"
    And I press "Save changes"

    And I click on "Attendees" "link"
    And I click on "Add users via file upload" "option" in the "#menuf2f-actions" "css_element"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees.csv" file to "CSV file" filemanager
    And I press "Continue"
    When I press "Confirm"
    Then I should see "Uploaded via csv file" in the "John1 Smith1" "table_row"
    And I should see "Also uploaded via csv file" in the "John2 Smith2" "table_row"
    When I click on "View results" "link"
    Then the following should exist in the "generaltable" table:
      | ID number | Name          | Result             |
      | I1        | John1 Smith1  | Added successfully |
      | I2        | John2 Smith2  | Added successfully |

  Scenario: Success result when choosing Add users via list of IDs option
    Given I follow "View all events"
    And I follow "Add a new event"
    And I press "Save changes"

    And I click on "Attendees" "link"
    And I click on "Add users via list of IDs" "option" in the "#menuf2f-actions" "css_element"
    And I set the field "idfield" to "ID number"
    And I set the field "csvinput" to "I3"
    And I press "Continue"
    And I press "Confirm"
    When I click on "View results" "link"
    Then the following should exist in the "generaltable" table:
      | ID number | Name          | Result             |
      | I3        | John3 Smith3  | Added successfully |

  Scenario: Success result when choosing Add users and Remove users options
    Given I follow "View all events"
    And I follow "Add a new event"
    And I press "Save changes"

    And I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "John1 Smith1, student1@example.com" "option"
    And I click on "John2 Smith2, student2@example.com" "option"
    And I click on "John4 Smith4, student4@example.com" "option"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    When I click on "View results" "link"
    Then the following should exist in the "generaltable" table:
      | ID number | Name          | Result              |
      | I1        | John1 Smith1  | Added successfully  |
      | I2        | John2 Smith2  | Added successfully  |
      |           | John4 Smith4  | Added successfully  |
    And I press "Cancel"
    And I should see "John1 Smith1"
    And I should see "John2 Smith2"

    # Removing users.
    When I click on "Remove users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "John1 Smith1, student1@example.com" "option"
    And I press "Remove"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    And I should see "John2 Smith2"
    And I click on "View results" "link"
    Then the following should exist in the "generaltable" table:
      | ID number | Name          | Result                |
      | I1        | John1 Smith1  | Removed successfully  |
    And I press "Cancel"
    And I should not see "John1 Smith1"
