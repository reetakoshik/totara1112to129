@availability @availability_hierarchy_position @totara
Feature: Applying availability of hierarchy position to course sectionshould allow the user to edit or remove it
  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | c101     | c101      | 0        |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname | idnumber |
      | pof1     | pf1      |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | fullname  | pos_framework | idnumber |
      | position1 | pof1          | po1      |
      | position2 | pof1          | po2      |
    And I am on a totara site
    And I log in as "admin"

  @javascript
  Scenario: Admin is able to edit the position availability within course section after added
    Given I am on "c101" course homepage with editing mode on
    And I edit the section "1"
    And I follow "Restrict access"
    And I click on "Add restriction..." "button"
    And I click on "Assigned to Position" "button"
    And I set the field "Assigned to Position" to "position1"
    And I press key "13" in the field "Assigned to Position"
    And I press "Save changes"
    And I edit the section "1"
    And I follow "Restrict access"
    And I should see "position1"
    And I set the field "Assigned to Position" to "position2"
    And I press key "13" in the field "Assigned to Position"
    And I press "Save changes"
    When I edit the section "1"
    And I follow "Restrict access"
    Then I should see "position2"

  @javascript
  Scenario: Admin is able to delete the position availaiblity within course section after added
    Given I am on "c101" course homepage with editing mode on
    And I edit the section "1"
    And I follow "Restrict access"
    And I click on "Add restriction..." "button"
    And I click on "Assigned to Position" "button"
    And I set the field "Assigned to Position" to "position1"
    And I press key "13" in the field "Assigned to Position"
    And I press "Save changes"
    And I edit the section "1"
    And I follow "Restrict access"
    When I click on "Delete" "link"
    And I press "Save changes"
    And I edit the section "1"
    And I follow "Restrict access"
    Then I should not see "position1"