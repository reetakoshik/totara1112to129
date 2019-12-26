@block @totara @javascript @block_totara_featured_links
Feature: The certification tile should work as expected
  As a user I should be able to
  - Select a certification from in the content form
  - Click on the tile and be taken to the selected certification
  - The name of the certification to be shown in the tile
  - The visibility of the certification to be displayed in the visibility form
  - deleting the certification should cause the tile to become hidden

  Background:
    Given the following "certifications" exist in "totara_program" plugin:
      | fullname              | shortname |
      | TestCertificationName | certshort |
    And I log in as "admin"
    And I am on site homepage
    And I follow "Turn editing on"
    And I add the "Featured Links" block
    And I click on "Add Tile" "link"

  Scenario: Check certification dialog displays the current certifications
    Given the following "certifications" exist in "totara_program" plugin:
      | fullname               | shortname  |
      | TestCertificationName1 | certshort1 |
      | TestCertificationName2 | certshort2 |
      | OtherTest              | certshort3 |
      | DifferentTest          | certshort4 |
    And I am on site homepage
    And I click on "Add Tile" "link"

    When I set the field "Tile type" to "Certification"
    And I click on "Select certification" "button"
    And I click on "Miscellaneous" "link" in the "Select certification" "totaradialogue"

    Then I should see "TestCertificationName" in the "Select certification" "totaradialogue"
    And I should see "TestCertificationName1" in the "Select certification" "totaradialogue"
    And I should see "TestCertificationName2" in the "Select certification" "totaradialogue"
    And I should see "OtherTest" in the "Select certification" "totaradialogue"
    And I should see "DifferentTest" in the "Select certification" "totaradialogue"

    When I click on "Search" "link" in the "Select certification" "totaradialogue"
    And I set the field "id_query" to "TestCertificationName"
    And I click on "Search" "button" in the "Select certification" "totaradialogue"

    Then "TestCertificationName" "link" in the "#search-tab" "css_element" should be visible
    And "TestCertificationName1" "link" in the "#search-tab" "css_element" should be visible
    And "TestCertificationName2" "link" in the "#search-tab" "css_element" should be visible
    And "OtherTest" "link" in the "#search-tab" "css_element" should not be visible
    And "DifferentTest" "link" in the "#search-tab" "css_element" should not be visible

  Scenario: Check Certification tile has to always have a certification
    When I set the field "Tile type" to "Certification"
    And I click on "Select certification" "button"
    And I click on "Cancel" "button" in the "Select certification" "totaradialogue"
    And I click on "Save changes" "button"
    Then I should see "Please select a certification"

  Scenario: Check Certification Tile selecting a certification
    When I set the field "Tile type" to "Certification"
    And I click on "Select certification" "button"
    And I click on "Miscellaneous" "link" in the "Select certification" "totaradialogue"
    And I click on "TestCertificationName" "link" in the "Select certification" "totaradialogue"
    And I click on "OK" "button" in the "Select certification" "totaradialogue"
    And I click on "Save changes" "button"
    Then "TestCertificationName" "text" should exist in the ".block_totara_featured_links" "css_element"

  Scenario: Check heading location is saved
    When I set the field "Tile type" to "Certification"
    And I click on "Select certification" "button"
    And I click on "Miscellaneous" "link" in the "Select certification" "totaradialogue"
    And I click on "TestCertificationName" "link" in the "Select certification" "totaradialogue"
    And I click on "OK" "button" in the "Select certification" "totaradialogue"

    And I set the field "Bottom" to "1"
    And I click on "Save changes" "button"

    Then ".block-totara-featured-links-content-bottom" "css_element" should exist

  Scenario: Check that the tile takes the user to the certification
    When I set the field "Tile type" to "Certification"
    And I click on "Select certification" "button"
    And I click on "Miscellaneous" "link" in the "Select certification" "totaradialogue"
    And I click on "TestCertificationName" "link" in the "Select certification" "totaradialogue"
    And I click on "OK" "button" in the "Select certification" "totaradialogue"
    And I click on "Save changes" "button"

    And I follow "Turn editing off"
    And I click on ".block-totara-featured-links-link" "css_element"
    Then "Manage programs" "link" should exist
    And I should see "TestCertificationName"
    And "Featured Links" "block" should not exist

  Scenario: Check that the visibility form shows the visibility of the certification
    When I set the field "Tile type" to "Certification"
    And I click on "Select certification" "button"
    And I click on "Miscellaneous" "link" in the "Select certification" "totaradialogue"
    And I click on "TestCertificationName" "link" in the "Select certification" "totaradialogue"
    And I click on "OK" "button" in the "Select certification" "totaradialogue"
    And I click on "Save changes" "button"
    And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link"

    Then I should see "Certification visibility"
    And I should see "Visible"
    And "Visible to all" "field" should exist

  Scenario: deleting the certification shouldn't break anything
    Given I set the field "Tile type" to "Certification"
    And I click on "Select certification" "button"
    And I click on "Miscellaneous" "link" in the "Select certification" "totaradialogue"
    And I click on "TestCertificationName" "link" in the "Select certification" "totaradialogue"
    And I click on "OK" "button" in the "Select certification" "totaradialogue"
    And I click on "Save changes" "button"

    When I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Delete" "link" in the "TestCertificationName" "table_row"
    And I click on "Continue" "button"

    And I am on site homepage
    Then I should see "Certification has been deleted"

    And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Edit" "link" in the "Featured Links" "block"
    Then I should see "Certification has been deleted"
    When I click on "Cancel" "button"

    And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link"
    Then I should see "Certification has been deleted"
    And I should see "Certification visibility"
    When I click on "Cancel" "button"

    And I follow "Turn editing off"
    Then ".block-totara-featured-links-tile-medium" "css_element" should not exist
