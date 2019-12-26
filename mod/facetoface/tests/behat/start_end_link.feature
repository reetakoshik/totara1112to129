@mod @mod_facetoface @totara @javascript
Feature: Confirm end date is adjusted when start date is altered
  In order to test that when the end date and time is adjusted when the start time changes
  As a site manager
  I need to create and edit a seminar session

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                                    | Test seminar name        |
      | Description                             | Test seminar description |
      | How many times the user can sign-up?    | Unlimited                |
      | Allow manager reservations              | Yes                      |
      | Maximum reservations                    | 10                       |
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
      | timefinish[minute] | 0    |

  Scenario Outline: Alter time by dropdown
    Given I set the following fields to these values:
      | <field> | <start_value> |
    Then I should see "<end_value>" in the "#<end_field>" "css_element"

    Examples:
      | field             | start_value | end_value | end_field            |
      | timestart[day]    | 2           | 2         | id_timefinish_day    |
      | timestart[month]  | 2           | February  | id_timefinish_month  |
      | timestart[year]   | 2021        | 2021      | id_timefinish_year   |
      | timestart[hour]   | 12          | 13        | id_timefinish_hour   |
      | timestart[minute] | 30          | 30        | id_timefinish_minute |

  Scenario: Alter seminar date by calendar
    Given I click on "Calendar" "link" in the "#fitem_id_timestart" "css_element"
    And I click on "22" "text" in the "#dateselector-calendar-panel" "css_element"
    Then I should see "22" in the "#id_timefinish_day" "css_element"

    Given I click on "Calendar" "link" in the "#fitem_id_timestart" "css_element"
    And I click on "#dateselector-calendar-panel .yui3-calendarnav-nextmonth" "css_element"
    And I click on "22" "text" in the "#dateselector-calendar-panel" "css_element"
    Then I should see "February" in the "#id_timefinish_month" "css_element"

    Given I click on "Calendar" "link" in the "#fitem_id_timestart" "css_element"
    And I click on "#dateselector-calendar-panel .yui3-calendarnav-nextmonth" "css_element"
    And I click on "#dateselector-calendar-panel .yui3-calendarnav-nextmonth" "css_element"
    And I click on "#dateselector-calendar-panel .yui3-calendarnav-nextmonth" "css_element"
    And I click on "#dateselector-calendar-panel .yui3-calendarnav-nextmonth" "css_element"
    And I click on "#dateselector-calendar-panel .yui3-calendarnav-nextmonth" "css_element"
    And I click on "#dateselector-calendar-panel .yui3-calendarnav-nextmonth" "css_element"
    And I click on "#dateselector-calendar-panel .yui3-calendarnav-nextmonth" "css_element"
    And I click on "#dateselector-calendar-panel .yui3-calendarnav-nextmonth" "css_element"
    And I click on "#dateselector-calendar-panel .yui3-calendarnav-nextmonth" "css_element"
    And I click on "#dateselector-calendar-panel .yui3-calendarnav-nextmonth" "css_element"
    And I click on "#dateselector-calendar-panel .yui3-calendarnav-nextmonth" "css_element"
    And I click on "#dateselector-calendar-panel .yui3-calendarnav-nextmonth" "css_element"
    And I click on "22" "text" in the "#dateselector-calendar-panel" "css_element"
    Then I should see "2021" in the "#id_timefinish_year" "css_element"

