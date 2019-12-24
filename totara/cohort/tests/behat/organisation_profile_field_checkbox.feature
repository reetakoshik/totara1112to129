@totara @totara_cohort @totara_hierarchy
Feature: Test audience with organisation checkbox profile field.

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
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname        | idnumber |
      | Organisation FW | OFW001   |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | org_framework | fullname        | idnumber |
      | OFW001        | Organisation    | ORG001   |
    And the following "organisation type" exist in "totara_hierarchy" plugin:
      | fullname            | idnumber   |
      | Organisation type 1 | ORGTYPE001 |
    And the following "checkbox field for hierarchy type" exist in "totara_hierarchy" plugin:
      | hierarchy    | typeidnumber | value |
      | organisation | ORGTYPE001   | 1     |
    And the following "hierarchy type assignments" exist in "totara_hierarchy" plugin:
      | hierarchy    | field    | typeidnumber | idnumber | value |
      | organisation | checkbox | ORGTYPE001   | ORG001   | 1     |
    And the following job assignments exist:
      | user      | organisation |
      | learner10 | ORG001       |
      | learner20 | ORG001       |
      | learner30 | ORG001       |

  @javascript
  Scenario: Test organisation with checked checkbox and audience with checked checkox.
    Given I log in as "admin"
    # Navigate to Audiences.
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab
    # Add a rule set.
    Then I set the field "addrulesetmenu" to "Organisation type checkbox"
    When I set the field "listofvalues" to "Checked"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"
    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "firstname10 lastname10" in the "#cohort_members" "css_element"
    And I should see "firstname20 lastname20" in the "#cohort_members" "css_element"
    And I should see "firstname30 lastname30" in the "#cohort_members" "css_element"

  @javascript
  Scenario: Test organisation with checked checkbox and audience with unchecked checkox.
    Given I log in as "admin"
    # Navigate to Audiences.
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab
    # Add a rule set.
    Then I set the field "addrulesetmenu" to "Organisation type checkbox"
    When I set the field "listofvalues" to "Unchecked"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"
    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "There are no records in this report"

  @javascript
  Scenario: Test organisation with unchecked checkbox and audience with checked checkbox.
    Given I log in as "admin"
    And I navigate to "Manage organisations" node in "Site administration > Hierarchies > Organisations"
    And I follow "Organisation FW"
    And I should see "Organisation"
    Then I follow "Edit"
    And I click on "Organisation type checkbox" "checkbox"
    Then I click on "Save changes" "button"
    # Navigate to Audiences.
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab
    # Add a rule set.
    Then I set the field "addrulesetmenu" to "Organisation type checkbox"
    When I set the field "listofvalues" to "Unchecked"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"
    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "firstname10 lastname10" in the "#cohort_members" "css_element"
    And I should see "firstname20 lastname20" in the "#cohort_members" "css_element"
    And I should see "firstname30 lastname30" in the "#cohort_members" "css_element"

  @javascript
  Scenario: Test organisation with unchecked checkbox and auidence with unchecked checkbox.
    Given I log in as "admin"
    And I navigate to "Manage organisations" node in "Site administration > Hierarchies > Organisations"
    And I follow "Organisation FW"
    And I should see "Organisation"
    Then I follow "Edit"
    And I click on "Organisation type checkbox" "checkbox"
    Then I click on "Save changes" "button"
    # Navigate to Audiences.
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab
    # Add a rule set.
    Then I set the field "addrulesetmenu" to "Organisation type checkbox"
    When I set the field "listofvalues" to "Checked"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"
    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "There are no records in this report"
