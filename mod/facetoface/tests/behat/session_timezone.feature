@mod @mod_facetoface @totara @totara_customfield
Feature: Seminar session date with timezone management
  In order to set up a session
  As an administrator
  I need to be able to use timezones

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                | timezone        |
      | teacher1 | Terry     | Teacher  | teacher1@example.com | Australia/Perth |
      | teacher2 | Herry     | Tutor    | teacher2@example.com | Europe/Prague   |
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
      | Name              | Room 1          |
      | Building          | Building 123    |
      | Address           | 123 Tory street |
      | Room capacity     | 10              |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

    And I press "Add a new room"
    And I set the following fields to these values:
      | Name             | Room 2          |
      | Building         | Building 234    |
      | Address          | 234 Tory street |
      | Room capacity    | 10              |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

    And I press "Add a new room"
    And I set the following fields to these values:
      | Name             | Room 3          |
      | Building         | Building 345    |
      | Address          | 345 Tory street |
      | Room capacity    | 10              |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"
    And I log out

  @javascript
  Scenario: Create seminar session by teacher in one timezone, check that timezones stored correctly, and check be teacher in another timezone
    Given I log in as "teacher1"
    And I wait "1" seconds
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And the field "sessiontimezone" matches value "User timezone"
    And I set the following fields to these values:
      | sessiontimezone      | Pacific/Auckland |
      | timestart[day]       | 2                |
      | timestart[month]     | 1                |
      | timestart[year]      | 2020             |
      | timestart[hour]      | 3                |
      | timestart[minute]    | 0                |
      | timestart[timezone]  | Europe/Prague    |
      | timefinish[day]      | 2                |
      | timefinish[month]    | 1                |
      | timefinish[year]     | 2020             |
      | timefinish[hour]     | 4                |
      | timefinish[minute]   | 0                |
      | timefinish[timezone] | Europe/Prague    |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    When I click on "Select room" "link"
    And I wait "1" seconds
    And I click on "Room 1" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"

    And I press "Add a new session"
    And I click on "Edit session" "link" in the ".f2fmanagedates .lastrow" "css_element"
    And I set the following fields to these values:
      | sessiontimezone      | User timezone |
      | timestart[day]       | 3             |
      | timestart[month]     | 2             |
      | timestart[year]      | 2021          |
      | timestart[hour]      | 9             |
      | timestart[minute]    | 0             |
      | timestart[timezone]  | Europe/London |
      | timefinish[day]      | 3             |
      | timefinish[month]    | 2             |
      | timefinish[year]     | 2021          |
      | timefinish[hour]     | 11            |
      | timefinish[minute]   | 0             |
      | timefinish[timezone] | Europe/Prague |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    When I click on "Select room" "link" in the ".f2fmanagedates .lastrow" "css_element"
    And I click on "Room 2" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    When I press "Save changes"
    Then I should see "3:00 PM - 4:00 PM Pacific/Auckland" in the "Room 1" "table_row"
    And I should see "5:00 PM - 6:00 PM Australia/Perth" in the "Room 2" "table_row"

    When I click on "Edit" "link" in the "Room 1" "table_row"
    And I click on "Edit session" "link"
    Then I set the following fields to these values:
      | sessiontimezone      | Pacific/Auckland |
      | timestart[day]       | 2                |
      | timestart[month]     | January          |
      | timestart[year]      | 2020             |
      | timestart[hour]      | 15               |
      | timestart[minute]    | 00               |
      | timestart[timezone]  | Pacific/Auckland |
      | timefinish[day]      | 2                |
      | timefinish[month]    | January          |
      | timefinish[year]     | 2020             |
      | timefinish[hour]     | 16               |
      | timefinish[minute]   | 00               |
      | timefinish[timezone] | Pacific/Auckland |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    When I click on "Edit" "link" in the "Room 1" "table_row"
    And I click on "Edit session" "link" in the ".f2fmanagedates .lastrow" "css_element"
    Then I set the following fields to these values:
      | sessiontimezone      | User timezone    |
      | timestart[day]       | 3                |
      | timestart[month]     | February         |
      | timestart[year]      | 2021             |
      | timestart[hour]      | 17               |
      | timestart[minute]    | 00               |
      | timestart[timezone]  | Australia/Perth  |
      | timefinish[day]      | 3                |
      | timefinish[month]    | February         |
      | timefinish[year]     | 2021             |
      | timefinish[hour]     | 18               |
      | timefinish[minute]   | 00               |
      | timefinish[timezone] | Australia/Perth  |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    When I press "Add a new session"
    And I click on "Edit session" "link" in the ".f2fmanagedates .lastrow" "css_element"
    Then I set the following fields to these values:
      | sessiontimezone      | Pacific/Auckland |
      | timestart[timezone]  | Pacific/Auckland |
      | timefinish[timezone] | Pacific/Auckland |

    And I set the following fields to these values:
      | timestart[day]       | 4             |
      | timestart[month]     | 4             |
      | timestart[year]      | 2022          |
      | timestart[hour]      | 1             |
      | timestart[minute]    | 00            |
      | timefinish[day]      | 4             |
      | timefinish[month]    | 4             |
      | timefinish[year]     | 2022          |
      | timefinish[hour]     | 2             |
      | timefinish[minute]   | 00            |
      | sessiontimezone      | Europe/Prague |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    When I click on "Select room" "link" in the ".f2fmanagedates .lastrow" "css_element"
    And I click on "Room 3" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"

    When I press "Save changes"
    Then I should see "3:00 PM - 4:00 PM Pacific/Auckland" in the "Room 1" "table_row"
    And I should see "5:00 PM - 6:00 PM Australia/Perth" in the "Room 2" "table_row"
    And I should see "3:00 PM - 4:00 PM Europe/Prague" in the "Room 3" "table_row"

    When I log out
    And I log in as "teacher2"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    Then I should see "3:00 PM - 4:00 PM Pacific/Auckland" in the "Room 1" "table_row"
    And I should see "10:00 AM - 11:00 AM Europe/Prague" in the "Room 2" "table_row"
    And I should see "3:00 PM - 4:00 PM Europe/Prague" in the "Room 3" "table_row"

    When I log out
    And I log in as "admin"
    And I set the following administration settings values:
      | facetoface_displaysessiontimezones | 0 |
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    Then I should see "10:00 AM - 11:00 AM " in the "Room 1" "table_row"
    And I should see "5:00 PM - 6:00 PM " in the "Room 2" "table_row"
    And I should see "9:00 PM - 10:00 PM" in the "Room 3" "table_row"
