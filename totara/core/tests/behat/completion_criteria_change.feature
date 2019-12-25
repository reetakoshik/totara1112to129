@totara @totara_core @totara_courseprogressbar
Feature: Test reaggregating completion data when changing course completion settings
  In order to test course completion settings
  I must log in as admin and configure the courses
  Then log in as a learner and complete a course completion criteria
  Then log in as admin and change the course completion settings, deleting the existing data
  Then run the scheduled task
  Then log in as the learner and check that progress has been reaggregated

  @javascript
  Scenario: course completion criteria are changed, deleting existing data
    Given I am on a totara site
    # Create users, courses and enrolments.
    And the following "users" exist:
    | username | firstname | lastname | email          |
    | user1    | user      | one      | u1@example.com |
    | user2    | user      | two      | u2@example.com |
    | user3    | user      | three    | u3@example.com |
    And the following "courses" exist:
    | fullname | shortname | summary          | format | enablecompletion |
    | Course 1 | C1        | Course summary 1 | topics | 1                |
    | Course 2 | C2        | Course summary 2 | topics | 1                |
    | Course 3 | C3        | Course summary 3 | topics | 1                |
    And the following "course enrolments" exist:
    | user  | course | role    |
    | user1 | C1     | student |
    | user2 | C1     | student |
    | user3 | C1     | student |
    | user1 | C2     | student |
    | user2 | C2     | student |
    | user3 | C2     | student |
    | user1 | C3     | student |
    | user2 | C3     | student |
    | user3 | C3     | student |
    # Create Courses 1 Assignment 1.
    Then I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I wait until the page is ready
    And I add a "Assignment" to section "1" and I fill the form with:
    | Assignment name | Assignment 1             |
    | Description     | Assignment 1 description |
    And I add the "Self completion" block
    # Set completion for Course 1 to Assignment 1 AND Manual self completion (will delete and remove self completion).
    Then I navigate to "Course completion" node in "Course administration"
    And I click on "Condition: Activity completion" "link"
    And I click on "Assignment 1" "checkbox"
    And I click on "Condition: Manual self completion" "link"
    And I click on "criteria_self_value" "checkbox"
    And I press "Save changes"
    # Create Course 2 Assignment 2.
    Then I am on "Course 2" course homepage
    And I wait until the page is ready
    And I add a "Assignment" to section "1" and I fill the form with:
    | Assignment name | Assignment 2             |
    | Description     | Assignment 2 description |
    And I add the "Self completion" block
    # Set completion for Course 2 to Assignment 2 AND Manual self completion (will delete and make no change).
    Then I navigate to "Course completion" node in "Course administration"
    And I click on "Condition: Activity completion" "link"
    And I click on "Assignment 2" "checkbox"
    And I click on "Condition: Manual self completion" "link"
    And I click on "criteria_self_value" "checkbox"
    And I press "Save changes"
    # Create Course 3 Assignment 3.
    Then I am on "Course 3" course homepage
    And I wait until the page is ready
    And I add a "Assignment" to section "1" and I fill the form with:
    | Assignment name | Assignment 3             |
    | Description     | Assignment 3 description |
    And I add the "Self completion" block
    # Set completion for Course 3 to Assignment 3 only (will not delete and add self completion).
    Then I navigate to "Course completion" node in "Course administration"
    And I click on "Condition: Activity completion" "link"
    And I click on "Assignment 3" "checkbox"
    And I press "Save changes"
    # Complete all three courses as user1.
    Then I log out
    And I log in as "user1"
    And I am on "Course 1" course homepage
    And I click on "Not completed: Assignment 1. Select to mark as complete." "link"
    And I click on "Complete course" "link"
    And I press "Yes"
    And I should see "You have already completed this course"
    And I am on "Course 2" course homepage
    And I click on "Not completed: Assignment 2. Select to mark as complete." "link"
    And I click on "Complete course" "link"
    And I press "Yes"
    And I should see "You have already completed this course"
    And I am on "Course 3" course homepage
    And I click on "Not completed: Assignment 3. Select to mark as complete." "link"
    # Confirm the status of the courses for user1.
    And I click on "Record of Learning" in the totara menu
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 100%     |
      | Course 2      | 100%     |
      | Course 3      | 100%     |
    # Complete all three assignments (but not manual self completion) as user2.
    Then I log out
    And I log in as "user2"
    And I am on "Course 1" course homepage
    And I click on "Not completed: Assignment 1. Select to mark as complete." "link"
    And I am on "Course 2" course homepage
    And I click on "Not completed: Assignment 2. Select to mark as complete." "link"
    And I am on "Course 3" course homepage
    And I click on "Not completed: Assignment 3. Select to mark as complete." "link"
    # Confirm the status of the courses for user2.
    When I click on "Record of Learning" in the totara menu
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 50%      |
      | Course 2      | 50%      |
      | Course 3      | 100%     |
    # Complete manual self completion (but not assignments) as user3.
    Then I log out
    And I log in as "user3"
    And I am on "Course 1" course homepage
    And I click on "Complete course" "link"
    And I press "Yes"
    And I should see "You have already marked yourself as complete in this course"
    And I am on "Course 2" course homepage
    And I click on "Complete course" "link"
    And I press "Yes"
    And I should see "You have already marked yourself as complete in this course"
    # Confirm the status of the courses for user3.
    When I click on "Record of Learning" in the totara menu
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 50%      |
      | Course 2      | 50%      |
      | Course 3      | 0%       |
    And "#plan_courses #plan_courses_r2 span" "css_element" should not exist
    # For course 1, unlock with delete and remove Manual self completion. Assignment completion will reaggregate.
    Then I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I press "Unlock criteria and delete existing completion data"
    And I click on "criteria_self_value" "checkbox"
    And I press "Save changes"
    # For course 2, just unlock with delete and save again. Manual self completion data will be lost.
    And I am on "Course 2" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I press "Unlock criteria and delete existing completion data"
    And I press "Save changes"
    # For course 3, unlock without delete, remove assignment and add Manual self completion. Previous completions are kept.
    And I am on "Course 3" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I press "Unlock criteria without deleting"
    And I click on "Assignment 3" "checkbox"
    And I click on "Condition: Manual self completion" "link"
    And I click on "criteria_self_value" "checkbox"
    And I press "Save changes"
    # Confirm the status of the courses for user1. Cron hasn't been run yet, so no reaggregation has occurred.
    Then I log out
    And I log in as "user1"
    When I click on "Record of Learning" in the totara menu
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 0%       |
      | Course 2      | 0%       |
      | Course 3      | 100%     |
    # Confirm the status of the courses for user2. Cron hasn't been run yet, so no reaggregation has occurred.
    Then I log out
    And I log in as "user2"
    And I click on "Record of Learning" in the totara menu
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 0%       |
      | Course 2      | 0%       |
      | Course 3      | 100%     |
    # Confirm the status of the courses for user3. Cron hasn't been run yet, so no reaggregation has occurred.
    Then I log out
    And I log in as "user3"
    And I click on "Record of Learning" in the totara menu
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 0%       |
      | Course 2      | 0%       |
      | Course 3      | 0%       |
    And "#plan_courses #plan_courses_r2 span" "css_element" should not exist
    # Run cron to cause reaggregation.
    Then I run the "\core\task\completion_regular_task" task
    # Confirm the status of the courses for user1.
    Then I log out
    And I log in as "user1"
    And I click on "Record of Learning" in the totara menu
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 100%     |
      | Course 2      | 50%      |
      | Course 3      | 100%     |
    # Confirm the status of the courses for user2.
    Then I log out
    And I log in as "user2"
    And I click on "Record of Learning" in the totara menu
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 100%     |
      | Course 2      | 50%      |
      | Course 3      | 100%     |
    # Confirm the status of the courses for user3.
    Then I log out
    And I log in as "user3"
    And I click on "Record of Learning" in the totara menu
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 0%       |
      | Course 2      | 0%       |
      | Course 3      | 0%       |
