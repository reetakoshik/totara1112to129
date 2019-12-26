@totara @totara_hierarchy @totara_hierarchy_competency @javascript
Feature: Verify completion of a course triggers assigning a competency.

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

    # Add a page activity to course 2.
    When I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Page" to section "1" and I fill the form with:
      | Name                | Course Completion Page |
      | Description         | -         |
      | Page content        | -         |
      | Completion tracking | 2         |
      | Require view        | 1         |

    # Set course completion on course 2.
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I click on "criteria_activity_value[1]" "checkbox"
    And I press "Save changes"

    # Create the competency scale.
    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I press "Add a new competency scale"
    And I set the field "Name" to "Graded Scale 1-5"
    And I set the field "Scale values" to multiline:
"""
5
4
3
2
1
"""
    And I press "Save changes"
    Then I should see "Competency scale \"Graded Scale 1-5\" added"

    # Make scale values 4 a proficient value.
    When I click on "Edit" "link" in the "4" "table_row"
    And I set the field "Proficient value" to "1"
    And I press "Save changes"
    Then I should see "Competency scale value \"4\" has been updated"

    # Make scale values 3 a proficient value.
    When I click on "Edit" "link" in the "3" "table_row"
    And I set the field "Proficient value" to "1"
    And I press "Save changes"
    Then I should see "Competency scale value \"3\" has been updated"

    # Define the competency framework and competency using the new custom sca;e.
    And the following "competency" frameworks exist:
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
    And I log out

  Scenario: Verify that a minimum competency proficiency is allocated on course completion and the manager is able to award a higher proficiency.

    # Create a learning plan using the new template for the learner.
    Given I log in as "admin"
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

    When I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I press "Add courses"
    And I follow "Miscellaneous"
    And I follow "Course 1"
    And I follow "Course 2"
    And I click on "Save" "button" in the "Add courses" "totaradialogue"
    Then I should see "Course 1" in the "#dp-component-update-table" "css_element"
    Then I should see "Course 2" in the "#dp-component-update-table" "css_element"

    When I click on "Competencies" "link" in the "#dp-plan-content" "css_element"
    And I press "Add competencies"
    And I follow "Competency 1"

    When I click on "Continue" "button" in the "Add competencies" "totaradialogue"
    And I click on "Save" "button" in the "Add competencies" "totaradialogue"
    Then I should see "Competency 1" in the "#dp-component-update-table" "css_element"

    When I press "Approve"
    Then I should see "Plan \"Bob's Learning Plan\" has been approved by Admin User"
    And I log out

    # Complete the course as the learner.
    When I log in as "learner1"
    And I follow "Course 1"
    And I follow "Course Completion Page"
    Then I log out

    When I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Learning Plans" "link" in the ".profile_tree" "css_element"
    And I follow "Bob's Learning Plan"
    And I click on "Competencies" "link" in the "#dp-plan-content" "css_element"
    # Not competent. Needs to be set by a cron run.
    Then the field "compprof_competency[1]" matches value "1"

    When I navigate to "Scheduled tasks" node in "Site administration > Server"
    And I press "Set all enabled tasks to run on next cron"
    And I trigger cron
    And I am on homepage
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Learning Plans" "link" in the ".profile_tree" "css_element"
    And I follow "Bob's Learning Plan"
    And I click on "Competencies" "link" in the "#dp-plan-content" "css_element"
    # Minimum competency set by the course.
    Then the field "compprof_competency[1]" matches value "3"

    When I set the field "compprof_competency[1]" to "5"
    And I navigate to "Scheduled tasks" node in "Site administration > Server"
    And I press "Set all enabled tasks to run on next cron"
    And I trigger cron
    And I am on homepage
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Learning Plans" "link" in the ".profile_tree" "css_element"
    And I follow "Bob's Learning Plan"
    And I click on "Competencies" "link" in the "#dp-plan-content" "css_element"

    # This should match the higher competency value set by admin,
    Then the field "compprof_competency[1]" matches value "5"

    And I log out
