@mod @mod_facetoface @totara @javascript @totara_customfield
Feature: Manage pre-defined rooms
  In order to test seminar rooms
  As a site manager
  I need to create and allocate rooms

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                    |
      | teacher1 | Teacher   | One      | teacher1@example.invalid |
      | user1    | User      | One      | user1@example.invalid    |
      | user2    | User      | Two      | user2@example.invalid    |
      | user3    | User      | Three    | user3@example.invalid    |
      | user4    | User      | Four     | user4@example.invalid    |
      | user5    | User      | Five     | user5@example.invalid    |
      | user6    | User      | Six      | user6@example.invalid    |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | user1    | C1     | student        |
      | user2    | C1     | student        |
      | user3    | C1     | student        |
      | user4    | C1     | student        |
      | user5    | C1     | student        |
      | user6    | C1     | student        |
    And I log in as "admin"
    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name              | Room 1          |
      | Building          | That house      |
      | Address           | 123 here street |
      | Room capacity     | 5               |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

    And I press "Add a new room"
    And I set the following fields to these values:
      | Name              | Room 2          |
      | Building          | Your house      |
      | Address           | 123 near street |
      | Room capacity     | 6               |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

  Scenario: See that the rooms were created correctly
    Given I navigate to "Rooms" node in "Site administration > Seminars"
    Then I should see "That house" in the "Room 1" "table_row"
    And I should see "123 here street" in the "Room 1" "table_row"
    And I should see "5" in the "Room 1" "table_row"

    Then I should see "Your house" in the "Room 2" "table_row"
    And I should see "123 near street" in the "Room 2" "table_row"
    And I should see "6" in the "Room 2" "table_row"

    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add a new event"
    When I click on "Select room" "link"
    Then I should see "Room 1, That house, 123 here street (Capacity: 5)" in the "Choose a room" "totaradialogue"
    And I should see "Room 2, Your house, 123 near street (Capacity: 6)" in the "Choose a room" "totaradialogue"

  Scenario: Fill a room
    Given I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
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
      | capacity           | 7   |
    When I click on "Select room" "link"
    And I wait "1" seconds
    And I click on "Room 1, That house, 123 here street (Capacity: 5)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I wait "1" seconds
    And I press "Use room capacity"
    And I wait "1" seconds
    And I press "Save changes"

    When I click on "Attendees" "link"
    And I set the field "menuf2f-actions" to "Add users"
    And I wait "1" seconds
    And I click on "User One, user1@example.invalid" "option"
    And I click on "User Two, user2@example.invalid" "option"
    And I click on "User Three, user3@example.invalid" "option"
    And I click on "User Four, user4@example.invalid" "option"
    And I click on "User Five, user5@example.invalid" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    Then I should see "User One"
    And I should see "User Two"
    And I should see "User Three"
    And I should see "User Four"
    And I should see "User Five"
    And I should see "Bulk add attendees success - Successfully added/edited 5 attendees."
    And I should not see "This session is overbooked"

    And I set the field "menuf2f-actions" to "Add users"
    And I wait "1" seconds
    And I click on "User Six, user6@example.invalid" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    Then I should see "User Six"
    And I should see "This event is overbooked"

  Scenario: Try and clash a room
    Given I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I set the following fields to these values:
      | capacity           | 5    |
    When I click on "Select room" "link"
    And I wait "1" seconds
    And I click on "Room 1, That house, 123 here street (Capacity: 5)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"

    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    When I click on "Select room" "link"
    And I wait "1" seconds
    Then I should see "(Room unavailable)" in the "Choose a room" "totaradialogue"
    And I click on "Cancel" "button" in the "Choose a room" "totaradialogue"
    And I wait "1" seconds
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 14   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 15   |
      | timefinish[minute] | 0    |
    And I press "OK"
    When I click on "Select room" "link"
    And I click on "Room 1, That house, 123 here street (Capacity: 5)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I wait "1" seconds
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 0    |
    And I press "OK"
    And I should see "The new dates you have selected are unavailable due to a scheduling conflict"
    And I click on "Cancel" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    Then I should see "Room 1" in the "1 January 2020" "table_row"

  Scenario: Clash a room with different timezones
    Given I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]       | 1                |
      | timestart[month]     | 1                |
      | timestart[year]      | 2020             |
      | timestart[hour]      | 19               |
      | timestart[minute]    | 0                |
      | timestart[timezone]  | Pacific/Auckland |
      | timefinish[day]      | 1                |
      | timefinish[month]    | 1                |
      | timefinish[year]     | 2020             |
      | timefinish[hour]     | 20               |
      | timefinish[minute]   | 0                |
      | timefinish[timezone] | Pacific/Auckland |
    And I press "OK"
    And I set the following fields to these values:
      | capacity                | 7                |
    When I click on "Select room" "link"
    And I wait "1" seconds
    And I click on "Room 1, That house, 123 here street (Capacity: 5)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"

    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]       | 1             |
      | timestart[month]     | 1             |
      | timestart[year]      | 2020          |
      | timestart[hour]      | 6             |
      | timestart[minute]    | 0             |
      | timestart[timezone]  | Europe/London |
      | timefinish[day]      | 1             |
      | timefinish[month]    | 1             |
      | timefinish[year]     | 2020          |
      | timefinish[hour]     | 7             |
      | timefinish[minute]   | 0             |
      | timefinish[timezone] | Europe/London |
    And I press "OK"
    And I set the following fields to these values:
      | capacity                | 7             |
    When I click on "Select room" "link"
    And I wait "1" seconds
    Then I should see "(Room unavailable)" in the "Choose a room" "totaradialogue"
    And I click on "Cancel" "button" in the "Choose a room" "totaradialogue"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]       | 1             |
      | timestart[month]     | 1             |
      | timestart[year]      | 2020          |
      | timestart[hour]      | 14            |
      | timestart[minute]    | 0             |
      | timestart[timezone]  | Europe/London |
      | timefinish[day]      | 1             |
      | timefinish[month]    | 1             |
      | timefinish[year]     | 2020          |
      | timefinish[hour]     | 15            |
      | timefinish[minute]   | 0             |
      | timefinish[timezone] | Europe/London |
    And I press "OK"
    And I wait "1" seconds
    When I click on "Select room" "link"
    And I click on "Room 1, That house, 123 here street (Capacity: 5)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I wait "1" seconds
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]       | 1             |
      | timestart[month]     | 1             |
      | timestart[year]      | 2020          |
      | timestart[hour]      | 6             |
      | timestart[minute]    | 0             |
      | timestart[timezone]  | Europe/London |
      | timefinish[day]      | 1             |
      | timefinish[month]    | 1             |
      | timefinish[year]     | 2020          |
      | timefinish[hour]     | 7             |
      | timefinish[minute]   | 0             |
      | timefinish[timezone] | Europe/London |
    And I press "OK"
    And I should see "The new dates you have selected are unavailable due to a scheduling conflict"
    And I click on "Cancel" "button" in the "Select date" "totaradialogue"
    And I click on "Delete" "link" in the ".f2fmanagedates" "css_element"
    And I press "Save changes"
    Then I should see "Room 1" in the "1 January 2020" "table_row"
