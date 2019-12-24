@totara @totara_cohort @totara_hierarchy
Feature: Test audience with organisation type menu field.

  Background:
    Given I am on a totara site
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname          | idnumber |
      | Organisation Root | OFW001   |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | org_framework | fullname        | idnumber |
      | OFW001        | IT              | ORG001   |
      | OFW001        | Fin             | ORG002   |
    And the following "organisation type" exist in "totara_hierarchy" plugin:
      | fullname | idnumber   |
      | OrgType  | ORGTYPE001 |
    And the following "menu field for hierarchy type" exist in "totara_hierarchy" plugin:
      | hierarchy    | typeidnumber | value  |
      | organisation | ORGTYPE001   | IT,Fin |
    And the following "hierarchy type assignments" exist in "totara_hierarchy" plugin:
      | hierarchy    | field | typeidnumber | idnumber | value |
      | organisation | menu  | ORGTYPE001   | ORG001   | 0     |
      | organisation | menu  | ORGTYPE001   | ORG002   | 1     |
    And the following "users" exist:
      | username  | firstname   | lastname   |
      | it10  | itf10 | itl10 |
      | it11  | itf11 | itl11 |
      | it12  | itf12 | itl12 |
      | fin20 | finf20 | finl20 |
      | fin21 | finf21 | finl21 |
      | fin22 | finf22 | finl22 |
    And the following job assignments exist:
      | user      | organisation |
      | it10      | ORG001       |
      | it11      | ORG001       |
      | it12      | ORG001       |
      | fin20     | ORG002       |
      | fin21     | ORG002       |
      | fin22     | ORG002       |
    And the following "cohorts" exist:
      | name       | idnumber | cohorttype |
      | Dyn1       | A1       | 2          |

  @javascript
  Scenario: Test organisation with custom menu field and dynamic audience with equals rule.
    Given I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Dyn1"
    And I switch to "Rule sets" tab
    Then I set the field "addrulesetmenu" to "Organisation type menu"
    When I set the field "equal" to "Equal to"
    And I set the field "listofvalues[]" to "IT"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"
    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "itf10 itl10" in the "#cohort_members" "css_element"
    And I should see "itf11 itl11" in the "#cohort_members" "css_element"
    And I should see "itf12 itl12" in the "#cohort_members" "css_element"

  @javascript
  Scenario: Test organisation with custom menu field and dynamic audience with not equals rule.
    Given I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Dyn1"
    And I switch to "Rule sets" tab
    Then I set the field "addrulesetmenu" to "Organisation type menu"
    When I set the field "equal" to "Not equal to"
    And I set the field "listofvalues[]" to "IT"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"
    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "finf20 finl20" in the "#cohort_members" "css_element"
    And I should see "finf21 finl21" in the "#cohort_members" "css_element"
    And I should see "finf22 finl22" in the "#cohort_members" "css_element"