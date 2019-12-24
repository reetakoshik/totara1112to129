@totara @totara_plan
Feature: Verify capability accessanyplan.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | firstname1 | lastname1 | learner1@example.com |
      | manager2 | firstname2 | lastname2 | manager2@example.com |
      | manager3 | firstname3 | lastname3 | manager3@example.com |
    And the following "system role assigns" exist:
      | user     | role    |
      | manager2 | manager |
    # Assign the user a line manager so their plan can be sent for approval.
    And the following job assignments exist:
      | user     | fullname       | manager  |
      | learner1 | jobassignment1 | manager3 |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | Course 1  | 1                |
    And the following "competency" frameworks exist:
      | fullname               | idnumber | description           |
      | Competency Framework 1 | CF1      | Framework description |
    And the following "competency" hierarchy exists:
      | framework | fullname     | idnumber | description            |
      | CF1       | Competency 1 | C1       | Competency description |
    And the following "programs" exist in "totara_program" plugin:
      | fullname  | shortname |
      | Program 1 | P1   |
    And the following "plans" exist in "totara_plan" plugin:
      | user     | name                   |
      | learner1 | learner1 Learning Plan |
    And the following "objectives" exist in "totara_plan" plugin:
      | user     | plan                   | name        |
      | learner1 | learner1 Learning Plan | Objective 1 |

    # Log in as the learner and create some evidence.
    When I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name        | My Evidence 1                  |
    And I press "Add evidence"
    Then I should see "Evidence created"

    When I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name        | My Evidence 2                  |
    And I press "Add evidence"
    Then I should see "Evidence created"

    # Navigate to the learners plan
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "learner1 Learning Plan" "link"

    # Add some courses to the plan.
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Add courses" "button"
    And I click on "Miscellaneous" "link"
    And I click on "Course 1" "link"
    # Check the selected courses appear in the plan.
    And I click on "Save" "button" in the "Add courses" "totaradialogue"
    Then I should see "Course 1" in the "#dp-component-update-table" "css_element"

    # Add some competencies to the plan.
    When I click on "Competencies" "link" in the "#dp-plan-content" "css_element"
    And I press "Add competencies"
    And I click on "Competency 1" "link"
    # Check the selected competency appear in the plan.
    And I click on "Continue" "button" in the "Add competencies" "totaradialogue"
    Then I should see "Competency 1" in the ".dp-plan-component-items" "css_element"

    # Add some programs to the plan.
    When I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I press "Add programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program 1" "link"
    # Check the selected program appears in the plan.
    And I click on "Save" "button" in the "Add programs" "totaradialogue"
    Then I should see "Program 1" in the ".dp-plan-component-items" "css_element"
    And I log out

  @javascript
  Scenario: Check a user can access but not approve the plan with accessanyplan capability.

    # Login as the learner and navigate to the learning plan.
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    When I click on "Learning Plans" "link" in the "My Learning" "block"
    And I click on "learner1 Learning Plan" "link"
    # Send the plan to the manager for approval.
    And I press "Send approval request"
    Then I should see "Approval request sent for plan \"learner1 Learning Plan\""
    And I should see "This plan has not yet been approved (Approval Requested)"
    And I log out

    # As the manager, access the learners plans.
    When I log in as "manager2"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "firstname1 lastname1"
    And I click on "Learning Plans" "link" in the ".userprofile" "css_element"
    # Access the learners plans and verify it hasn't been approved.
    And I click on "learner1 Learning Plan" "link"
    Then I should see "You are viewing firstname1 lastname1's plan"
    And I should see "This plan has not yet been approved"
    And I should not see "Edit details"
    And I should not see "Delete plan"

  @javascript
  Scenario: Check a user can view but not amend plan courses with accessanyplan capability.

    # As the manager, access the learners plans.
    Given I log in as "manager2"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "firstname1 lastname1"
    And I click on "Learning Plans" "link" in the ".userprofile" "css_element"
    # Access the learners plan.
    When I click on "learner1 Learning Plan" "link"
    Then I should see "You are viewing firstname1 lastname1's plan"

    # Check the user can't add a course.
    When I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Course 1"
    And I should not see "Add courses"
    And I should not see "Delete"
    And I should not see "Add RPL"

    # Check the user can access course detail but not add, remove or amend anything.
    When I follow "Course 1"
    Then I should see "Course 1"
    And I should not see "Add linked competencies"
    And I should not see "Add linked evidence"
    And I should not see "Remove selected links"

  @javascript
  Scenario: Check a user can view but not amend plan competencies with accessanyplan capability.

    # As the manager, access the learners plans.
    Given I log in as "manager2"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "firstname1 lastname1"
    And I click on "Learning Plans" "link" in the ".userprofile" "css_element"
    # Access the learners plan.
    When I click on "learner1 Learning Plan" "link"
    Then I should see "You are viewing firstname1 lastname1's plan"

    # Check the user can't add a competency.
    When I click on "Competencies" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Competency 1"
    And "Add competencies" "button" should not exist
    And "Delete" "link" should not exist
    And "Set status" "link" should not exist

    # Check the user can access course detail but not add, remove or amend anything.
    When I follow "Competency 1"
    Then I should see "Competency 1"
    And "Add linked courses from plan" "button" should not exist
    And "Add linked courses from competency" "button" should not exist
    And "Add linked evidence" "button" should not exist
    And "Remove selected links" "button" should not exist

  @javascript
  Scenario: Check a user can view but not amend plan objectives with accessanyplan capability.

    # As the manager, access the learners plans.
    Given I log in as "manager2"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "firstname1 lastname1"
    And I click on "Learning Plans" "link" in the ".userprofile" "css_element"
    # Access the learners plan.
    When I click on "learner1 Learning Plan" "link"
    Then I should see "You are viewing firstname1 lastname1's plan"

    # Check the user can't add an objective.
    When I click on "Objectives" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Objective 1"
    And "Add new objective" "button" should not exist
    And "Delete" "link" should not exist

    # Check the user can access course detail but not add, remove or amend anything.
    When I follow "Objective 1"
    Then I should see "Objective 1"
    And "Edit details" "button" should not exist
    And "Add linked courses from plan" "button" should not exist
    And "Add linked evidence" "button" should not exist
    And "Remove selected links" "button" should not exist

  @javascript
  Scenario: Check a user can view but not amend plan programs with accessanyplan capability.

    # As the manager, access the learners plans.
    Given I log in as "manager2"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "firstname1 lastname1"
    And I click on "Learning Plans" "link" in the ".userprofile" "css_element"
    # Access the learners plan.
    When I click on "learner1 Learning Plan" "link"
    Then I should see "You are viewing firstname1 lastname1's plan"

    # Check the user can't add a program.
    When I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Program 1"
    And "Add programs" "button" should not exist
    And "Delete" "link" should not exist

    # Check the user can access course detail but not add, remove or amend anything.
    When I follow "Program 1"
    Then I should see "Program 1"
    And "Add linked evidence" "button" should not exist
    And "Remove selected links" "button" should not exist
