@totara @totara_cohort @totara_hierarchy
Feature: Test audience with position checkbox profile field.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname   | lastname   |
      | learner10 | firstname10 | lastname10 |
      | learner11 | firstname11 | lastname11 |
      | learner20 | firstname20 | lastname20 |
      | learner21 | firstname21 | lastname21 |
      | learner30 | firstname30 | lastname30 |
      | learner31 | firstname31 | lastname31 |
    And the following "cohorts" exist:
      | name       | idnumber | cohorttype |
      | Audience 1 | A1       | 2          |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname    | idnumber |
      | Position FW | PFW001   |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | pos_framework | fullname | idnumber |
      | PFW001        | Position | POS001   |
    And the following "position type" exist in "totara_hierarchy" plugin:
      | fullname        | idnumber   |
      | Position type 1 | POSTYPE001 |
    And the following "checkbox field for hierarchy type" exist in "totara_hierarchy" plugin:
      | hierarchy | typeidnumber | value |
      | position  | POSTYPE001   | 1     |
    And the following "hierarchy type assignments" exist in "totara_hierarchy" plugin:
      | hierarchy | field    | typeidnumber | idnumber | value |
      | position  | checkbox | POSTYPE001   | POS001   | 1     |
    And the following job assignments exist:
      | user      | position |
      | learner10 | POS001   |
      | learner20 | POS001   |
      | learner30 | POS001   |

  @javascript
  Scenario: Test position with checked checkbox and audience with checked checkox.
    Given I log in as "admin"
    # Navigate to Audiences.
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab
    # Add a rule set.
    Then I set the field "addrulesetmenu" to "Position type checkbox"
    When I set the field "listofvalues" to "Checked"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"
    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "firstname10 lastname10" in the "#cohort_members" "css_element"
    And I should see "firstname20 lastname20" in the "#cohort_members" "css_element"
    And I should see "firstname30 lastname30" in the "#cohort_members" "css_element"

  @javascript
  Scenario: Test position with checked checkbox and audience with unchecked checkox.
    Given I log in as "admin"
    # Navigate to Audiences.
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab
    # Add a rule set.
    Then I set the field "addrulesetmenu" to "Position type checkbox"
    When I set the field "listofvalues" to "Unchecked"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"
    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "There are no records in this report"

  @javascript
  Scenario: Test position with unchecked checkbox and audience with checked checkbox.
    Given I log in as "admin"
    And I navigate to "Manage positions" node in "Site administration > Hierarchies > Positions"
    And I follow "Position FW"
    And I should see "Position"
    Then I follow "Edit"
    And I click on "Position type checkbox" "checkbox"
    Then I click on "Save changes" "button"
    # Navigate to Audiences.
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab
    # Add a rule set.
    Then I set the field "addrulesetmenu" to "Position type checkbox"
    When I set the field "listofvalues" to "Unchecked"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"
    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "firstname10 lastname10" in the "#cohort_members" "css_element"
    And I should see "firstname20 lastname20" in the "#cohort_members" "css_element"
    And I should see "firstname30 lastname30" in the "#cohort_members" "css_element"

  @javascript
  Scenario: Test position with unchecked checkbox and auidence with unchecked checkbox.
    Given I log in as "admin"
    And I navigate to "Manage positions" node in "Site administration > Hierarchies > Positions"
    And I follow "Position FW"
    And I should see "Position"
    Then I follow "Edit"
    And I click on "Position type checkbox" "checkbox"
    Then I click on "Save changes" "button"
    # Navigate to Audiences.
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab
    # Add a rule set.
    Then I set the field "addrulesetmenu" to "Position type checkbox"
    When I set the field "listofvalues" to "Checked"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"
    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "There are no records in this report"
