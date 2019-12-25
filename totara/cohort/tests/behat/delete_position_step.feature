@totara_cohort @totara @javascript
Feature: Delete a position audience requirement
  I need to be able to delete individual positions

  @_alert
  Scenario: Delete individual position setting
    Given I am on a totara site
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | idnumber | fullname    |
      | fw1      | framework 1 |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | idnumber | fullname | pos_framework |
      | 1        | Pos 1    | fw1           |
      | 2        | Pos 2    | fw1           |
      | 3        | Pos 3    | fw1           |
      | 4        | Pos 4    | fw1           |
    And I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I switch to "Add new audience" tab
    And I set the following fields to these values:
      | Name | test audience |
      | Type | Dynamic       |
    And I click on "Save changes" "button"

    When I set the field "addrulesetmenu" to "Positions"
    And I click on "Pos 1" "link" in the "Add rule" "totaradialogue"
    And I click on "Pos 2" "link" in the "Add rule" "totaradialogue"
    And I click on "Pos 3" "link" in the "Add rule" "totaradialogue"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Pos 1" in the "Ruleset #1" "fieldset"
    And I should see "Pos 2" in the "Ruleset #1" "fieldset"
    And I should see "Pos 3" in the "Ruleset #1" "fieldset"
    And I should not see "Pos 4" in the "Ruleset #1" "fieldset"

    # Now do a delete.
    When I click on "Delete rule item" "link" confirming the dialogue
    Then I should not see "Pos 1" in the "Ruleset #1" "fieldset"
    And I should see "Pos 2" in the "Ruleset #1" "fieldset"
    And I should see "Pos 3" in the "Ruleset #1" "fieldset"
    And I should not see "Pos 4" in the "Ruleset #1" "fieldset"

    When I reload the page
    Then I should not see "Pos 1" in the "Ruleset #1" "fieldset"
    And I should see "Pos 2" in the "Ruleset #1" "fieldset"
    And I should see "Pos 3" in the "Ruleset #1" "fieldset"
    And I should not see "Pos 4" in the "Ruleset #1" "fieldset"

    When I click on "Delete rule item" "link" confirming the dialogue
    Then I should not see "Pos 1" in the "Ruleset #1" "fieldset"
    And I should not see "Pos 2" in the "Ruleset #1" "fieldset"
    And I should see "Pos 3" in the "Ruleset #1" "fieldset"
    And I should not see "Pos 4" in the "Ruleset #1" "fieldset"
