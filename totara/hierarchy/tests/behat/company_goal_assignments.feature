@totara @totara_hierarchy @javascript
Feature: Company goal assignments
  In order to user personal goal types
  As a user
  I need to be able create personal goals with types

  Scenario: Assign company goals to users via cohort
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
      | user4    | User      | Four     | user4@example.com |
    And the following "system role assigns" exist:
      | user  | role    |
      | user4 | manager |
    And the following "goal frameworks" exist in "totara_hierarchy" plugin:
      | fullname       | idnumber |
      | Goal Framework | gframe   |
    And the following "goals" exist in "totara_hierarchy" plugin:
      | fullname | idnumber | goal_framework |
      | Goal One | goal1    | gframe         |
      | Goal Two | goal2    | gframe         |
    And the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | CH1      |
      | Cohort 2 | CH2      |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | CH1    |
      | user2 | CH1    |
      | user3 | CH2    |
      | user4 | CH1    |

    When I log in as "admin"
    And I navigate to "Manage goals" node in "Site administration > Goals"
    Then I should see "Goal Framework"

    When I click on "Goal Framework" "link" in the "#frameworkstable" "css_element"
    Then I should see "Goal One"
    And I should see "Goal Two"

    When I follow "Goal One"
    And I set the field "groupselector" to "Add audience(s)"
    And I click on "Cohort 1" "link" in the "Assign group of users" "totaradialogue"
    And I click on "Save" "button" in the "Assign group of users" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Cohort 1" in the "#assignedgroups" "css_element"
    And I log out

    When I log in as "user1"
    And I click on "Goals" in the totara menu
    Then I should see "Goal One" in the "#company_goals_table" "css_element"
    When I follow "Goal One"
    And I should not see "Cohort 1"

    When I log out
    And I log in as "user3"
    And I click on "Goals" in the totara menu
    Then I should not see "Goal One" in the "#company_goals_table" "css_element"
    When I follow "View Goal Frameworks"
    And I follow "Goal Framework"
    And I follow "Goal One"
    Then I should not see "Goal Assignments"
    And I should not see "Cohort 1"

    When I log out
    And I log in as "user4"
    And I click on "Goals" in the totara menu
    Then I should see "Goal One" in the "#company_goals_table" "css_element"
    When I follow "Goal One"
    Then I should see "Goal Assignments" in the ".list-assigned" "css_element"
    # There is no id, distinct class, or caption on this table.
    And I should see "Cohort 1" in the "#assignedgroups .generaltable .lastrow" "css_element"
    And I should see "3" in the "#assignedgroups .generaltable .lastrow .c2" "css_element"
    And I should not see "Cohort 2" in the "#assignedgroups .generaltable" "css_element"