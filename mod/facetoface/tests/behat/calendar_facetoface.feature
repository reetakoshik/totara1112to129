@mod @mod_facetoface @totara @core_calendar
Feature: Seminar calendar
  In order to verify seminar events in the calendar
  As a teacher
  I need to create and assign seminar activities

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                | city     | country | calendartype | timezone           |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com | Auckland | NZ      | gregorian    | 'Pacific/Auckland' |
      | student1 | Sam1      | Student1 | student1@example.com | Chicago  | US      | gregorian    | 'America/Chicago'  |
      | student2 | Sam2      | Student2 | student2@example.com | Madrid   | ES      | gregorian    | 'UTC'              |
      | student3 | Sam3      | Student3 | student3@example.com | Perth    | AU      | gregorian    | 'Australia/Perth'  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |

  @javascript
  Scenario: View main calendar
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                                    | Test seminar name        |
      | Description                             | Test seminar description |
      | How many times the user can sign-up?    | Unlimited                |
      | Show entry on user's calendar           | 1                        |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | +1               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | +1               |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | +1               |
      | timefinish[minute] | 00               |
    And I press "OK"
    And I press "Save changes"
    And I log out

    When I log in as "student1"
    And I click on "Dashboard" in the totara menu
    And I click on "Go to calendar" "link"
#    Make step to see the date.
#    see calendar_format_event_time function to get the expected result.
    And I should see "(time zone: Pacific/Auckland)"
    And I log out

