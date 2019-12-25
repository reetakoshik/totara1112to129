@totara @totara_cohort @javascript
Feature: Users updating course may enrol cohorts
  In order to enrol cohort into course
  As a user with the mooodle/cohort:view permission
  I need to be able to use the ajax widget in course edit form

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username    | firstname    | lastname   | email                |
      | teacher1    | First        | Teacher    | teacher1@example.com |
      | teacher2    | Second       | Teacher    | teacher2@example.com |
      | teacher3    | Third        | Teacher    | teacher3@example.com |
      | teacher4    | Fourth       | Teacher    | teacher4@example.com |
    And the following "categories" exist:
      | name           | idnumber | category |
      | Category 01    | cat01    | 0        |
      | Category 11    | cat11    | cat01    |
      | Category 02    | cat02    | 0        |
      | Category 03    | cat03    | 0        |
    And the following "courses" exist:
      | fullname    | shortname | category | description      |
      | Course 01   | c01       | cat01   | About this course |
      | Course 11   | c11       | cat11   | About this course |
      | Course 02   | c02       | cat02   | About this course |
      | Course 03   | c03       | cat03   | About this course |
    And the following "roles" exist:
      | name            | shortname  |
      | Audience Viewer | audview    |
    And the following "permission overrides" exist:
      | capability          | permission | role    | contextlevel | reference |
      | moodle/cohort:view  | Allow      | audview | System       |           |
    And the following "cohorts" exist:
      | name              | idnumber | description         | contextlevel | reference |
      | System Audience 1 | AUD00-1  | About this audience | System       | 0         |
      | System Audience 2 | AUD00-2  | About this audience | System       | 0         |
      | Audience 01-1     | AUD01-1  | About this audience | Category     | cat01     |
      | Audience 01-2     | AUD01-2  | About this audience | Category     | cat01     |
      | Audience 11-1     | AUD11-1  | About this audience | Category     | cat11     |
      | Audience 11-2     | AUD11-2  | About this audience | Category     | cat11     |
      | Audience 02-1     | AUD02-1  | About this audience | Category     | cat02     |
      | Audience 02-2     | AUD02-2  | About this audience | Category     | cat02     |
      | Audience 03-1     | AUD03-1  | About this audience | Category     | cat03     |
      | Audience 03-2     | AUD03-2  | About this audience | Category     | cat03     |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | c01    | editingteacher |
      | teacher1 | c11    | editingteacher |
      | teacher1 | c02    | editingteacher |
      | teacher2 | c11    | editingteacher |
      | teacher3 | c11    | editingteacher |
      | teacher4 | c01    | editingteacher |
    And the following "role assigns" exist:
      | user     | role    | contextlevel | reference |
      | teacher1 | audview | Category     | cat01     |
      | teacher2 | audview | Category     | cat11     |
      | teacher3 | audview | System       |           |
    And the following "cohort enrolments" exist in "totara_cohort" plugin:
      | course | cohort  |
      | c01    | AUD00-1 |
      | c01    | AUD01-1 |
      | c01    | AUD03-1 |
      | c11    | AUD11-1 |
      | c11    | AUD01-1 |

  Scenario: Teacher with cohort view capability from category may enrol cohorts via course edit form
    Given I log in as "teacher1"
    And I am on "Course 01" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I should see "Audience 01-1"
    And I should see "Audience 03-1"
    And I should see "System Audience 1"
    And I click on "Add enrolled audiences" "button"
    And I should see "Audience 01-1" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "Audience 01-2" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "Audience 03-1" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "System Audience 1" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "Audience 02-" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "Audience 03-2" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "System Audience 2" in the "Course audiences (enrolled)" "totaradialogue"

    When I click on "Audience 01-2" "link"
    And I click on "OK" "button" in the "Course audiences (enrolled)" "totaradialogue"
    Then I should see "System Audience 1"
    And I should see "Audience 01-1"
    And I should see "Audience 03-1"
    And I should see "Audience 01-2"
    And I should not see "Audience 02-"
    And I should not see "Audience 03-2"
    And I should not see "System Audience 2"

    When I click on "Delete" "link" in the "Audience 01-1" "table_row"
    And I click on "Save and display" "button"
    And I navigate to "Edit settings" node in "Course administration"
    Then I should see "System Audience 1"
    And I should see "Audience 03-1"
    And I should see "Audience 01-2"
    And I should not see "Audience 01-1"
    And I should not see "Audience 02-"
    And I should not see "Audience 03-2"
    And I should not see "System Audience 2"

    When I click on "Add enrolled audiences" "button"
    And I click on "Search" "link" in the "Course audiences (enrolled)" "totaradialogue"
    And I search for "A" in the "Course audiences (enrolled)" totara dialogue
    Then I should see "Audience 01-1" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "Audience 01-2" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "Audience 02-" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "Audience 03-" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "System Audience" in the "Course audiences (enrolled)" "totaradialogue"

    And I click on "OK" "button" in the "Course audiences (enrolled)" "totaradialogue"
    And I click on "Save and display" "button"

  Scenario: Teacher with cohort view capability from subcategory may enrol cohorts via course edit form
    Given I log in as "teacher2"
    And I am on "Course 11" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I should see "Audience 01-1"
    And I should see "Audience 11-1"
    And I click on "Add enrolled audiences" "button"
    And I should see "Audience 01-1" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "Audience 11-1" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "Audience 11-2" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "Audience 02-" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "Audience 03-" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "System Audience" in the "Course audiences (enrolled)" "totaradialogue"

    When I click on "Audience 11-2" "link"
    And I click on "OK" "button" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "Audience 01-1"
    And I should see "Audience 11-1"
    And I should see "Audience 11-2"
    And I should not see "Audience 02-"
    And I should not see "Audience 03-"
    And I should not see "System Audience"

    When I click on "Delete" "link" in the "Audience 01-1" "table_row"
    And I click on "Delete" "link" in the "Audience 11-1" "table_row"
    And I click on "Save and display" "button"
    And I navigate to "Edit settings" node in "Course administration"
    Then I should see "Audience 11-2"
    And I should not see "Audience 01-"
    And I should not see "Audience 11-1"
    And I should not see "Audience 02-"
    And I should not see "Audience 03-"
    And I should not see "System Audience"

    When I click on "Add enrolled audiences" "button"
    And I click on "Search" "link" in the "Course audiences (enrolled)" "totaradialogue"
    And I search for "A" in the "Course audiences (enrolled)" totara dialogue
    Then I should see "Audience 11-1" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "Audience 11-2" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "Audience 01-" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "Audience 02-" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "Audience 03-" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "System Audience" in the "Course audiences (enrolled)" "totaradialogue"

    And I click on "OK" "button" in the "Course audiences (enrolled)" "totaradialogue"
    And I click on "Save and display" "button"

  Scenario: Teacher with cohort view capability from system may enrol cohorts via course edit form
    Given I log in as "teacher3"
    And I am on "Course 11" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I should see "Audience 01-1"
    And I should see "Audience 11-1"
    And I click on "Add enrolled audiences" "button"
    And I should see "Audience 01-1" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "Audience 01-2" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "Audience 11-1" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "Audience 11-2" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "System Audience 1" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "System Audience 2" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "Audience 02-" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "Audience 03-" in the "Course audiences (enrolled)" "totaradialogue"

    When I click on "Audience 11-2" "link"
    And I click on "System Audience 1" "link"
    And I click on "OK" "button" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "Audience 01-1"
    And I should see "Audience 11-1"
    And I should see "Audience 11-2"
    And I should see "System Audience 1"
    And I should not see "Audience 02-"
    And I should not see "Audience 03-"
    And I should not see "System Audience 2"

    When I click on "Delete" "link" in the "Audience 01-1" "table_row"
    And I click on "Delete" "link" in the "Audience 11-1" "table_row"
    And I click on "Save and display" "button"
    And I navigate to "Edit settings" node in "Course administration"
    Then I should see "Audience 11-2"
    And I should see "System Audience 1"
    And I should not see "Audience 01-"
    And I should not see "Audience 11-1"
    And I should not see "Audience 02-"
    And I should not see "Audience 03-"
    And I should not see "System Audience 2"

    When I click on "Add enrolled audiences" "button"
    And I click on "Search" "link" in the "Course audiences (enrolled)" "totaradialogue"
    And I search for "A" in the "Course audiences (enrolled)" totara dialogue
    Then I should see "Audience 01-1" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "Audience 01-2" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "Audience 11-1" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "Audience 11-2" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "System Audience 1" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "System Audience 2" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "Audience 02-" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "Audience 03-" in the "Course audiences (enrolled)" "totaradialogue"

    When I click on "Audience 01-2 (AUD01-2)" "link" in the "Course audiences (enrolled)" "totaradialogue"
    And I click on "System Audience 2 (AUD00-2)" "link" in the "Course audiences (enrolled)" "totaradialogue"
    And I click on "OK" "button" in the "Course audiences (enrolled)" "totaradialogue"
    And I click on "Save and display" "button"
    And I navigate to "Edit settings" node in "Course administration"
    Then I should see "Audience 01-2"
    And I should see "Audience 11-2"
    And I should see "System Audience 1"
    And I should see "System Audience 2"
    And I should not see "Audience 01-1"
    And I should not see "Audience 11-1"
    And I should not see "Audience 02-"
    And I should not see "Audience 03-"

    And I click on "Save and display" "button"

  Scenario: Teacher without cohort view capability may not enrol cohorts via course edit form
    Given I log in as "teacher4"
    And I am on "Course 01" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I should see "Audience 01-1"
    And I should see "Audience 03-1"
    And I should see "System Audience 1"
    And I click on "Add enrolled audiences" "button"
    And I should see "Audience 01-1" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "Audience 03-1" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "System Audience 1" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "Audience 01-2" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "Audience 02-" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "Audience 03-2" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "System Audience 2" in the "Course audiences (enrolled)" "totaradialogue"

    When I click on "Search" "link" in the "Course audiences (enrolled)" "totaradialogue"
    And I search for "A" in the "Course audiences (enrolled)" totara dialogue
    Then I should not see "Audience 01-" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "Audience 02-" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "Audience 03-" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "System Audience" in the "Course audiences (enrolled)" "totaradialogue"
    And I click on "OK" "button" in the "Course audiences (enrolled)" "totaradialogue"

    When I click on "Delete" "link" in the "Audience 01-1" "table_row"
    And I click on "Delete" "link" in the "System Audience 1" "table_row"
    And I click on "Save and display" "button"
    And I navigate to "Edit settings" node in "Course administration"
    Then I should see "Audience 03-1"
    And I should not see "Audience 01-"
    And I should not see "Audience 01-"
    And I should not see "Audience 02-"
    And I should not see "Audience 03-2"
    And I should not see "System Audience"

    And I click on "Save and display" "button"
