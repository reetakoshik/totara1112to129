@mod @mod_facetoface @totara @totara_reportbuilder @javascript
Feature: Check the seminar events and sessions reports display correctly

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Terry1    | Teacher1 | teacher1@moodle.com |
      | student1 | Sam1      | Student1 | student1@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    When I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
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
    And I press "Save changes"
    Then I should see "1 January 2020"

  Scenario: Seminar events report should only display one row per event with the sessions report showing one row per session
    #
    # Events with a single session display correctly.
    #
    When I navigate to "Events report" node in "Site administration > Seminars"
    Then I should see "Test seminar name" in the "Course 1" "table_row"
    When I follow "Sessions view"
    Then I should see "1 January 2020" in the "Course 1" "table_row"

    #
    # Events with multiple sessions display correctly.
    # Lets add another session.
    # There should only be one row per event in the events report.
    #
    When I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on "Edit event" "link"
    And I click on "Add a new session" "button"
    And I click to edit the seminar event date at position 2
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
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    Then I should see "1 January 2020"
    And I should see "2 January 2020"

    # Check reports.
    When I navigate to "Events report" node in "Site administration > Seminars"
    Then I should see "Test seminar name" in the "Course 1" "table_row"
    And "//table[@id='facetoface_events']/tbody/tr[2]" "xpath_element" should not exist
    When I follow "Sessions view"
    Then the following should exist in the "facetoface_summary" table:
      | Seminar Name      | Course Name | Session Start Date/Time |
      | Test seminar name | Course 1    | 1 January 2020          |
      | Test seminar name | Course 1    | 2 January 2020          |

  Scenario: Check the Seminar events report displays the event start and finish dates, times and timezones correctly

    # Add event start and finish cols to the events report.
    When I navigate to "Events report" node in "Site administration > Seminars"
    Then I should see "Test seminar name" in the "Course 1" "table_row"
    When I click on "Edit this report" "button"
    And I follow "Columns"
    And I set the field "newcolumns" to "Event Start Date/Time"
    And I press "Add"
    And I set the field "newcolumns" to "Event Finish Date/Time"
    And I press "Add"
    Then I press "Save changes"

    # Set admin users timezone to Europe/London.
    When I follow "Profile" in the user menu
    And I follow "Edit profile"
    And I set the field "timezone" to "Europe/London"
    And I press "Update profile"
    Then I should see "Europe/London"

    # Check the events start and finish date display correctly.
    When I navigate to "Events report" node in "Site administration > Seminars"
    Then the following should exist in the "facetoface_events" table:
      | Seminar Name      | Course Name | Event Start Date/Time                  | Event Finish Date/Time                |
      | Test seminar name | Course 1    | 1 January 2020, 3:00 AM Europe/London  | 1 January 2020, 4:00 AM Europe/London |

    # Set admin users timezone to Pacific/Auckland.
    When I follow "Profile" in the user menu
    And I follow "Edit profile"
    And I set the field "timezone" to "Pacific/Auckland"
    And I press "Update profile"
    Then I should see "Pacific/Auckland"

    # Check the events start and finish date display correctly.
    When I navigate to "Events report" node in "Site administration > Seminars"
    Then the following should exist in the "facetoface_events" table:
      | Seminar Name      | Course Name | Event Start Date/Time                     | Event Finish Date/Time                   |
      | Test seminar name | Course 1    | 1 January 2020, 4:00 PM Pacific/Auckland  | 1 January 2020, 5:00 PM Pacific/Auckland |

    # Set the sessions display timezone to America/Toronto.
    When I follow "Course 1"
    And I follow "View all events"
    And I click on "Edit event" "link"
    And I click on "Edit session" "link"
    And I set the field "sessiontimezone" to "America/Toronto"
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    Then I should see "10:00 PM - 11:00 PM America/Toronto"

    # Check the events start and finish date display correctly.
    When I navigate to "Events report" node in "Site administration > Seminars"
    Then the following should exist in the "facetoface_events" table:
      | Seminar Name      | Course Name | Event Start Date/Time                         | Event Finish Date/Time                     |
      | Test seminar name | Course 1    | 31 December 2019, 10:00 PM America/Toronto    | 31 December 2019, 11:00 PM America/Toronto |
