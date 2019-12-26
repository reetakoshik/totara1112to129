@totara @totara_plan @javascript
Feature: See that audience based visibility doesn't effect a course showing in a Learning Plan.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                     |
      | learner1 | Learner   | One      | learner.one@example.com   |
      | learner2 | Learner   | Two      | learner.two@example.com   |
      | manager1 | Manager   | One      | manager.one@example.com   |
    And the following "courses" exist:
      | fullname                       | shortname   |
      | CourseVisibility Test Course 1 | testcourse1 |
    And the following job assignments exist:
      | user     | fullname       | manager  |
      | learner1 | jobassignment1 | manager1 |
    And the following "plans" exist in "totara_plan" plugin:
      | user     | name            |
      | learner1 | Learning Plan 1 |

  Scenario: Add course to plan with no visibility restrictions.
    Given I log in as "learner1"
    When I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "Learning Plan 1" "link"
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Add courses" "button"
    And I click on "Miscellaneous" "link"
    And I click on "CourseVisibility Test Course 1" "link"
    And I click on "Save" "button" in the "Add courses" "totaradialogue"
    Then I should see "CourseVisibility Test Course 1" in the "#dp-component-update-table" "css_element"

  Scenario: Audienced based visibility where learner can't see course.
    Given I log in as "admin"
    When I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    And I navigate to "Courses and categories" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I click on "CourseVisibility Test Course 1" "link"
    And I click on "Edit" "link" in the ".course-detail-listing-actions" "css_element"
    And I set the following fields to these values:
      | Visibility | Enrolled users and members of the selected audiences |
    And I press "Save and display"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Learner One" "link"
    And I click on "Learning Plans" "link" in the ".userprofile" "css_element"
    And I click on "Learning Plan 1" "link"
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Add courses" "button"
    And I click on "Miscellaneous" "link"
    And I click on "CourseVisibility Test Course 1" "link"
    And I click on "Save" "button" in the "Add courses" "totaradialogue"

    # Check that the course is visible in the plan.
    Then I should see "CourseVisibility Test Course 1" in the "#dp-component-update-table" "css_element"
