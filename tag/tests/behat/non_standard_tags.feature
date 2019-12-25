@core @core_tag @javascript
Feature: Users can create non-standard tags
  In order to use tags
  As a user
  I need to be able to edit tags

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | 1        | user1@example.com |
    And I log in as "user1"
    And I follow "Profile" in the user menu
    And I click on "Edit profile" "link"
    And I expand all fieldsets

  Scenario: Create non-standard tag
    Given I set the field "List of interests" to "test"
    Then I should see "test" in the ".form-autocomplete-selection" "css_element"

    Given I set the field "List of interests" to "<b>not bold</b>"
    Then I should see "<b>not bold</b>" in the ".form-autocomplete-selection" "css_element"