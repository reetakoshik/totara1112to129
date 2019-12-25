@block @javascript @totara @block_totara_featured_links
Feature: Test the add tile button in the Featured Links block
  To be able to use this block the user must be able to add and remove tiles
  when adding tile and removing them they should be in the correct order
  the user should be able to change that order

  Background:
    When I log in as "admin"
    And I follow "Dashboard"
    And I click on "Customise this page" "button"
    And I add the "Featured Links" block

  Scenario: Test the a tile can be added then deleted
    When I click on "Add Tile" "link"
    And I click on "Cancel" "button"
    And I click on "Stop customising this page" "button"
    # There will not be any text in this it just makes sure that no tile exist
    Then ".block-totara-featured-links-layout > div > a" "css_element" should not exist
    When I click on "Customise this page" "button"
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | default description |
    And I click on "Save changes" "button"
    Then I should see "default description"
    When I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Delete" "link" in the ".block-totara-featured-links-layout" "css_element"
    And I click on "Delete" "button" in the ".confirmation-buttons" "css_element"
    Then I should not see "default description"

  Scenario: Add multiple tile and check that the order is maintained when deleting
    When I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | default description1 |
    And I click on "Save changes" "button"
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | default description2 |
    And I click on "Save changes" "button"
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | default description3 |
    And I click on "Save changes" "button"
    And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Edit" "link" in the "Featured Links" "block"
    And I set the following fields to these values:
      | Description | not default |
      | Title | test heading |
    And I set the field "sortorder" to "2"
    And I press "Save changes"
    And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Delete" "link" in the ".block-totara-featured-links-layout" "css_element"
    And I wait "1" seconds
    And I click on "Delete" "button"
    Then I should see "not default"
    And I should not see "default description2"