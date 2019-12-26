@mod @mod_facetoface @totara @totara_customfield
Feature: Filter session by pre-defined rooms
  In order to test seminar rooms
  As a site manager
  I need to create rooms

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity   | name              | course | idnumber |
      | facetoface | Test seminar name | C1     | S9103    |

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
      | Name              | Room 2          |
      | Building          | Building 234    |
      | Address           | 234 Tory street |
      | Room capacity     | 10              |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

    And I press "Add a new room"
    And I set the following fields to these values:
      | Name              | Room 3          |
      | Building          | Building 345    |
      | Address           | 345 Tory street |
      | Room capacity     | 10              |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

    And I press "Add a new room"
    And I set the following fields to these values:
      | Name              | Room 4          |
      | Building          | Building 456    |
      | Address           | 456 Tory street |
      | Room capacity     | 10              |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

  @javascript
  Scenario: Add sessions with different rooms and filter sessions by rooms
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"

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
    And I click on "Select room" "link"
    And I click on "Room 1, Building 123, 123 Tory street (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"

    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 2    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 2    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I click on "Select room" "link"
    And I click on "Room 2, Building 234, 234 Tory street (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"

    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 3    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 3    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I click on "Select room" "link"
    And I click on "Room 3, Building 345, 345 Tory street (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"

    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 4    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 4    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I click on "Select room" "link"
    And I click on "Room 4, Building 456, 456 Tory street (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"

    When I click on "Room 1" "option"
    Then I should see "Room 1" in the "1 January 2020" "table_row"
    And I should not see "Room 2" in the ".generaltable" "css_element"
    And I should not see "Room 3" in the ".generaltable" "css_element"
    And I should not see "Room 4" in the ".generaltable" "css_element"

    When I click on "Room 2" "option"
    Then I should see "Room 2" in the "2 January 2020" "table_row"
    And I should not see "Room 1" in the ".generaltable" "css_element"
    And I should not see "Room 3" in the ".generaltable" "css_element"
    And I should not see "Room 4" in the ".generaltable" "css_element"

    When I click on "Room 3" "option"
    Then I should see "Room 3" in the "3 January 2020" "table_row"
    And I should not see "Room 2" in the ".generaltable" "css_element"
    And I should not see "Room 1" in the ".generaltable" "css_element"
    And I should not see "Room 4" in the ".generaltable" "css_element"

    When I click on "Room 4" "option"
    Then I should see "Room 4" in the "4 January 2020" "table_row"
    And I should not see "Room 2" in the ".generaltable" "css_element"
    And I should not see "Room 3" in the ".generaltable" "css_element"
    And I should not see "Room 1" in the ".generaltable" "css_element"

    When I click on "All rooms" "option"
    Then I should see "Room 1" in the "1 January 2020" "table_row"
    And I should see "Room 2" in the "2 January 2020" "table_row"
    And I should see "Room 3" in the "3 January 2020" "table_row"
    And I should see "Room 4" in the "4 January 2020" "table_row"
