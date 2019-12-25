@availability @availability_hierarchy_organisation @totara
Feature: Apply availability of hierarchy organisation to course section should allow the user to edit or remove it

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | c101     | c101      | 0        |
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname     | idnumber |
      | Organisation | org      |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | fullname | org_framework | idnumber |
      | org1     | org           | org1     |
      | org2     | org           | org2     |
    And I am on a totara site
    And I log in as "admin"

  @javascript
  Scenario: Admin is able to edit the organisation availability within course section after added
    Given I am on "c101" course homepage with editing mode on
    And I edit the section "1"
    And I follow "Restrict access"
    And I click on "Add restriction..." "button"
    And I click on "Assigned to Organisation" "button"
    And I set the field "Assigned to Organisation" to "org1"
    And I press key "13" in the field "Assigned to Organisation"
    And I press "Save changes"
    When I edit the section "1"
    And I follow "Restrict access"
    Then I should see "org1"
    And I set the field "Assigned to Organisation" to "org2"
    And I press key "13" in the field "Assigned to Organisation"
    And I press "Save changes"
    When I edit the section "1"
    And I follow "Restrict access"
    Then I should see "org2"

  @javascript
  Scenario:  Admin is able to delete the organisation availability within course section after added
    Given I am on "c101" course homepage with editing mode on
    And I edit the section "1"
    And I follow "Restrict access"
    And I click on "Add restriction..." "button"
    And I click on "Assigned to Organisation" "button"
    And I set the field "Assigned to Organisation" to "org1"
    And I press key "13" in the field "Assigned to Organisation"
    And I press "Save changes"
    When I edit the section "1"
    And I follow "Restrict access"
    And I click on "Delete" "link"
    And I press "Save changes"
    And I edit the section "1"
    And I follow "Restrict access"
    Then I should not see "org1"