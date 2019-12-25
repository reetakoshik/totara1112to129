@javascript @mod @mod_facetoface @totara @takeattendance
Feature: Check attendees actions are performed by users with the right permissions
  In order to check users with the right permission could perform action on the attendees page
  As Admin
  I need to set users with different capabilities and perform actions as the users

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                |
      | trainer1  | Trainer   | One      | trainer1@example.com |
      | student1  | Sam1      | Student1 | student1@example.com |
      | student2  | Sam2      | Student2 | student2@example.com |
      | student3  | Sam3      | Student3 | student3@example.com |
      | manager1  | Manager   | One      | student4@example.com |
    And the following job assignments exist:
      | user     | fullname           | idnumber | manager   |
      | student1 | Job Assignment One | 1        | manager1  |
      | student2 | Job Assignment One | 1        | manager1  |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion | completionstartonenrol |
      | Course 1 | C1        | 0        | 1                | 1                      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | trainer1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable restricted access | 1 |
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
      | Completion tracking           | Show activity as complete when conditions are met |
      | completionstatusrequired[100] | 1                                                 |
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Seminar - Test seminar name | 1 |
    And I press "Save changes"
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | -1               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | 0                |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | 0                |
      | timefinish[minute] | -30              |
    And I press "OK"
    And I press "Save changes"
    And I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I click on "Sam3 Student3, student3@example.com" "option"
    And I press "Add"
    # We must wait here, because the refresh may not happen before the save button is clicked otherwise.
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Sam1 Student1"
    And I should see "Sam2 Student2"
    And I should see "Sam3 Student3"
    And I log out

  Scenario: Check trainer actions on attendees page
    Given I log in as "trainer1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I click on "View all events" "link"
    When I click on "Attendees" "link"
    Then I should see "Attendees" in the "div.tabtree" "css_element"
    And I should see "Wait-list" in the "div.tabtree" "css_element"
    And I should see "Cancellations" in the "div.tabtree" "css_element"
    And I should see "Take attendance" in the "div.tabtree" "css_element"
    And I should see "Message users" in the "div.tabtree" "css_element"
    And I log out

  Scenario: Check trainer actions on attendees page after removing take attendance capability
    Given the following "permission overrides" exist:
      | capability                       | permission | role           | contextlevel | reference |
      | mod/facetoface:takeattendance    | Prohibit   | editingteacher | Course       |        C1 |
    When I log in as "trainer1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I click on "View all events" "link"
    And I click on "Attendees" "link"
    Then I should see "Attendees" in the "div.tabtree" "css_element"
    And I should see "Wait-list" in the "div.tabtree" "css_element"
    And I should see "Cancellations" in the "div.tabtree" "css_element"
    And I should not see "Take attendance" in the "div.tabtree" "css_element"
    And I should not see "Message users" in the "div.tabtree" "css_element"
    When I visit the attendees page for session "1" with action "takeattendance"
    Then I should not see "Sam1 Student1"
    And I should not see "Sam2 Student2"
    And I should not see "Sam3 Student3"
    And I should not see "Mark all selected as:"
    And "Save attendance" "button" should not exist

  Scenario: Check trainer actions on attendees page after removing view cancellations capability
    Given the following "permission overrides" exist:
      | capability                       | permission | role           | contextlevel | reference |
      | mod/facetoface:viewcancellations | Prohibit   | editingteacher | Course       |        C1 |
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I click on "View all events" "link"
    And I click on "Attendees" "link"
    And I click on "Remove users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Remove"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    And I log out
    When I log in as "trainer1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I click on "View all events" "link"
    And I click on "Attendees" "link"
    Then I should see "Attendees" in the "div.tabtree" "css_element"
    And I should see "Wait-list" in the "div.tabtree" "css_element"
    And I should see "Take attendance" in the "div.tabtree" "css_element"
    And I should see "Message users" in the "div.tabtree" "css_element"
    And I should not see "Cancellations" in the "div.tabtree" "css_element"
    When I visit the attendees page for session "1" with action "cancellations"
    Then I should not see "Sam1 Student1"
    And I should not see "Cancellations" in the "div.f2f-attendees-table" "css_element"

  Scenario: Check trainer actions on attendees page after removing view attendees capability
    Given the following "permission overrides" exist:
      | capability                    | permission | role           | contextlevel | reference |
      | mod/facetoface:viewattendees  | Prohibit   | editingteacher | Course       |        C1 |
    When I log in as "trainer1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I click on "View all events" "link"
    Then "Attendees" "link" should not exist
    When I visit the attendees page for session "1" with action "takeattendance"
    And I should see "Cancellations" in the "div.tabtree" "css_element"
    And I should see "Take attendance" in the "div.tabtree" "css_element"
    And I should see "Message users" in the "div.tabtree" "css_element"
    And I should not see "Attendees" in the "div.tabtree" "css_element"
    And I should not see "Wait-list" in the "div.tabtree" "css_element"
#    I cannot visit attendees page with action=attendees because an exception is thrown a Behat doesn't like it

  Scenario: Check managers can view attendees page
    Given I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar2 name        |
      | Description | Test seminar2 description |
      | Completion tracking           | Show activity as complete when conditions are met |
      | completionstatusrequired[100] | 1                                                 |
      | Manager Approval              | 1                                                 |
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Seminar - Test seminar2 name | 1 |
    And I press "Save changes"
    And I follow "Test seminar2 name"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | +8               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | +8               |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | 0                |
      | timefinish[minute] | +30              |
    And I press "OK"
    And I press "Save changes"
    And I log out

    When I log in as "student1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "Sign-up"
    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."
    And I log out

    When I log in as "manager1"
    And I click on "Dashboard" in the totara menu
    And I click on "View all tasks" "link"
    And I should see "Sam1 Student1" in the "td.user_namelink" "css_element"
    And I click on "Attendees" "link"
    Then I should see "Sam1 Student1"
    And I should not see "Cancellations" in the "div.f2f-attendees-table" "css_element"
    And I should not see "Take attendance" in the "div.tabtree" "css_element"
    And I should not see "Message users" in the "div.tabtree" "css_element"
    And I should not see "Attendees" in the "div.tabtree" "css_element"
    And I should not see "Wait-list" in the "div.tabtree" "css_element"
    And I set the following fields to these values:
      | Approve Sam1 Student1 for this event | 1 |
    And I press "Update requests"
    And I log out

    When I log in as "student1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    Then I should see "Cancel booking"
