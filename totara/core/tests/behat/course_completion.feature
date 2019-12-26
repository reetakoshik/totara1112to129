@totara @course @completion @totara_courseprogressbar @javascript
Feature: Users completion of courses
  In order to view a course
  As a user
  I need to login if forcelogin enabled

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user001  | fn_001    | ln_001   | user001@example.com |
      | user002  | fn_002    | ln_002   | user002@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 1 | C1        | topics | 1                |
      | Course 2 | C2        | topics | 1                |
    And the following "course enrolments" exist:
      | user    | course | role    |
      | user001 | C1     | student |
      | user001 | C2     | student |
      | user002 | C1     | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Choice" to section "1" and I fill the form with:
      | Choice name          | Activity One                                      |
      | Description          | The first activity                                |
      | option[0]            | Option 1                                          |
      | option[1]            | Option 2                                          |
      | option[2]            | Option 3                                          |
      | Completion tracking  | Show activity as complete when conditions are met |
      | id_completionsubmit  | 1                                                 |
    And I add a "Choice" to section "2" and I fill the form with:
      | Choice name          | Activity Two                                      |
      | Description          | The other activity                                |
      | option[0]            | Option 1                                          |
      | option[1]            | Option 2                                          |
      | option[2]            | Option 3                                          |
      | Completion tracking  | Show activity as complete when conditions are met |
      | id_completionsubmit  | 1                                                 |
    And I navigate to "Course completion" node in "Course administration"
    And I click on "Condition: Activity completion" "link"
    And I click on "Choice - Activity One" "checkbox"
    And I click on "Choice - Activity Two" "checkbox"
    And I press "Save changes"
    And I am on "Course 2" course homepage
    And I add a "Choice" to section "1" and I fill the form with:
      | Choice name          | Activity Three                                    |
      | Description          | The final activity                                |
      | option[0]            | Option 1                                          |
      | option[1]            | Option 2                                          |
      | option[2]            | Option 3                                          |
      | Completion tracking  | Show activity as complete when conditions are met |
      | id_completionsubmit  | 1                                                 |
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I click on "Choice - Activity Three" "checkbox"
    And I click on "Miscellaneous / Course 1" "option" in the "#id_criteria_course_value" "css_element"
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Test course deletion only removes records relating to that course
    When I log in as "user001"
    And I am on "Course 1" course homepage
    And I click on "Activity One" "link"
    And I click on "Option 1" "radio"
    And I press "Save my choice"
    And I am on "Course 1" course homepage
    And I click on "Activity Two" "link"
    And I click on "Option 2" "radio"
    And I press "Save my choice"
    And I am on "Course 2" course homepage
    And I click on "Activity Three" "link"
    And I click on "Option 3" "radio"
    And I press "Save my choice"
    And I click on "Record of Learning" in the totara menu
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Completions archive" node in "Course administration"
    And I press "Continue"
    And I press "Continue"
    And I am on "Course 2" course homepage
    And I navigate to "Completions archive" node in "Course administration"
    And I press "Continue"
    And I press "Continue"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Historic Completions Report"
    And I set the field "Source" to "Course Completion Including History"
    And I press "Create report"
    And I click on "View This Report" "link"
    Then I should see "No" in the "Course 1" "table_row"
    And I should see "No" in the "Course 2" "table_row"
    When I click on "Home" in the totara menu
    And I navigate to "Courses and categories" node in "Site administration > Courses"
    And I should see "Course 1" in the "#course-listing" "css_element"
    And I click on "delete" action for "Course 1" in management course listing
    And I press "Delete"
    And I press "Continue"
    And I click on "Reports" in the totara menu
    And I click on "Historic Completions Report" "link"
    Then I should not see "Course 1"
    And I should see "Course 2"
    And I should see "No" in the "Course 2" "table_row"

  @javascript
  Scenario: Test instant and re-aggregation of course completion using activity completion
    When I log in as "user001"
    And I am on "Course 1" course homepage
    And I click on "Activity One" "link"
    And I click on "Option 1" "radio"
    And I press "Save my choice"
    And I click on "Record of Learning" in the totara menu
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 50%      |
      | Course 2      | 0%       |

    When I click on "Course 1" "link"
    And I click on "Activity Two" "link"
    And I click on "Option 2" "radio"
    And I press "Save my choice"
    And I click on "Record of Learning" in the totara menu
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 100%     |
      | Course 2      | 50%      |

    When I click on "Course 2" "link"
    And I click on "Activity Three" "link"
    And I click on "Option 3" "radio"
    And I press "Save my choice"
    And I click on "Record of Learning" in the totara menu
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 100%     |
      | Course 2      | 100%     |

    When I log out
    And I log in as "user002"
    And I am on "Course 1" course homepage
    And I click on "Activity One" "link"
    And I click on "Option 1" "radio"
    And I press "Save my choice"
    And I click on "Record of Learning" in the totara menu
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 50%      |

    # Thats the instant functionality done, now unlock and reaggregate to test cron functionality.
    When I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I press "Unlock criteria and delete existing completion data"
    And I click on "Choice - Activity Two" "checkbox"
    And I press "Save changes"
    And I run the "\core\task\completion_regular_task" task

    When I log out
    And I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 100%     |
      | Course 2      | 100%     |

    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 100%     |

  @javascript
  Scenario: Test completion of another course criteria when admin completes for learner
    When the following "course enrolments" exist:
      | user    | course | role    |
      | user002 | C2     | student |
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 0%       |
      | Course 2      | 0%       |
    And I log out
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I complete the course via rpl for "fn_002 ln_002" with text "Test 1"
    And I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 100%     |
      | Course 2      | 50%      |
