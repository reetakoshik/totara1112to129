@mod @mod_facetoface @totara @javascript
Feature: Seminar Signup Manager Approval
  In order to signup to seminar
  As a learner
  I need to request approval from learner-manager

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
    And the following "courses" exist:
      | fullname    | shortname | category |
      | Course 9360 | C9360     | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C9360  | student |
    And the following job assignments exist:
      | user     | manager  |
      | student1 | student2 |

    And I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_approvaloptions[approval_none]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_self]" "checkbox"
    And I press "Save changes"

    And I click on "Find Learning" in the totara menu
    And I follow "Course 9360"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Seminar 9360     |
      | approvaloptions   | approval_manager |

  @javascript
  Scenario: Student gets approved through manager approval by "learner" role
    And I click on "Find Learning" in the totara menu
    And I follow "Course 9360"
    And I follow "Seminar 9360"
    And I follow "Add a new event"
    And I press "Save changes"
    And I log out

    And I log in as "student1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 9360"
    And I should see "Sign-up"
    And I follow "Sign-up"
    And I should see "Manager Approval"
    And I press "Request approval"
    And I should see "Your request was sent to your manager for approval"
    And I log out

    And I log in as "student2"
    And I click on "Dashboard" in the totara menu
    And I click on "View all tasks" "link"
    And I should see "Sam1 Student1" in the "td.user_namelink" "css_element"
    When I click on "Attendees" "link"
    Then I should see "Sam1 Student1"
    When I click on "requests[3]" "radio" in the ".lastrow .lastcol" "css_element"
    And I click on "Update requests" "button"
    Then I should not see "Sam1 Student1"
    And I should see "Attendance requests updated"
    And I should see "No pending approvals"




