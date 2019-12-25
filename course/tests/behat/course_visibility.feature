@totara @core_course
Feature: Test course visibility
  In order to test course visibility
  I must configure visible and hidden courses
  And check a learner can only see what they are allowed to see

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname  | shortname | visible |
      | Biology   | C1        | 1       |
      | Chemistry | C2        | 1       |
      | Physics   | C3        | 0       |
      | Calculus  | C4        | 0       |
      | Mandatory | C0        | 1       |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | trainer1 | Trainer   | 1        | trainer1@example.com |
      | learner1 | Learner   | 1        | learner1@example.com |
      | learner2 | Learner   | 2        | learner2@example.com |
      | learner3 | Learner   | 3        | learner3@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | trainer1 | C1     | editingteacher |
      | trainer1 | C2     | editingteacher |
      | trainer1 | C3     | editingteacher |
      | trainer1 | C4     | editingteacher |
      | learner1 | C1     | student        |
      | learner1 | C2     | student        |
      | learner1 | C3     | student        |
      | learner1 | C4     | student        |
      | trainer1 | C0     | editingteacher |
      | learner1 | C0     | student        |
      | learner2 | C0     | student        |
      | learner3 | C0     | student        |
    And the following "cohorts" exist:
      | name     | idnumber | contextlevel | reference |
      | Cohort 1 | AUD1     | System       | 0         |
    And the following "cohort enrolments" exist in "totara_cohort" plugin:
      | course | cohort |
      | C2     | AUD1   |
      | C4     | AUD1   |
    And the following "cohort members" exist in "totara_cohort" plugin:
      | user     | cohort |
      | learner2 | AUD1   |
    And I log in as "admin"
    And I am on site homepage
    And I navigate to "Turn editing on" node in "Front page settings"
    And I configure the "Available courses" block
    And I set the following fields to these values:
      | Display | Courses nested in categories |
    And I press "Save changes"
    And I navigate to "Turn editing off" node in "Front page settings"
    And I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "My Learning" "link"
    And I press "Blocks editing on"
    And I add the "Courses" block
    And I log out

  @javascript
  Scenario: Traditional course visibility works as expected
    Given I log in as "trainer1"
    When I click on "Dashboard" in the totara menu
    Then I should see "Biology"
    And I should see "Chemistry"
    And I should see "Physics"
    And I should see "Calculus"
    When I click on "Find Learning" in the totara menu
    Then I should see "Biology"
    And I should see "Chemistry"
    And I should see "Physics"
    And I should see "Calculus"

    When I log out
    And I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    Then I should see "Biology"
    And I should see "Chemistry"
    And I should not see "Physics"
    And I should not see "Calculus"
    When I click on "Find Learning" in the totara menu
    Then I should see "Biology"
    And I should see "Chemistry"
    And I should not see "Physics"
    And I should not see "Calculus"

    When I click on "Home" in the totara menu
    And I follow the more information icon for the "Biology" course
    And I should see "Editing Trainer: Trainer 1"

    When I log out
    And I log in as "learner2"
    And I click on "Dashboard" in the totara menu
    Then I should not see "Biology"
    And I should see "Chemistry"
    And I should not see "Physics"
    And I should not see "Calculus"
    And I click on "Find Learning" in the totara menu
    Then I should see "Biology"
    And I should see "Chemistry"
    And I should not see "Physics"
    And I should not see "Calculus"

    When I click on "Home" in the totara menu
    And I follow the more information icon for the "Biology" course
    And I should see "Editing Trainer: Trainer 1"

    When I log out
    And I log in as "learner3"
    And I click on "Dashboard" in the totara menu
    Then I should not see "Biology"
    And I should not see "Chemistry"
    And I should not see "Physics"
    And I should not see "Calculus"
    And I click on "Find Learning" in the totara menu
    Then I should see "Biology"
    And I should see "Chemistry"
    And I should not see "Physics"
    And I should not see "Calculus"

    When I click on "Home" in the totara menu
    And I follow the more information icon for the "Biology" course
    And I should see "Editing Trainer: Trainer 1"


  @javascript
  Scenario: Audience based course visibility works as expected
    Given I log in as "admin"
    And I set the following administration settings values:
      | Enable audience-based visibility | 1 |

    # Make Chemistry visible only to enrolled users.
    And I am on "Chemistry" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the following fields to these values:
      | Visibility | Enrolled users only |
    And I press "Save and display"

    # Make Physics visible to enrolled users and selected audiences.
    And I am on "Physics" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the following fields to these values:
      | Visibility | Enrolled users and members of the selected audiences |
    And I press "Save and display"

    # Make Calculus visible to no users.
    And I am on "Calculus" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the following fields to these values:
      | Visibility | No users |
    And I press "Save and display"

    # Now add these audiences as visible learning.
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Cohort 1"
    And I follow "Visible learning"
    And I press "Add courses"
    And I follow "Miscellaneous"
    And I click on "Chemistry" "link"
    And I wait "1" seconds
    And I click on "Physics" "link"
    And I press "Save"
    And I wait "1" seconds
    Then I should see "Chemistry" in the "td.associations_nameiconlink" "css_element"
    And I should not see "Physics" in the "td.associations_nameiconlink" "css_element"

    When I log out
    And I log in as "trainer1"
    And I click on "Dashboard" in the totara menu
    Then I should see "Biology"
    And I should see "Chemistry"
    And I should see "Physics"
    And I click on "Find Learning" in the totara menu
    Then I should see "Biology"
    And I should see "Chemistry"
    And I should see "Physics"
    And I should see "Calculus"

    When I click on "Home" in the totara menu
    And I follow the more information icon for the "Biology" course
    And I should see "Editing Trainer: Trainer 1"

    When I log out
    And I log in as "learner1"
    Then I should see "Biology"
    And I should see "Chemistry"
    And I should see "Physics"
    And I should not see "Calculus"
    And I click on "Find Learning" in the totara menu
    Then I should see "Biology"
    And I should see "Chemistry"
    And I should see "Physics"
    And I should not see "Calculus"

    When I click on "Home" in the totara menu
    And I follow the more information icon for the "Biology" course
    And I should see "Editing Trainer: Trainer 1"

    When I log out
    And I log in as "learner2"
    And I click on "Home" in the totara menu
    Then I should see "Biology"
    And I should see "Chemistry"
    And I should see "Physics"
    And I should not see "Calculus"
    And I click on "Find Learning" in the totara menu
    Then I should see "Biology"
    And I should see "Chemistry"
    And I should see "Physics"
    And I should not see "Calculus"

    When I click on "Home" in the totara menu

    And I follow the more information icon for the "Biology" course
    And I should see "Editing Trainer: Trainer 1"

    When I log out
    And I log in as "learner3"
    And I click on "Home" in the totara menu
    Then I should see "Biology"
    And I should not see "Chemistry"
    And I should not see "Physics"
    And I should not see "Calculus"
    And I click on "Find Learning" in the totara menu
    Then I should see "Biology"
    And I should not see "Chemistry"
    And I should not see "Physics"
    And I should not see "Calculus"

    When I click on "Home" in the totara menu
    And I follow the more information icon for the "Biology" course
    And I should see "Editing Trainer: Trainer 1"
