@totara @totara_core @core_tag @javascript
Feature: Create and edit activity pages handle tags correctly
  In order to tag an activity properly
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
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |


  Scenario: Verify activity tags work as expected
    Given I am on "Course 1" course homepage with editing mode on
    And I wait until the page is ready
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Assignment Example                            |
      | Description     | Assignment Description                        |
      | Tags            | Superb, Superfluous, Salacious, Sanctimonious |
    And I follow "Assignment Example"
    And I navigate to "Edit settings" node in "Assignment administration"
    And I expand all fieldsets
    Then I should see "Superb" in the "#fitem_id_tags" "css_element"
    And I should see "Superfluous" in the "#fitem_id_tags" "css_element"
    And I should see "Salacious" in the "#fitem_id_tags" "css_element"
    And I should see "Sanctimonious" in the "#fitem_id_tags" "css_element"
    And I should not see "Supreme" in the "#fitem_id_tags" "css_element"

    When I set the following fields to these values:
      | Tags | Newtag |
    And I press "Save and display"
    And I navigate to "Edit settings" node in "Assignment administration"
    And I expand all fieldsets
    Then I should see "Superb" in the "#fitem_id_tags" "css_element"
    And I should see "Superfluous" in the "#fitem_id_tags" "css_element"
    And I should see "Salacious" in the "#fitem_id_tags" "css_element"
    And I should see "Sanctimonious" in the "#fitem_id_tags" "css_element"
    And I should see "Newtag" in the "#fitem_id_tags" "css_element"
    And I should not see "Supreme" in the "#fitem_id_tags" "css_element"

