@core_enrol @core_group @javascript @totara
Feature: View an enrolled user list with suspended users in the course enrolment
  In order to manage enrolled users
  As a teacher
  In order to manage enrolled users effectively I should not see suspended users in the list of enrolment candidates

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | John      | Smith    | teacher1@example.com |
      | student1 | James     | First    | student1@example.com |
      | student2 | James     | Second   | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  Scenario: Verify that "moodle/course:viewsuspendedusers" capability is necessary to see suspended users in the list of enrolment candidates
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I wait "2" seconds
    When I click on "Enrol users" "button"
    Then I should see "James First" in the ".user-enroller-panel" "css_element"
    And I should see "James Second" in the ".user-enroller-panel" "css_element"
    And I click on "Finish enrolling users" "button"
    And I log out
    # Suspend user.
    When I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Suspend James Second" "link" in the "James Second" "table_row"
    And I log out
    # Check if the teacher can see the suspended users.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    When I click on "Enrol users" "button"
    Then I should see "James First" in the ".user-enroller-panel" "css_element"
    And I should see "James Second" in the ".user-enroller-panel" "css_element"
    And I click on "Finish enrolling users" "button"
    And I log out
    # Disable 'moodle/course:viewsuspendedusers' capability for the teacher role.
    When I log in as "admin"
    And I navigate to "Define roles" node in "Site administration > Permissions"
    And I follow "Editing Trainer"
    And I click on "Edit" "button"
    And I set the field "Filter" to "view sus"
    And I click on "moodle/course:viewsuspendedusers" "checkbox"
    And I press "Save changes"
    And I log out
    # Check if the teacher still can see the suspended users.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    When I click on "Enrol users" "button"
    Then I should see "James First" in the ".user-enroller-panel" "css_element"
    And I should not see "James Second" in the ".user-enroller-panel" "css_element"
