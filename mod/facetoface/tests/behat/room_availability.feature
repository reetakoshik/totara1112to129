@mod @mod_facetoface @totara @javascript
Feature: Seminar room availability
  In order to prevent room conflicts
  As an editing trainer
  I need to see only available rooms

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | teacher2 | Teacher   | Two      | teacher2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | editingteacher |
    And I log in as "admin"
    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name                         | Room 1          |
      | Room capacity                | 10              |
      | Allow room booking conflicts | 0               |
    And I press "Add a room"
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name                         | Room 2          |
      | Room capacity                | 10              |
      | Allow room booking conflicts | 1               |
    And I press "Add a room"
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name                         | Room 3          |
      | Room capacity                | 10              |
      | Allow room booking conflicts | 0               |
    And I press "Add a room"
    And I click on "Hide from users when choosing a room on the Add/Edit event page" "link" in the "Room 3" "table_row"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test Seminar 1 |
      | Description | test           |
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test Seminar 2 |
      | Description | test           |
    And I log out

  Scenario: Time based seminar room conflicts
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test Seminar 1"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Room 1 (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I click on "Select room" "link"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Add a new session"
    # The UI is not usable much here, we just save this and go back and the last added session will be listed first.
    And I press "Save changes"
    And I click on "Edit event" "link" in the "0 / 10" "table_row"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2026 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2026 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Room 2 (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Add a new session"
    # The UI is not usable much here, we just save this and go back and the last added session will be listed first.
    And I press "Save changes"
    And I click on "Edit event" "link" in the "0 / 10" "table_row"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 12   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 13   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Room 1 (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I should see "Room 1 (10)" in the "1 January 2025 1:00 PM" "table_row"
    And I should see "Room 1 (10)" in the "1 January 2025 11:00 AM" "table_row"
    And I should see "Room 2 (10)" in the "January 2026" "table_row"
    And I press "Save changes"

    When I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 20 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 10   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 11   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Room 1 (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"
    Then I should see "Room 1" in the "0 / 20" "table_row"

    When I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 30 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 13   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 14   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Room 1 (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"
    Then I should see "Room 1" in the "0 / 30" "table_row"

    When I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 40 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2026 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2026 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Room 2 (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"
    Then I should see "Room 2" in the "0 / 40" "table_row"

    When I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 50 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    Then I should see "Room 1 (Capacity: 10) (Room unavailable)"
    When I click on "Cancel" "button" in the "Choose a room" "totaradialogue"
    And I press "Cancel"

    And I click on "Edit event" "link" in the "0 / 20" "table_row"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I should see "Room 1 is already booked"
    When I click on "Cancel" "button" in the "Select date" "totaradialogue"
    And I press "Cancel"

  Scenario: Hiding related seminar room availability
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test Seminar 1"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 20 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Room 1 (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I click on "Select room" "link"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"
    And I log out
    And I log in as "admin"
    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I click on "Hide from users when choosing a room on the Add/Edit event page" "link" in the "Room 1" "table_row"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test Seminar 1"

    When I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2026 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2026 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    Then I should not see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Cancel" "button" in the "Choose a room" "totaradialogue"
    And I press "Cancel"

    When I click on "Edit event" "link" in the "0 / 20" "table_row"
    And I click on "Select room" "link"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Cancel" "button" in the "Choose a room" "totaradialogue"
    And I press "Add a new session"
    # The UI is not usable much here, we just save this and go back and the last added session will be listed first.
    And I press "Save changes"
    And I click on "Edit event" "link" in the "0 / 20" "table_row"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2026 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2026 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    Then I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Room 1 (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"
    And I should see "Upcoming events"

  Scenario: Custom seminar room availability
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test Seminar 1"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 30 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    And I click on "Create new room" "link" in the "Choose a room" "totaradialogue"
    And I set the following fields to these values:
      | Name                         | Zimmer 1 |
      | roomcapacity                 | 30       |
      | Allow room booking conflicts | 0        |
    And I click on "//div[@aria-describedby='editcustomroom0-dialog']//div[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element"

    When  I press "Add a new session"
    # The UI is not usable much here, we just save this and go back and the last added session will be listed first.
    And I press "Save changes"
    And I click on "Edit event" "link" in the "0 / 30" "table_row"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 12   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 13   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    Then I should see "Zimmer 1 (Capacity: 30) (Seminar: Test Seminar 1)"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Zimmer 1 (Capacity: 30) (Seminar: Test Seminar 1)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"
    And I should see "Upcoming events"

    When I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 40 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    Then I should see "Zimmer 1 (Capacity: 30) (Room unavailable) (Seminar: Test Seminar 1)"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Create new room" "link" in the "Choose a room" "totaradialogue"
    And I set the following fields to these values:
      | Name                         | Zimmer 2 |
      | roomcapacity                 | 40       |
      | Allow room booking conflicts | 0        |
    And I click on "//div[@aria-describedby='editcustomroom0-dialog']//div[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element"
    And I click on "Delete" "link" in the "Zimmer 2" "table_row"
    And I press "Save changes"
    And I should not see "Zimmer 2" in the "0 / 40" "table_row"

    When I click on "Edit event" "link" in the "0 / 40" "table_row"
    And I click on "Select room" "link"
    Then I should see "Zimmer 1 (Capacity: 30) (Room unavailable) (Seminar: Test Seminar 1)"
    And I should see "Zimmer 2 (Capacity: 40) (Seminar: Test Seminar 1)"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Cancel" "button" in the "Choose a room" "totaradialogue"
    And I press "Cancel"

    When I am on "Course 1" course homepage
    And I follow "Test Seminar 2"
    And I follow "Add a new event"
    And I click on "Select room" "link"
    Then I should not see "Zimmer 1"
    And I should see "Zimmer 2 (Capacity: 40) (Seminar: Test Seminar 2)"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Cancel" "button" in the "Choose a room" "totaradialogue"
    And I press "Cancel"
    And I log out

    When I log in as "teacher2"
    And I am on "Course 1" course homepage
    And I follow "Test Seminar 2"
    And I follow "Add a new event"
    And I click on "Select room" "link"
    Then I should not see "Zimmer 1"
    And I should not see "Zimmer 2"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Cancel" "button" in the "Choose a room" "totaradialogue"
    And I press "Cancel"

    When I am on "Course 1" course homepage
    And I follow "Test Seminar 1"
    And I follow "Add a new event"
    And I click on "Select room" "link"
    Then I should see "Zimmer 1 (Capacity: 30) (Seminar: Test Seminar 1)"
    And I should not see "Zimmer 2"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Cancel" "button" in the "Choose a room" "totaradialogue"
    And I press "Cancel"

  Scenario: Seminar switch site room to not allow conflicts
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test Seminar 1"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 20 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    And I click on "Room 2 (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 30 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    And I click on "Room 2 (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    When I press "Save changes"
    Then I should see "Room 2" in the "0 / 20" "table_row"
    And I should see "Room 2" in the "0 / 30" "table_row"

    When I navigate to "Rooms" node in "Site administration > Seminars"
    And I click on "Edit" "link" in the "Room 2" "table_row"
    And I set the following fields to these values:
      | Allow room booking conflicts | 0               |
    And I press "Save changes"
    Then I should see "Room has conflicting usage"
    And I press "Cancel"

    When I am on "Course 1" course homepage
    And I follow "Test Seminar 1"
    And I click on "Edit event" "link" in the "0 / 30" "table_row"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 12   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 13   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I click on "Edit" "link" in the "Room 2" "table_row"
    And I set the following fields to these values:
      | Allow room booking conflicts | 0               |
    And I press "Save changes"
    Then I should not see "Room has conflicting usage"

  Scenario: Seminar switch custom room to not allow conflicts
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test Seminar 1"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 40 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    And I click on "Create new room" "link" in the "Choose a room" "totaradialogue"
    And I set the following fields to these values:
      | Name                         | Zimmer 1 |
      | roomcapacity                 | 40       |
      | Allow room booking conflicts | 1        |
    And I click on "//div[@aria-describedby='editcustomroom0-dialog']//div[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element"
    And I press "Save changes"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 50 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    And I click on "Zimmer 1 (Capacity: 40) (Seminar: Test Seminar 1)" "link"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"
    Then I should see "Zimmer 1" in the "0 / 40" "table_row"
    And I should see "Zimmer 1" in the "0 / 50" "table_row"

    When I click on "Edit event" "link" in the "0 / 50" "table_row"
    And I click on "Edit room" "link" in the "Zimmer 1 (40)" "table_row"
    And I set the following fields to these values:
      | Allow room booking conflicts | 0 |
    And I click on "//div[@aria-describedby='editcustomroom0-dialog']//div[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element"
    Then I should see "Room has conflicting usage" in the "Edit room" "totaradialogue"
    And I click on "Cancel" "button" in the "Edit room" "totaradialogue"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 12   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 13   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    When I click on "Edit event" "link" in the "0 / 50" "table_row"
    And I click on "Edit room" "link" in the "Zimmer 1 (40)" "table_row"
    And I set the following fields to these values:
      | Allow room booking conflicts | 0 |
    And I click on "//div[@aria-describedby='editcustomroom0-dialog']//div[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element"
    Then I should not see "Room has conflicting usage"
    And I press "Save changes"

  Scenario: Reportbuilder seminar room availability filter
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test Seminar 1"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 20 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    And I click on "Room 1 (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 30 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 13   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 14   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    And I click on "Room 1 (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 30 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 15   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 16   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    And I click on "Room 2 (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"
    And I navigate to "Rooms" node in "Site administration > Seminars"

    # NOTE: We cannot use "Search" button because there is already "Search by" aria button above.

    When I set the following fields to these values:
      | room-roomavailable_enable        | Free between the following times |
      | room-roomavailable_start[day]    | 1                                |
      | room-roomavailable_start[month]  | January                          |
      | room-roomavailable_start[year]   | 2025                             |
      | room-roomavailable_start[hour]   | 10                               |
      | room-roomavailable_start[minute] | 00                               |
      | room-roomavailable_end[day]      | 1                                |
      | room-roomavailable_end[month]    | January                          |
      | room-roomavailable_end[year]     | 2025                             |
      | room-roomavailable_end[hour]     | 11                               |
      | room-roomavailable_end[minute]   | 00                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should see "Room 1"
    And I should see "Room 2"
    And I should see "Room 3"

    When I set the following fields to these values:
      | room-roomavailable_start[day]    | 1                                |
      | room-roomavailable_start[month]  | January                          |
      | room-roomavailable_start[year]   | 2025                             |
      | room-roomavailable_start[hour]   | 10                               |
      | room-roomavailable_start[minute] | 00                               |
      | room-roomavailable_end[day]      | 1                                |
      | room-roomavailable_end[month]    | January                          |
      | room-roomavailable_end[year]     | 2025                             |
      | room-roomavailable_end[hour]     | 11                               |
      | room-roomavailable_end[minute]   | 01                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should not see "Room 1"
    And I should see "Room 2"
    And I should see "Room 3"

    When I set the following fields to these values:
      | room-roomavailable_start[day]    | 1                                |
      | room-roomavailable_start[month]  | January                          |
      | room-roomavailable_start[year]   | 2025                             |
      | room-roomavailable_start[hour]   | 11                               |
      | room-roomavailable_start[minute] | 30                               |
      | room-roomavailable_end[day]      | 1                                |
      | room-roomavailable_end[month]    | January                          |
      | room-roomavailable_end[year]     | 2025                             |
      | room-roomavailable_end[hour]     | 12                               |
      | room-roomavailable_end[minute]   | 30                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should not see "Room 1"
    And I should see "Room 2"
    And I should see "Room 3"

    When I set the following fields to these values:
      | room-roomavailable_start[day]    | 1                                |
      | room-roomavailable_start[month]  | January                          |
      | room-roomavailable_start[year]   | 2025                             |
      | room-roomavailable_start[hour]   | 12                               |
      | room-roomavailable_start[minute] | 59                               |
      | room-roomavailable_end[day]      | 1                                |
      | room-roomavailable_end[month]    | January                          |
      | room-roomavailable_end[year]     | 2025                             |
      | room-roomavailable_end[hour]     | 14                               |
      | room-roomavailable_end[minute]   | 00                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should not see "Room 1"
    And I should see "Room 2"
    And I should see "Room 3"

    When I set the following fields to these values:
      | room-roomavailable_start[day]    | 1                                |
      | room-roomavailable_start[month]  | January                          |
      | room-roomavailable_start[year]   | 2025                             |
      | room-roomavailable_start[hour]   | 10                               |
      | room-roomavailable_start[minute] | 00                               |
      | room-roomavailable_end[day]      | 1                                |
      | room-roomavailable_end[month]    | January                          |
      | room-roomavailable_end[year]     | 2025                             |
      | room-roomavailable_end[hour]     | 14                               |
      | room-roomavailable_end[minute]   | 00                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should not see "Room 1"
    And I should see "Room 2"
    And I should see "Room 3"

    When I set the following fields to these values:
      | room-roomavailable_start[day]    | 1                                |
      | room-roomavailable_start[month]  | January                          |
      | room-roomavailable_start[year]   | 2025                             |
      | room-roomavailable_start[hour]   | 14                               |
      | room-roomavailable_start[minute] | 00                               |
      | room-roomavailable_end[day]      | 1                                |
      | room-roomavailable_end[month]    | January                          |
      | room-roomavailable_end[year]     | 2025                             |
      | room-roomavailable_end[hour]     | 15                               |
      | room-roomavailable_end[minute]   | 00                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should see "Room 1"
    And I should see "Room 2"
    And I should see "Room 3"

    When I set the following fields to these values:
      | room-roomavailable_start[day]    | 1                                |
      | room-roomavailable_start[month]  | January                          |
      | room-roomavailable_start[year]   | 2001                             |
      | room-roomavailable_start[hour]   | 10                               |
      | room-roomavailable_start[minute] | 00                               |
      | room-roomavailable_end[day]      | 1                                |
      | room-roomavailable_end[month]    | January                          |
      | room-roomavailable_end[year]     | 2030                             |
      | room-roomavailable_end[hour]     | 14                               |
      | room-roomavailable_end[minute]   | 00                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should not see "Room 1"
    And I should see "Room 2"
    And I should see "Room 3"
