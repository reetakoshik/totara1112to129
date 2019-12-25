@totara @block @block_last_course_accessed @javascript
Feature: Verify a learner cannot access a course they don't have access to via the LCA block.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Bob1      | Learner1 | learner1@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
    And the following "course enrolments" exist:
      | user      | course    | role     |
      | learner1  | C1        | student  |

  Scenario: Verify a learner cannot access a hidden course via the Last Course Accessed block.

    Given I log in as "learner1"

    When I click on "Dashboard" in the totara menu

    # Visit the course.
    When I follow "Course 1"
    Then I should see "C1" in the ".breadcrumb-nav" "css_element"
    And I log out

    # Hide the course.
    When I log in as "admin"
    And I navigate to "Courses and categories" node in "Site administration > Courses"
    And I click on "Hide" "link" in the "#course-listing" "css_element"
    And I log out

    # Login in as the learner and check the course can't be accessed.
    When I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    Then "Last Course Accessed" "block" should not exist

