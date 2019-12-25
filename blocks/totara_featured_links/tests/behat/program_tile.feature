@block @totara @javascript @block_totara_featured_links
Feature: The program tile should work as expected
  As a user I should be able to
  - Select a program from in the content form
  - Click on the tile and be taken to the selected program
  - The name of the program to be shown in the tile
  - The visibility of the program to be displayed in the visibility form
  - deleting the program should cause the tile to become hidden

  Background:
    Given the following "programs" exist in "totara_program" plugin:
      | fullname        | shortname |
      | TestProgramName | progshort |
    And I log in as "admin"
    And I am on site homepage
    And I follow "Turn editing on"
    And I add the "Featured Links" block
    And I click on "Add Tile" "link"

  Scenario: Check program dialog displays the current programs
    Given the following "programs" exist in "totara_program" plugin:
      | fullname         | shortname  |
      | TestProgramName1 | progshort1 |
      | TestProgramName2 | progshort2 |
      | OtherTest        | progshort3 |
      | DifferentTestPro | progshort4 |
    And I am on site homepage
    And I click on "Add Tile" "link"

    When I set the field "Tile type" to "Program"
    And I click on "Select program" "button"
    And I click on "Miscellaneous" "link" in the "Select program" "totaradialogue"

    Then I should see "TestProgramName" in the "Select program" "totaradialogue"
    And I should see "TestProgramName1" in the "Select program" "totaradialogue"
    And I should see "TestProgramName2" in the "Select program" "totaradialogue"
    And I should see "OtherTest" in the "Select program" "totaradialogue"
    And I should see "DifferentTestPro" in the "Select program" "totaradialogue"

    When I click on "Search" "link" in the "Select program" "totaradialogue"
    And I set the field "id_query" to "TestProgramName"
    And I click on "Search" "button" in the "Select program" "totaradialogue"

    Then "TestProgramName" "link" in the "#search-tab" "css_element" should be visible
    And "TestProgramName1" "link" in the "#search-tab" "css_element" should be visible
    And "TestProgramName2" "link" in the "#search-tab" "css_element" should be visible
    And "OtherTest" "link" in the "#search-tab" "css_element" should not be visible
    And "DifferentTestPro" "link" in the "#search-tab" "css_element" should not be visible

  Scenario: Check Program tile has to always have a program
    When I set the field "Tile type" to "Program"
    And I click on "Select program" "button"
    And I click on "Cancel" "button" in the "Select program" "totaradialogue"
    And I click on "Save changes" "button"
    Then I should see "Please select a program"

  Scenario: Check program Tile selecting a program
    When I set the field "Tile type" to "Program"
    And I click on "Select program" "button"
    And I click on "Miscellaneous" "link" in the "Select program" "totaradialogue"
    And I click on "TestProgramName" "link" in the "Select program" "totaradialogue"
    And I click on "OK" "button" in the "Select program" "totaradialogue"
    And I click on "Save changes" "button"
    Then "TestProgramName" "text" should exist in the ".block_totara_featured_links" "css_element"

  Scenario: Check heading location is saved
    When I set the field "Tile type" to "Program"
    And I click on "Select program" "button"
    And I click on "Miscellaneous" "link" in the "Select program" "totaradialogue"
    And I click on "TestProgramName" "link" in the "Select program" "totaradialogue"
    And I click on "OK" "button" in the "Select program" "totaradialogue"

    And I set the field "Bottom" to "1"
    And I click on "Save changes" "button"

    Then ".block-totara-featured-links-content-bottom" "css_element" should exist

  Scenario: Check that the tile takes the user to the program
    When I set the field "Tile type" to "Program"
    And I click on "Select program" "button"
    And I click on "Miscellaneous" "link" in the "Select program" "totaradialogue"
    And I click on "TestProgramName" "link" in the "Select program" "totaradialogue"
    And I click on "OK" "button" in the "Select program" "totaradialogue"
    And I click on "Save changes" "button"

    And I follow "Turn editing off"
    And I click on ".block-totara-featured-links-link" "css_element"
    Then "Manage programs" "link" should exist
    And I should see "TestProgramName"
    And "Featured Links" "block" should not exist

  Scenario: Check that the visibility form shows the visibility of the program
    When I set the field "Tile type" to "Program"
    And I click on "Select program" "button"
    And I click on "Miscellaneous" "link" in the "Select program" "totaradialogue"
    And I click on "TestProgramName" "link" in the "Select program" "totaradialogue"
    And I click on "OK" "button" in the "Select program" "totaradialogue"
    And I click on "Save changes" "button"
    And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link"

    Then I should see "Program visibility"
    And I should see "Visible"
    And "Visible to all" "field" should exist

  Scenario: deleting the program shouldn't break anything
    Given I set the field "Tile type" to "Program"
    And I click on "Select program" "button"
    And I click on "Miscellaneous" "link" in the "Select program" "totaradialogue"
    And I click on "TestProgramName" "link" in the "Select program" "totaradialogue"
    And I click on "OK" "button" in the "Select program" "totaradialogue"
    And I click on "Save changes" "button"

    When I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Miscellaneous"
    And I click on "//*[@title='Delete']" "xpath_element"
    And I click on "Continue" "button"

    And I am on site homepage
    Then I should see "Program has been deleted"

    And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Edit" "link" in the "Featured Links" "block"
    Then I should see "Program has been deleted"
    When I click on "Cancel" "button"

    And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link"
    Then I should see "Program has been deleted"
    And I should see "Program visibility"
    When I click on "Cancel" "button"

    And I follow "Turn editing off"
    Then ".block-totara-featured-links-tile-medium" "css_element" should not exist
