@totara @totara_cohort @core_tag @javascript
Feature: Create and edit audience pages handle tags correctly
  In order to tag audience properly
  As a user
  I need to introduce the tags while editing

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Manage tags" node in "Site administration > Appearance"
    And I click on "Default collection" "link"
    And I click on "Add standard tags" "link"
    And I set the field "Enter comma-separated list of new tags" to "Superb, Supreme, Superfluous"
    And I press "Continue"

  Scenario: Verify audience tags work as expected
    Given I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Add new audience"
    And I set the following fields to these values:
      | Name | Example Audience                              |
      | Type | Dynamic                                       |
      | Tags | Superb, Superfluous, Salacious, Sanctimonious |
    And I press "Save changes"
    And I follow "Edit details"
    And I expand all fieldsets
    Then I should see "Superb" in the "#fitem_id_tags" "css_element"
    And I should see "Superfluous" in the "#fitem_id_tags" "css_element"
    And I should see "Salacious" in the "#fitem_id_tags" "css_element"
    And I should see "Sanctimonious" in the "#fitem_id_tags" "css_element"
    And I should not see "Supreme" in the "#fitem_id_tags" "css_element"

    When I set the following fields to these values:
      | Tags | Newtag |
    And I press "Save changes"
    And I follow "Edit details"
    And I expand all fieldsets
    Then I should see "Superb" in the "#fitem_id_tags" "css_element"
    And I should see "Superfluous" in the "#fitem_id_tags" "css_element"
    And I should see "Salacious" in the "#fitem_id_tags" "css_element"
    And I should see "Sanctimonious" in the "#fitem_id_tags" "css_element"
    And I should see "Newtag" in the "#fitem_id_tags" "css_element"
    And I should not see "Supreme" in the "#fitem_id_tags" "css_element"
