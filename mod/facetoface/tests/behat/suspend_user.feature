@mod @mod_facetoface @totara
Feature: Suspend user in different session times
  In order to test the suspended user in Face to face
  As admin
  I need to keep or remove the suspend user in/from session

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity   | name              | multiplesessions | course | idnumber |
      | facetoface | Test seminar name | 1                | C1     | seminar  |

  @javascript
  Scenario: Create sessions with different dates and add users to a face to face sessions
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"

    # Session in the fututre
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I set the following fields to these values:
      | Maximum bookings | 1 |
    And I press "Save changes"

    When I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Add"
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Sam1 Student1"
    And I should see "Sam2 Student2"
    And I click on "Go back" "link"

    # Session is wait-listed
    And I follow "Add a new event"
    And I click on "Delete" "link" in the ".f2fmanagedates" "css_element"
    And I set the following fields to these values:
      | Maximum bookings | 2 |
    And I press "Save changes"

    When I click on "Attendees" "link" in the "Wait-listed" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Add"
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    And I follow "Wait-list"
    Then I should see "Sam1 Student1"
    And I should see "Sam2 Student2"
    And I click on "Go back" "link"

    # Session in the past
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2015 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2015 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I set the following fields to these values:
      | Maximum bookings | 2 |
    And I press "Save changes"

    When I click on "Attendees" "link" in the "Event over" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Add"
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Sam1 Student1"
    And I should see "Sam2 Student2"
    And I click on "Go back" "link"

    # Session in progress
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | timestart[day]     | 0    |
      | timestart[month]   | 0    |
      | timestart[year]    | 0    |
      | timestart[hour]    | 0    |
      | timestart[minute]  | -30  |
      | timefinish[day]    | 0    |
      | timefinish[month]  | 0    |
      | timefinish[year]   | 0    |
      | timefinish[hour]   | 0    |
      | timefinish[minute] | +30  |
    And I press "OK"
    And I set the following fields to these values:
      | Maximum bookings | 2 |
    And I press "Save changes"

    When I click on "Attendees" "link" in the "Event in progress" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Add"
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Sam1 Student1"
    And I should see "Sam2 Student2"
    And I click on "Go back" "link"

    # Suspend Sam1 Student1 user
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Suspend Sam1 Student1" "link" in the "Sam1 Student1" "table_row"
    And I wait until the page is ready

    And I am on "Course 1" course homepage
    And I follow "Test seminar name"

    # Check the result
    When I click on "Attendees" "link" in the "Booking full" "table_row"
    Then I should not see "Sam1 Student1"
    And I should see "Sam2 Student2"

    And I click on "Go back" "link"

    When I click on "Attendees" "link" in the "Wait-listed" "table_row"
    And I follow "Wait-list"
    Then I should not see "Sam1 Student1"
    And I should see "Sam2 Student2"

    And I click on "Go back" "link"
    When I click on "Attendees" "link" in the "Event over" "table_row"
    Then I should see "Sam1 Student1"
    And I should see "Sam2 Student2"

    And I click on "Go back" "link"

    When I click on "Attendees" "link" in the "Event in progress" "table_row"
    Then I should see "Sam1 Student1"
    And I should see "Sam2 Student2"
