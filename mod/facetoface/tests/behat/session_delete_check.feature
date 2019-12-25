@mod @mod_facetoface @totara @javascript
Feature: Confirm overlapping sessions can be removed
  In order to remove additional dates
  As a user
  I need to be able to remove overlapping times

  Scenario Outline:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And I log in as "admin"
    Given I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]       | 15               |
      | timestart[month]     | 7                |
      | timestart[year]      | 2020             |
      | timestart[hour]      | 15               |
      | timestart[minute]    | 0                |
      | timestart[timezone]  | Pacific/Auckland |
      | timefinish[day]      | 15               |
      | timefinish[month]    | 7                |
      | timefinish[year]     | 2020             |
      | timefinish[hour]     | 16               |
      | timefinish[minute]   | 0                |
      | timefinish[timezone] | Pacific/Auckland |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Add a new session"
    And I click on "Edit session" "link" in the ".f2fmanagedates .lastrow" "css_element"
    And I set the following fields to these values:
      | timestart[day]       | 15             |
      | timestart[month]     | 7              |
      | timestart[year]      | 2020           |
      | timestart[hour]      | <starthour>    |
      | timestart[minute]    | <startminute>  |
      | timestart[timezone]  | <timezone>     |
      | timefinish[day]      | 15             |
      | timefinish[month]    | 7              |
      | timefinish[year]     | 2020           |
      | timefinish[hour]     | <finishhour>   |
      | timefinish[minute]   | <finishminute> |
      | timefinish[timezone] | <timezone>     |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Delete" "link" in the ".f2fmanagedates .lastrow" "css_element"
    And I press "Save changes"
    Then I should not see "This date conflicts with an earlier date in this event"
    And I should see "Upcoming events"

    Examples:
      | starthour | startminute | finishhour | finishminute | timezone         |
      | 12        | 00          | 13         | 00           | Pacific/Auckland |
      | 15        | 00          | 16         | 00           | Pacific/Auckland |
      | 15        | 30          | 16         | 30           | Pacific/Auckland |
      | 14        | 30          | 15         | 30           | Pacific/Auckland |
      | 14        | 30          | 16         | 30           | Pacific/Auckland |
      | 15        | 05          | 15         | 55           | Pacific/Auckland |
      | 03        | 00          | 04         | 00           | UTC              |
