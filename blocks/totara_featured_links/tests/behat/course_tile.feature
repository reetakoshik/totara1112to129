@block @totara @javascript @block_totara_featured_links
Feature: The course tile should work as expected
  As a user I should be able to
  - Select a course from in the content form
  - Click on the tile and be taken to the selected course
  - The name of the course to be shown in the tile
  - The visibility of the course to be displayed in the visibility form
  - deleting the course should cause the tile to become hidden

  Background:
    Given the following "courses" exist:
      | fullname        | shortname |
      | TestCourseName  | courshort |
    And I log in as "admin"
    And I am on site homepage
    And I follow "Turn editing on"
    And I add the "Featured Links" block
    And I click on "Add Tile" "link"

  Scenario: Check course dialog displays the current courses
    Given the following "courses" exist:
      | fullname          | shortname  |
      | TestCourseName1   | courshort1 |
      | TestCourseName2   | courshort2 |
      | OtherTest         | courshort3 |
      | DifferentTestCour | courshort4 |
    And I am on site homepage
    And I click on "Add Tile" "link"

    When I set the field "Tile type" to "Course"
    And I click on "Select course" "button"
    And I click on "Miscellaneous" "link" in the "Select course" "totaradialogue"

    Then I should see "TestCourseName" in the "Select course" "totaradialogue"
    And I should see "TestCourseName1" in the "Select course" "totaradialogue"
    And I should see "TestCourseName2" in the "Select course" "totaradialogue"
    And I should see "OtherTest" in the "Select course" "totaradialogue"
    And I should see "DifferentTestCour" in the "Select course" "totaradialogue"

    When I click on "Search" "link" in the "Select course" "totaradialogue"
    And I set the field "id_query" to "TestCourseName"
    And I click on "Search" "button" in the "Select course" "totaradialogue"

    Then "TestCourseName" "link" in the "#search-tab" "css_element" should be visible
    And "TestCourseName1" "link" in the "#search-tab" "css_element" should be visible
    And "TestCourseName2" "link" in the "#search-tab" "css_element" should be visible
    And "OtherTest" "link" in the "#search-tab" "css_element" should not be visible
    And "DifferentTestCour" "link" in the "#search-tab" "css_element" should not be visible

  Scenario: Check course tile has to always have a course
    When I set the field "Tile type" to "Course"
    And I click on "Select course" "button"
    And I click on "Cancel" "button" in the "Select course" "totaradialogue"
    And I click on "Save changes" "button"
    Then I should see "Please select a course"

  Scenario: Check course Tile selecting a course
    When I set the field "Tile type" to "Course"
    And I click on "Select course" "button"
    And I click on "Miscellaneous" "link" in the "Select course" "totaradialogue"
    And I click on "TestCourseName" "link" in the "Select course" "totaradialogue"
    And I click on "OK" "button" in the "Select course" "totaradialogue"
    And I click on "Save changes" "button"
    Then "TestCourseName" "text" should exist in the ".block_totara_featured_links" "css_element"

  Scenario: Check heading location is saved
    When I set the field "Tile type" to "Course"
    And I click on "Select course" "button"
    And I click on "Miscellaneous" "link" in the "Select course" "totaradialogue"
    And I click on "TestCourseName" "link" in the "Select course" "totaradialogue"
    And I click on "OK" "button" in the "Select course" "totaradialogue"

    And I set the field "Bottom" to "1"
    And I click on "Save changes" "button"

    Then ".block-totara-featured-links-content-bottom" "css_element" should exist

  Scenario: Check that the tile takes the user to the course
    When I set the field "Tile type" to "Course"
    And I click on "Select course" "button"
    And I click on "Miscellaneous" "link" in the "Select course" "totaradialogue"
    And I click on "TestCourseName" "link" in the "Select course" "totaradialogue"
    And I click on "OK" "button" in the "Select course" "totaradialogue"
    And I click on "Save changes" "button"

    And I follow "Turn editing off"
    And I click on ".block-totara-featured-links-link" "css_element"
    Then I should see "Courses"
    And I should see "courshort"
    And "Featured Links" "block" should not exist

  Scenario: Check that the visibility form shows the visibility of the course
    When I set the field "Tile type" to "Course"
    And I click on "Select course" "button"
    And I click on "Miscellaneous" "link" in the "Select course" "totaradialogue"
    And I click on "TestCourseName" "link" in the "Select course" "totaradialogue"
    And I click on "OK" "button" in the "Select course" "totaradialogue"
    And I click on "Save changes" "button"
    And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link"

    Then I should see "Course visibility"
    And I should see "Visible"
    And "Visible to all" "field" should exist

  Scenario: deleting the course shouldn't break anything
    Given I set the field "Tile type" to "Course"
    And I click on "Select course" "button"
    And I click on "Miscellaneous" "link" in the "Select course" "totaradialogue"
    And I click on "TestCourseName" "link" in the "Select course" "totaradialogue"
    And I click on "OK" "button" in the "Select course" "totaradialogue"
    And I click on "Save changes" "button"

    When I navigate to "Courses and categories" node in "Site administration > Courses"
    And I follow "Miscellaneous"
    And I click on "//*[@title='Delete']" "xpath_element"
    And I click on "Delete" "button"

    And I am on site homepage
    Then I should see "Course has been deleted"

    And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Edit" "link" in the "Featured Links" "block"
    Then I should see "Course has been deleted"
    When I click on "Cancel" "button"

    And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link"
    Then I should see "Course has been deleted"
    And I should see "Course visibility"
    When I click on "Cancel" "button"

    And I follow "Turn editing off"
    Then ".block-totara-featured-links-tile-medium" "css_element" should not exist

  Scenario: Check Course Tile can show progress
    Given the following "courses" exist:
      | fullname  | shortname  | enablecompletion |
      | Course 1  | course1    | 1                |
    And the following "course enrolments" exist:
      | user     | course   | role |
      | admin | course1  | student |
    And I click on "Courses" in the totara menu
    And I follow "Course 1"
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Enable" to "1"
    And I press "Save changes"

    When I am on site homepage
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | Tile type | Course |
    And I click on "Select course" "button"
    And I click on "Miscellaneous" "link" in the "Select course" "totaradialogue"
    And I click on "Course 1" "link" in the "Select course" "totaradialogue"
    And I click on "OK" "button" in the "Select course" "totaradialogue"
    And I set the field "Show progress" to "1"
    And I click on "Save changes" "button"

    Then I should see "Course 1" in the "Featured Links" "block"
    And ".progress" "css_element" in the "Featured Links" "block" should be visible
