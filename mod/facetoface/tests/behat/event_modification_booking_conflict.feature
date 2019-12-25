@mod @mod_facetoface @totara @javascript
Feature: I cannot edit seminar session dates that will result in booking conflicts for attendees
  In order to ensure no booking conflicts are made when editing session dates
  As admin
  I need to create different events with attendees and change session dates.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
      | student3 | Sam3      | Student3 | student3@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity   | name              | course | idnumber |
      | facetoface | Test seminar1     | C1     | seminar1 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "Test seminar1"
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
      | capacity           | 5    |
    And I press "Save changes"
    And I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I set the following fields to these values:
      | searchtext | Sam |
    And I press "Search"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Add"
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Sam1 Student1"
    Then I should see "Sam2 Student2"

  # Create a wait-list, add users to it and change session dates that result in conflict.
  Scenario: change wait-list to session dates with booking conflicts
    Given I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar2             |
      | Description | Test seminar2 description |
    And I follow "Test seminar2"
    And I follow "Add a new event"
    And I click on "Delete" "link"
    And I set the following fields to these values:
      | capacity                  | 5   |
    And I press "Save changes"
    When I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I set the following fields to these values:
      | searchtext | Sam |
    And I press "Search"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Add"
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I press "Add"
    And I click on "Sam3 Student3, student3@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    And I follow "Wait-list"
    Then I should see "Sam1 Student1"
    Then I should see "Sam2 Student2"
    Then I should see "Sam3 Student3"
    And I follow "Test seminar2"
    And I click on "Edit event" "link"
    And I click on "Add a new session" "button"
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
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    Then I should see "Booking conflict: 2 user(s) have another booking on the selected date and time. Change event time or exclude these users to continue."

    When I click on "View details" "link"
    Then I should see "Sam1 Student1 is already booked to attend Test seminar1 at 11:00 AM to 12:00 PM on 1 January 2020. Please select another user or change the session"
    Then I should see "Sam2 Student2 is already booked to attend Test seminar1 at 11:00 AM to 12:00 PM on 1 January 2020. Please select another user or change the session"
    Then I should not see "Sam3 Student3"

  # Create an event, add users to it and change session dates that result in conflict.
  Scenario: change event dates that result in booking conflicts
    Given I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar2             |
      | Description | Test seminar2 description |
    And I follow "Test seminar2"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 10   |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 10   |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I set the following fields to these values:
      | capacity                  | 5   |
    And I press "Save changes"
    When I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I set the following fields to these values:
      | searchtext | Sam |
    And I press "Search"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Add"
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I press "Add"
    And I click on "Sam3 Student3, student3@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Sam1 Student1"
    Then I should see "Sam2 Student2"
    Then I should see "Sam3 Student3"
    And I follow "Test seminar2"
    And I click on "Edit event" "link"
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
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    Then I should see "Booking conflict: 2 user(s) have another booking on the selected date and time. Change event time or exclude these users to continue."

    When I click on "View details" "link"
    Then I should see "Sam1 Student1 is already booked to attend Test seminar1 at 11:00 AM to 12:00 PM on 1 January 2020. Please select another user or change the session"
    Then I should see "Sam2 Student2 is already booked to attend Test seminar1 at 11:00 AM to 12:00 PM on 1 January 2020. Please select another user or change the session"
    Then I should not see "Sam3 Student3"

  # Create a seminar and an event with the same day than seminar1 and check it's saved because there are not booking conflicts.
  Scenario: seminar with same dates can be created if they don't lead to booking conflicts
    Given I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar2             |
      | Description | Test seminar2 description |
    And I follow "Test seminar2"
    When I follow "Add a new event"
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
      | capacity                  | 5   |
    And I press "Save changes"
    Then I should see "1 January 2020"
    And I should not see "Booking conflict"

  # Modify an event to have the same dates than seminar1 and check it's saved because there are not booking conflicts.
  Scenario: seminar events can be modified to have same dates as others if they don't lead to booking conflicts
    Given I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar2             |
      | Description | Test seminar2 description |
    And I follow "Test seminar2"
    And I follow "Add a new event"
    And I click on "Delete" "link"
    And I set the following fields to these values:
      | capacity                  | 5   |
    And I press "Save changes"
    And I click on "Edit event" "link"
    And I click on "Add a new session" "button"
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
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    Then I should see "1 January 2020"
    And I should not see "Booking conflict"

