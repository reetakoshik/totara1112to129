@totara @totara_cohort
Feature: Test the audience member rule in dynamic audiences
  I need to be able to select an audience as a rule in a dynamic audience

  @javascript
  Scenario: Select audience for audience member rule in dynamic audience
    # Set up the audiences which we will be selecting.
    Given I am on a totara site
    And the following "cohorts" exist:
      | name             | idnumber  | description      | contextlevel | reference |
      | Other Audience 1 | OtherAud1 | Other audience 1 | System       | 0         |
      | Other Audience 2 | OtherAud2 | Other audience 2 | System       | 0         |
      | Other Audience 3 | OtherAud3 | Other audience 3 | System       | 0         |

    # Create a dynamic audience.
    When I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I switch to "Add new audience" tab
    And I set the following fields to these values:
      | Name | test audience |
      | Type | Dynamic       |
    And I click on "Save changes" "button"

    # Try adding the audience member rule.
    When I set the field "addrulesetmenu" to "Audience member"
    And I click on "Other Audience 1" "link" in the "Add rule" "totaradialogue"
    And I click on "Other Audience 3" "link" in the "Add rule" "totaradialogue"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "User is a member of any of these audiences:" in the "Ruleset #1" "fieldset"
    And I should see "Other Audience 1" in the "Ruleset #1" "fieldset"
    And I should not see "Other Audience 2" in the "Ruleset #1" "fieldset"
    And I should see "Other Audience 3" in the "Ruleset #1" "fieldset"
