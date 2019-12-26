@availability @availability_restriction @javascript
Feature: Restriction set of course's restriction is appearing when user editing it
  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | c101     | c101      | 0        |
    And the following "cohorts" exist:
      | name  | idnumber |
      | Hunga | hunga    |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname               | idnumber |
      | Position Framework 001 | PFW001   |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | pos_framework | fullname | idnumber |
      | PFW001        | Tunga1   | POS001   |
      | PFW001        | Tunga2   | POS002   |
    And the following "organisation" frameworks exist:
      | fullname                 | idnumber |
      | Organisation Framework 1 | OF1      |
    And the following "organisation" hierarchy exists:
      | framework | fullname | idnumber | description              |
      | OF1       | Ropu 1   | O1       | Organisation description |
      | OF1       | Ropu 2   | O2       | Organisation description |
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
    And I set the field "Assigned to Organisation" to "Ropu 1"
    And I press key "13" in the field "Assigned to Organisation"

    And I click on "Add restriction..." "button"
    And I click on "Assigned to Position" "button"
    And I set the field "Assigned to Position" to "Tunga1"
    And I press key "13" in the field "Assigned to Position"

    And I click on "Add restriction..." "button"
    And I click on "Member of Audience" "button"
    And I set the field "Member of Audience" to "Hunga"
    And I press key "13" in the field "Member of Audience"

    And I click on "Save changes" "button"
    When I edit the section "1"
    And I follow "Restrict access"
    Then I should see "Ropu 1"
    And I should see "Tunga1"
    And I should see "Hunga"

  @mod @mod_survey
  Scenario: User is adding the restriction to survey and going to edit restriction set afterward
    Given I am on "c101" course homepage with editing mode on
    And I follow "Add an activity or resource"
    When I click on "Survey" "radio" in the "Add an activity or resource" "dialogue"
    And I click on "Add" "button" in the "Add an activity or resource" "dialogue"
    And I set the field "Name" to "Pukapuka Uiui"
    And I set the field "Survey type" to "COLLES (Actual)"
    And I follow "Restrict access"

    And I click on "Add restriction..." "button"
    And I click on "Restriction set" "button"

    And I click on "Add restriction..." "button"
    And I click on "Assigned to Organisation" "button"
    And I set the field "Assigned to Organisation" to "Ropu 1"
    And I press key "13" in the field "Assigned to Organisation"

    And I click on "Add restriction..." "button"
    And I click on "Assigned to Position" "button"
    And I set the field "Assigned to Position" to "Tunga1"
    And I press key "13" in the field "Assigned to Position"

    And I click on "Add restriction..." "button"
    And I click on "Member of Audience" "button"
    And I set the field "Member of Audience" to "Hunga"
    And I press key "13" in the field "Member of Audience"

    And I click on "Add restriction..." "button"
    And I click on "Grade" "button"

    When I click on "Save and display" "button"
    Then I should see "You must select a grade item for the grade condition."
    And I should see "Ropu 1"
    And I should see "Tunga1"
    And I should see "Hunga"

    And I click on "Delete" "link" in the ".availability-item:last-child" "css_element"
    When I click on "Save and display" "button"
    Then I should not see "Adding a new Survey"
    And I should see "Pukapuka Uiui"

    When I navigate to "Edit settings" node in "Survey administration"
    And I follow "Restrict access"
    Then I should see "Ropu 1"
    And I should see "Tunga1"
    And I should see "Hunga"

  @mod @mod_facetoface
  Scenario: User is adding the restriction to seminar and going to edit restriction set afterward
    Given I am on "c101" course homepage with editing mode on
    And I follow "Add an activity or resource"
    When I click on "Seminar" "radio" in the "Add an activity or resource" "dialogue"
    And I click on "Add" "button" in the "Add an activity or resource" "dialogue"
    And I set the field "Name" to "Wananga"
    And I follow "Restrict access"

    And I click on "Add restriction..." "button"
    And I click on "Restriction set" "button"

    And I click on "Add restriction..." "button"
    And I click on "Restriction set" "button"

    And I click on "Add restriction..." "button"
    And I click on "Restriction set" "button"

    And I click on "Add restriction..." "button" in the ".availability-children .availability-children .availability-children .availability-button" "css_element"
    And I click on "Date" "button"

    And I click on "Add restriction..." "button" in the ".availability-field > .availability-list > .availability-inner > .availability-children > .availability-list > .availability-inner > .availability-children > .availability-list > .availability-inner > .availability-button" "css_element"
    And I click on "Date" "button"

    And I click on "Add restriction..." "button" in the ".availability-field > .availability-list > .availability-inner > .availability-children > .availability-list > .availability-inner > .availability-button" "css_element"
    And I click on "Date" "button"

    And I click on "Add restriction..." "button" in the ".availability-field > .availability-list > .availability-inner > .availability-button" "css_element"
    And I click on "Date" "button"

    When I click on "Save and display" "button"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I follow "Restrict access"
    Then I should see "Add restriction..." in the ".availability-children .availability-children .availability-children .availability-button" "css_element"
    And I should see "Date" in the ".availability-children .availability-children .availability-children .availability-children .availability_date" "css_element"
    And I should see "Date" in the ".availability-field > .availability-list > .availability-inner > .availability-children > .availability-list > .availability-inner > .availability-children > .availability-list > .availability-inner > .availability-children > .availability-item > .availability_date" "css_element"
    And I should see "Date" in the ".availability-field > .availability-list > .availability-inner > .availability-children > .availability-list > .availability-inner > .availability-children > .availability-item > .availability_date" "css_element"
    And I should see "Date" in the ".availability-field > .availability-list > .availability-inner > .availability-children > .availability-item > .availability_date" "css_element"
