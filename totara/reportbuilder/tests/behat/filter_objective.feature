@totara @totara_reportbuilder @javascript
Feature: Verify the blank date filter works for reports that use it.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Bob1      | Learner1 | learner1@example.com |
    And the following "plans" exist in "totara_plan" plugin:
      | user     | name            |
      | learner1 | Learning Plan 1 |
    And the following "objectives" exist in "totara_plan" plugin:
      | user     | plan            | name        |
      | learner1 | Learning Plan 1 | Objective 1 |

    When I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | RoL Objectives                 |
      | Source      | Record of Learning: Objectives |
    And I press "Create report"
    Then I should see "Edit Report 'RoL Objectives'"

  Scenario: Verify no results are retrieved when filtering blank dates on the date created field.

    When I switch to "Filters" tab
    And I select "Date Created" from the "newstandardfilter" singleselect
    And I press "Add"
    And I wait "1" seconds
    And I follow "View This Report"
    And I set the field "show blank date records" to "1"
    And I click on "input[value=Search]" "css_element"
    Then I should see "There are no records that match your selected criteria"

  Scenario: Verify one result is retrieved when filtering blank dates on the date updated field.

    When I switch to "Filters" tab
    And I select "Date Updated" from the "newstandardfilter" singleselect
    And I press "Add"
    And I wait "1" seconds
    And I follow "View This Report"
    And I set the field "show blank date records" to "1"
    And I click on "input[value=Search]" "css_element"
    Then I should see "Objective 1"
    And I should not see "There are no records that match your selected criteria"
