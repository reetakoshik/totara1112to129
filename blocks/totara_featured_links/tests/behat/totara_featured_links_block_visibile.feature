@block @javascript @totara @block_totara_featured_links
Feature: Check that the visibility options for the tiles work correctly
  The User should be able to hide the tile based on whether other users are in an audience or they match some
  preset rules.
  The User should be able to set aggregation options for all of these so they can make sure they can hide the tile from
  the people they want while showing it to the people who need to see it.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email | idnumber |
      | user1    | First     | User     | first@example.com  | T1       |
      | user2    | Second    | User     | second@example.com | T2       |
      | user3    | Third     | User     | third@example.com  | T3       |
      | user4    | Forth     | User     | forth@example.com  | T4       |
    And the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | CH1      |
      | Cohort 2 | CH2      |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | CH1    |
      | user2 | CH1    |
      | user2 | CH2    |
      | user3 | CH2    |
    And I log in as "admin"
    And I am on site homepage
    And I follow "Turn editing on"
    And I add the "Featured Links" block
    And I click on "Add Tile" "link"
    And I set the field "url" to "https://www.example.com"
    And I set the field "textbody" to "default description"
    And I click on "Save changes" "button"

  Scenario: Test javascript with custom visibility rules works
    When I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link"
    Then I should see "Edit Visibility"
    When I set the "Access" Totara form field to "Apply rules"
    And I click on "Expand all" "text"
    And I set the "Define access by audience rules" Totara form field to "1"
    And I set the "Define access by preset rules" Totara form field to "1"
    Then I should see "Presets"
    And I should see "Preset rule aggregation"
    And I should see "Audience"
    And I should see "Audience rule aggregation"
    And I should see "Ruleset aggregation logic"
    And I should see "Ruleset aggregation"
    When I click on "Add audiences" "button"
    Then I should see "Cohort 1"
    When I click on "Cohort 1" "link"
    And I click on "OK" "button"
    And I wait "1" seconds
    Then I should see "Cohort 1"
    When I set the "Condition required to view" Totara form field to "User is logged in"
    And I click on "Save changes" "button"
    Then "default description" "link" should exist

  Scenario: Test that setting hidden from everyone hides the tile
    When I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link"
    And I click on "Hidden from all" "text"
    And I click on "Save changes" "button"
    Then ".block-totara-featured-links-disabled" "css_element" should exist
    And I follow "Turn editing off"
    And "default description" "link" should not exist
    When I log out
    And I log in as "user1"
    And I am on site homepage
    Then "default description" "link" should not exist

  Scenario: Test that the tile can be hidden by audience
    When I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link"
    And I set the "Access" Totara form field to "Apply rules"
    And I click on "Expand all" "text"
    And I set the "Define access by audience rules" Totara form field to "1"
    And I click on "Add audiences" "button"
    And I click on "Cohort 1" "link"
    And I click on "OK" "button"
    And I click on "Save changes" "button"
    And I follow "Turn editing off"
    When I log out
    And I log in as "user1"
    And I am on site homepage
    Then "default description" "link" should exist
    When I log out
    And I log in as "user3"
    And I am on site homepage
    Then "default description" "link" should not exist

  Scenario: Test that that all aggregation on audiences hides the tile from people who aren't in all the audiences
    When I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link"
    And I set the "Access" Totara form field to "Apply rules"
    And I click on "Expand all" "text"
    And I set the "Define access by audience rules" Totara form field to "1"
    And I click on "Add audiences" "button"
    And I click on "Cohort 1" "link"
    And I click on "Cohort 2" "link"
    And I click on "OK" "button"
    And I set the "Audience rule aggregation" Totara form field to "All of the audiences above"
    And I click on "Save changes" "button"
    And I log out
      # In both the audiences.
    And I log in as "user2"
    And I am on site homepage
    Then "default description" "link" should exist
    When I log out
      # In one audienece but not both.
    And I log in as "user1"
    And I am on site homepage
    Then "default description" "link" should not exist
    When I log out
    And I log in as "user3"
    And I am on site homepage
    Then "default description" "link" should not exist
    When I log out
     # Not in any of the audiences.
    And I log in as "user4"
    And I am on site homepage
    Then "default description" "link" should not exist

  Scenario: Check that all the presets work correctly
    # User logged in
    When I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link"
    And I set the "Access" Totara form field to "Apply rules"
    And I click on "Expand all" "text"
    And I set the "Define access by preset rules" Totara form field to "1"
    # This confilcts with User is logged in as guest
    #And I set the "Condition required to view" Totara form field to "User is logged in"
    And I click on "label[for=\"tfiid_presets_checkboxes_block_totara_featured_links_tile_default_form_visibility___chb_0\"]" "css_element"
    And I click on "Save changes" "button"
    Then "default description" "link" should exist
    When I log out
    And I am on site homepage
    Then "default description" "link" should not exist
    # User not logged in
    When I log in as "admin"
    And I am on site homepage
    And I follow "Turn editing on"
    And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link"
    # This conflicts with User is not logged in as guest
    #And I set the "Condition required to view" Totara form field to "User is not logged in"
    # this gets around it
    # Unselectet the previous checkbox
    And I click on "label[for=\"tfiid_presets_checkboxes_block_totara_featured_links_tile_default_form_visibility___chb_0\"]" "css_element"
    # select the new one
    And I click on "label[for=\"tfiid_presets_checkboxes_block_totara_featured_links_tile_default_form_visibility___chb_1\"]" "css_element"
    And I click on "Save changes" "button"
    And I follow "Turn editing off"
    Then "default description" "link" should not exist
    When I log out
    And I am on site homepage
    Then "default description" "link" should exist
    When I log in as "admin"
    And I am on site homepage
    Then "default description" "link" should not exist
    # User logged in as guest
    And I follow "Turn editing on"
    And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link"
    And I set the "Condition required to view" Totara form field to "User is logged in as guest"
    And I click on "Save changes" "button"
    And I follow "Turn editing off"
    Then "default description" "link" should not exist
    When I log out
    And I am on site homepage
    Then "default description" "link" should not exist
    And I log in as "guest"
    And I am on site homepage
    Then "default description" "link" should exist
    # User is not logged in as guest
    When I log in as "admin"
    And I am on site homepage
    And I follow "Turn editing on"
    And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link"
    And I set the "Condition required to view" Totara form field to "User is not logged in as guest"
    And I click on "Save changes" "button"
    Then "default description" "link" should exist
    When I log out
    And I am on site homepage
    Then "default description" "link" should exist
    When I log in as "guest"
    And I am on site homepage
    Then "default description" "link" should not exist
    # User is site admin
    When I log in as "admin"
    And I am on site homepage
    And I follow "Turn editing on"
    And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link"
    And I set the "Condition required to view" Totara form field to "User is site administrator"
    And I click on "Save changes" "button"
    Then "default description" "link" should exist
    When I log out
    And I am on site homepage
    And "default description" "link" should not exist
    When I log in as "user1"
    And I am on site homepage
    Then "default description" "link" should not exist

  Scenario: Check the the is site administrator preset rule works
    When I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link"
    And I set the "Access" Totara form field to "Apply rules"
    And I click on "Expand all" "text"
    And I set the "Define access by preset rules" Totara form field to "1"
    And I set the "Condition required to view" Totara form field to "User is site administrator"
    And I click on "Save changes" "button"
    And I follow "Turn editing off"
    Then "default description" "link" should exist
    When I log out
    And I log in as "user1"
    And I am on site homepage
    Then "default description" "link" should not exist
    When I log out
    And I log in as "user2"
    And I am on site homepage
    Then "default description" "link" should not exist

  Scenario: Test the aggregation between the presets work correctly
    When I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link"
    And I set the "Access" Totara form field to "Apply rules"
    And I click on "Expand all" "text"
    And I set the "Define access by preset rules" Totara form field to "1"
    And I set the "Condition required to view" Totara form field to "User is site administrator,User is not logged in"
    And I set the "Preset rule aggregation" Totara form field to "All of the selected preset rules above"
    And I click on "Save changes" "button"
    And I follow "Turn editing off"
    Then "default description" "link" should not exist
    When I log out
    And I log in as "user1"
    And I am on site homepage
    Then "default description" "link" should not exist
    When I log out
    And I log in as "user2"
    And I am on site homepage
    Then "default description" "link" should not exist

  Scenario: Tests the aggregation between the audiences and the presets work correctly
    When I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link"
    And I set the "Access" Totara form field to "Apply rules"
    And I click on "Expand all" "text"
    And I set the "Define access by preset rules" Totara form field to "1"
    And I set the "Condition required to view" Totara form field to "User is site administrator"
    And I set the "Define access by audience rules" Totara form field to "1"
    And I click on "Add audiences" "button"
    And I click on "Cohort 1" "link"
    And I click on "OK" "button"
    And I set the "Ruleset aggregation" Totara form field to "Users matching all of the criteria above can view this feature link"
    And I click on "Save changes" "button"
    And I follow "Turn editing off"
    When I log out
    And I log in as "user1"
    And I am on site homepage
    Then "default description" "link" should not exist
    When I log out
    And I log in as "user2"
    And I am on site homepage
    Then "default description" "link" should not exist
    When I log out
    And I log in as "user3"
    And I am on site homepage
    Then "default description" "link" should not exist
    When I log out
    And I log in as "user4"
    And I am on site homepage
    Then "default description" "link" should not exist

  Scenario: Tests visibility options are not available on dashboard
    When I click on "Dashboard" in the totara menu
    And I click on "Customise this page" "button"
    And I add the "Featured Links" block
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | textbody | default description |
    And I click on "Save changes" "button"
    Then "Visibility" "link" should not exist