@totara @totara_hierarchy @totara_hierarchy_competency @totara_courseprogressbar @javascript
Feature: Verify competencies completion status is updated when the associated course completions happen before the competency creation

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | Bob1       | Learner1  | learner1@example.com |
      | manager1 | Dave1      | Manager1  | manager1@example.com |
    And the following job assignments exist:
      | user     | manager  |
      | learner1 | manager1 |
    And the following "courses" exist:
      | fullname | shortname | idnumber | enablecompletion |
      | Course 1 | C1        | 1        | 1                |
      | Course 2 | C2        | 2        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | C1     | student |
      | learner1 | C2     | student |

    # Add a page activity to the courses.
    When I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Page" to section "1" and I fill the form with:
      | Name                | Course 1 Completion Page |
      | Description         | -                      |
      | Page content        | -                      |
      | Completion tracking | 2                      |
      | Require view        | 1                      |

    And I am on "Course 2" course homepage
    And I add a "Page" to section "1" and I fill the form with:
      | Name                | Course 2 Completion Page |
      | Description         | -                      |
      | Page content        | -                      |
      | Completion tracking | 2                      |
      | Require view        | 1                      |

    # Set course completion on the courses.
    When I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I click on "criteria_activity_value[1]" "checkbox"
    And I press "Save changes"

    And I am on "Course 2" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I click on "criteria_activity_value[2]" "checkbox"
    And I press "Save changes"

    # Create the competency scale.
    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I press "Add a new competency scale"
    And I set the field "Name" to "Graded Scale 1-3"
    And I set the field "Scale values" to multiline:
"""
Scale 3
Scale 2
Scale 1
"""
    And I press "Save changes"
    Then I should see "Competency scale \"Graded Scale 1-3\" added"

    # Define the competency framework and competency using the new custom scale.
    When the following "competency" frameworks exist:
      | fullname               | idnumber | description | scale |
      | Competency Framework 1 | CF1      | Description | 2     |
    And the following "competency" hierarchy exists:
      | framework | fullname     | idnumber |
      | CF1       | Competency 1 | C1       |

    # Create a new learning plan template.
    When I navigate to "Manage templates" node in "Site administration > Learning Plans"
    And I set the field "Name" to "My Template"
    And I press "Save changes"
    Then I should see "My Template"

    When I am on "Course 1" course homepage
    And I navigate to "Competencies" node in "Course administration"
    And I press "Assign course completion to competencies"
    And I follow "Competency 1"
    And I click on "Save" "button" in the "Assign course completion to competencies" "totaradialogue"
    Then I should see "Competency Framework 1"
    And I should see "Competency 1"
    And I set the field "linktype" to "Mandatory"

    When I am on "Course 2" course homepage
    And I navigate to "Competencies" node in "Course administration"
    And I press "Assign course completion to competencies"
    And I follow "Competency 1"
    And I click on "Save" "button" in the "Assign course completion to competencies" "totaradialogue"
    Then I should see "Competency Framework 1"
    And I should see "Competency 1"
    And I set the field "linktype" to "Mandatory"

    # Create a learning plan using the new template for the learner.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Learning Plans" "link" in the ".profile_tree" "css_element"
    And I press "Create new learning plan"
    Then I should see "You are viewing Bob1 Learner1's plans."
    And I should see "Create new learning plan"

    When I set the following fields to these values:
    | Plan template | My Template         |
    | Plan name     | Bob's Learning Plan |
    And I press "Create plan"
    Then I should see "Plan creation successful"

    When I click on "Competencies" "link" in the "#dp-plan-content" "css_element"
    And I press "Add competencies"
    And I follow "Competency 1"
    And I click on "Continue" "button" in the "Add competencies" "totaradialogue"
    And I click on "Save" "button" in the "Add competencies" "totaradialogue"
    Then I should see "Competency 1" in the "#dp-component-update-table" "css_element"

    When I press "Approve"
    Then I should see "Plan \"Bob's Learning Plan\" has been approved by Admin User"
    And I log out

  Scenario: Verify that competency status is not updated if all criteria is not met
    # Check learner's competency record
    When I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Competencies" tab
    Then I should see "Competency 1" in the "Bob's Learning Plan" "table_row"
    And I should see "Scale 1" in the "Bob's Learning Plan" "table_row"
    And I log out

    # Upload course completion for 1 course with today's date
    When I log in as "admin"
    And the following courses are completed:
      | user     | course | timecompleted  |
      | learner1 | C1     | today          |
    And I run the "\totara_hierarchy\task\update_competencies_task" task
    And I log out

    # Check course completion but competency status not updated
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Courses" tab
    # Complete via rpl will be shown in the popover once it is available
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 100%     |
      | Course 2      | 0%       |
    When I switch to "Competencies" tab
    Then the following should exist in the "plan_competencies" table:
      | Plan                 | Status   |
      | Bob's Learning Plan  | Scale 1  |
    And I log out

  Scenario: Verify that competency status is updated after some course completion with completion date set to today
    # Upload course completion for course 1 with today's date
    When I log in as "admin"
    And the following courses are completed:
      | user     | course | timecompleted  |
      | learner1 | C1     | today          |
    Then I run the "\totara_hierarchy\task\update_competencies_task" task

    # Upload course completion for other courses with today's date
    When the following courses are completed:
      | user     | course | timecompleted  |
      | learner1 | C2     | today          |
    And I run the "\totara_hierarchy\task\update_competencies_task" task
    Then I log out

    # Check course completion and competency status updated
    When I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Courses" tab
    # Complete via rpl will be shown in the popover once it is available
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 100%     |
      | Course 2      | 100%     |
    When I switch to "Competencies" tab
    Then the following should exist in the "plan_competencies" table:
      | Plan                 | Status   |
      | Bob's Learning Plan  | Scale 3  |
    And I log out

  Scenario: Verify that competency status is updated after some course completion with completion date set to last month
    # Upload course completion for course 1 with today's date
    When I log in as "admin"
    And the following courses are completed:
      | user     | course | timecompleted  |
      | learner1 | C1     | today          |
    Then I run the "\totara_hierarchy\task\update_competencies_task" task

    # Upload course completion for other courses with last month's date
    When the following courses are completed:
      | user     | course | timecompleted  |
      | learner1 | C2     | last month     |
    Then I run the "\totara_hierarchy\task\update_competencies_task" task
    And I log out

    # Check course completion and competency status updated
    When I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Courses" tab
    # Complete via rpl will be shown in the popover once it is available
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 100%     |
      | Course 2      | 100%     |
    When I switch to "Competencies" tab
    Then the following should exist in the "plan_competencies" table:
      | Plan                 | Status   |
      | Bob's Learning Plan  | Scale 3  |
    And I log out

  Scenario: Verify that competency status is updated when competency criteria is changed
    # Upload course completion for course 1 with today's date
    When I log in as "admin"
    And the following courses are completed:
      | user     | course | timecompleted  |
      | learner1 | C1     | today          |
    Then I run the "\totara_hierarchy\task\update_competencies_task" task
    And I log out

    # Check course completion and competency status is not updated
    When I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Courses" tab
    # Complete via rpl will be shown in the popover once it is available
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 100%     |
      | Course 2      | 0%       |
    When I switch to "Competencies" tab
    Then the following should exist in the "plan_competencies" table:
      | Plan                 | Status   |
      | Bob's Learning Plan  | Scale 1  |
    And I log out

    When I log in as "admin"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I follow "Competency Framework 1"
    And I click on "Edit" "link" in the "Competency 1" "table_row"
    And I set the field "Aggregation method" to "Any"
    And I press "Save changes"
    Then I run the "\totara_hierarchy\task\update_competencies_task" task
    And I log out

    # Check competency status is updated
    When I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Courses" tab
    # Complete via rpl will be shown in the popover once it is available
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 100%     |
      | Course 2      | 0%       |
    When I switch to "Competencies" tab
    Then the following should exist in the "plan_competencies" table:
      | Plan                 | Status   |
      | Bob's Learning Plan  | Scale 3  |
    And I log out
