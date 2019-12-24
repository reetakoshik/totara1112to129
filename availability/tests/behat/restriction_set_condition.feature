@availability @availability_restriction @javascript
Feature: Restriction set of course's restriction is appearing when user editing it
  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | c101     | c101      | 0        |
    And the following "cohorts" exist:
      | name   | idnumber |
      | toyota | toyota   |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname               | idnumber |
      | Position Framework 001 | PFW001   |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | pos_framework | fullname | idnumber |
      | PFW001        | Position1 | POS001   |
      | PFW001        | Position2 | POS002   |
    And the following "organisation" frameworks exist:
      | fullname                 | idnumber |
      | Organisation Framework 1 | OF1      |
    And the following "organisation" hierarchy exists:
      | framework | fullname       | idnumber | description              |
      | OF1       | Organisation 1 | O1       | Organisation description |
      | OF1       | Organisation 2 | O2       | Organisation description |
    And I am on a totara site
    And I log in as "admin"
  Scenario: User is adding the restriction and going to edit restriction set afterward
    Given I am on "c101" course homepage with editing mode on
    And I edit the section "1"
    And I follow "Restrict access"
    And I click on "Add restriction..." "button"
    And I click on "Restriction set" "button"
    And I click on "Add restriction..." "button"
    And I click on "Assigned to Organisation" "button"
    And I set the field "Assigned to Organisation" to "Organisation 1"
    And I press key "13" in the field "Assigned to Organisation"
    And I click on "Add restriction..." "button"
    And I click on "Assigned to Position" "button"
    And I set the field "Assigned to Position" to "Position1"
    And I press key "13" in the field "Assigned to Position"
    And I click on "Add restriction..." "button"
    And I click on "Member of Audience" "button"
    And I set the field "Member of Audience" to "toyota"
    And I press key "13" in the field "Member of Audience"
    And I click on "Save changes" "button"
    When I edit the section "1"
    And I follow "Restrict access"
    Then I should see "Organisation 1"
    And I should see "Position1"
    And I should see "toyota"
