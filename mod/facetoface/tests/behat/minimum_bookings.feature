@mod @mod_facetoface @totara @javascript
Feature: Minimum Seminar bookings
  In order to test minimum bookings work as expected
  As a manager
  I need to change approval required value

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
      | student2 | Student   | Two      | student2@example.com |
      | trainer1 | Trainer   | One      | trainer1@example.com |
      | trainer2 | Trainer   | Two      | trainer2@example.com |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | teacher2 | Teacher   | Two      | teacher2@example.com |
      | creator  | Cre       | Ater     | creator@example.com  |
      | siteman  | Site      | Manager  | sm@example.com       |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
      | Course 2 | C2        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C2     | student        |
      | trainer1 | C1     | teacher        |
      | trainer2 | C2     | teacher        |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C2     | editingteacher |
    And the following "role assigns" exist:
      | user    | role          | contextlevel | reference |
      | creator | coursecreator | System       |           |
      | siteman | manager       | System       |           |
    And I log in as "admin"
    And I set the following administration settings values:
      | Default minimum bookings | 5 |

  Scenario: Confirm default minimum bookings is set correctly
    Given I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I click on "Turn editing on" "button"
    And I add a "Seminar" to section "1" and I fill the form with:
      | name | test activity |
    And I follow "View all events"
    When I follow "Add a new event"
    Then the field "Minimum bookings" matches value "5"

    When I set the field "Minimum bookings" to "2"
    And I click on "Edit session" "link" in the "Select room" "table_row"
    And I set the following fields to these values:
      | timestart[day]     | 29       |
      | timestart[month]   | December |
      | timestart[year]    | 2030     |
      | timestart[hour]    | 12       |
      | timestart[minute]  | 00       |
      | timefinish[day]    | 29       |
      | timefinish[month]  | December |
      | timefinish[year]   | 2030     |
      | timefinish[hour]   | 13       |
      | timefinish[minute] | 00       |
    And I click on "OK" "button"
    And I wait "1" seconds
    And I click on "Save changes" "button"
    And I click on "Edit" "link" in the "29 December 2030" "table_row"
    Then the field "Minimum bookings" matches value "2"

  Scenario Outline: Confirm notifications are sent out once cutoff has been reached
    Given I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "Editing Trainer" "text" in the "#admin-facetoface_session_rolesnotify" "css_element"
    And I click on "<notification to>" "checkbox" in the "#admin-facetoface_session_rolesnotify" "css_element"
    And I press "Save changes"
    Given I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I click on "Turn editing on" "button"
    And I add a "Seminar" to section "1" and I fill the form with:
      | name | test activity |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link" in the "Select room" "table_row"
    And I fill seminar session with relative date in form data:
      | timestart[day]       | +1 |
      | timestart[month]     | 0  |
      | timestart[year]      | 0  |
      | timestart[hour]      | 0  |
      | timestart[minute]    | 0  |
      | timefinish[day]      | +3 |
      | timefinish[month]    | 0  |
      | timefinish[year]     | 0  |
      | timefinish[hour]     | 0  |
      | timefinish[minute]   | 0  |
    And I click on "OK" "button"
    And I wait "1" seconds
    And I click on "Save changes" "button"
    And I should see "All events in test activity"
    And I use magic to set Seminar "test activity" to send capacity notification two days ahead
    And I click on "Attendees" "link"
    And I set the field "f2f-actions" to "Add users"
    And I click on "Student One, student1@example.com" "option"
    And I click on "Trainer One, trainer1@example.com" "option"
    And I click on "Teacher One, teacher1@example.com" "option"
    And I click on "Add" "button"
    And I click on "Continue" "button"
    And I click on "Confirm" "button"
    And I should see "Bulk add attendees success"
    And I run the scheduled task "mod_facetoface\task\send_notifications_task"

    # Confirm that the alert was sent.
    And I log out
    And I log in as "student1"
    And I click on "Dashboard" in the totara menu
    And I <student> see "Event under minimum bookings for: test activity"
    And I log out
    And I log in as "trainer1"
    And I click on "Dashboard" in the totara menu
    And I <trainer> see "Event under minimum bookings for: test activity"
    And I log out
    And I log in as "teacher1"
    And I click on "Dashboard" in the totara menu
    And I <teacher> see "Event under minimum bookings for: test activity"
    And I log out
    And I log in as "creator"
    And I click on "Dashboard" in the totara menu
    And I <creator> see "Event under minimum bookings for: test activity"
    And I log out
    And I log in as "siteman"
    And I click on "Dashboard" in the totara menu
    And I <manager> see "Event under minimum bookings for: test activity"
    And I log out

    # Confirm it wasn't set elsewhere - these are failing as it is sent to all people of the given role
    And I log in as "student2"
    And I click on "Dashboard" in the totara menu
    And I should not see "Event under minimum bookings for: test activity"
    And I log out
    And I log in as "trainer2"
    And I click on "Dashboard" in the totara menu
    And I should not see "Event under minimum bookings for: test activity"
    And I log out
    And I log in as "teacher2"
    And I click on "Dashboard" in the totara menu
    And I should not see "Event under minimum bookings for: test activity"

  Examples:
    | notification to                        | student    | trainer    | teacher    | creator    | manager    |
    | Learner                                | should     | should not | should not | should not | should not |

    # Trainer, otherwise it clicks on "Editing Trainer"
    | id_s__facetoface_session_rolesnotify_4 | should not | should     | should not | should not | should not |
    | Editing Trainer                        | should not | should not | should     | should not | should not |
    | Course creator                         | should not | should not | should not | should     | should not |
    | Site Manager                           | should not | should not | should not | should not | should     |



