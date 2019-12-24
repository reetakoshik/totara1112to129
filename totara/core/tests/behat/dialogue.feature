@totara @totara_core
Feature: Test Totara dialogue operation
  In order to test the Totara dialogues
  As an admin
  I trigger the display of a dialogue and then close it


  @javascript
  Scenario: I can open and close a Totara dialogue using its title
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | summary | format |
      | Course 1 | C1 | <p>Course summary</p> | topics |
    And the following "cohorts" exist:
      | name | idnumber |
      | Cohort 1 | CH1 |
      | Cohort 2 | CH2 |
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I click on "Edit settings" "link" in the "Administration" "block"
    When I press "Add enrolled audiences"
    Then I should see "Cohort 1" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "Cohort 2" in the "Course audiences (enrolled)" "totaradialogue"
    And I click on "Cancel" "button" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "Cohort 1" in the "Course audiences (enrolled)" "totaradialogue"

  @javascript
  Scenario: I can open and close a Totara dialogue using its id
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | summary | format |
      | Course 1 | C1 | <p>Course summary</p> | topics |
    And the following "cohorts" exist:
      | name | idnumber |
      | Cohort 1 | CH1 |
      | Cohort 2 | CH2 |
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I click on "Edit settings" "link" in the "Administration" "block"
    When I press "Add enrolled audiences"
    Then I should see "Cohort 1" in the "course-cohorts-enrolled-dialog" "totaradialogue"
    And I should see "Cohort 2" in the "course-cohorts-enrolled-dialog" "totaradialogue"
    And I click on "Cancel" "button" in the "course-cohorts-enrolled-dialog" "totaradialogue"
    And I should not see "Cohort 1" in the "course-cohorts-enrolled-dialog" "totaradialogue"

  @javascript
  Scenario: I can close a Totara dialogue using the close icon
    Given the following "courses" exist:
      | fullname | shortname | summary | format |
      | Course 1 | C1 | <p>Course summary</p> | topics |
    And the following "cohorts" exist:
      | name | idnumber |
      | Cohort 1 | CH1 |
      | Cohort 2 | CH2 |
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I click on "Edit settings" "link" in the "Administration" "block"
    When I press "Add enrolled audiences"
    Then I should see "Cohort 1" in the "Course audiences (enrolled)" "totaradialogue"
    And I should see "Cohort 2" in the "Course audiences (enrolled)" "totaradialogue"
    And I click on "close" "button" in the "Course audiences (enrolled)" "totaradialogue"
    And I should not see "Cohort 1" in the "Course audiences (enrolled)" "totaradialogue"
