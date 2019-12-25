@block @totara @javascript @block_totara_featured_links @core_course
Feature: Tests the behaviour of the gallery tile
  - The first save should take the user to the edit content form saving form here should
    take the user back to the page with the block.
  - Saving a tile from the edit content form should return the user to the edit content form
  - each tile should be displayed in the edit content form.

  Background:
    When I log in as "admin"
    And I follow "Dashboard"
    When I click on "Customise this page" "button"
    And I add the "Featured Links" block
    And I click on "Add Tile" "link"

  Scenario: I add the gallery tile and add a tile
    When I set the following fields to these values:
      | Tile type | Gallery |
    And I click on "Save and Edit content" "button"

    Then I should see "Edit content"
    And I should see "Finished editing"

    When I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | default description |
    And I click on "Save changes" "button"

    Then I should see "Edit content"
    And I should see "Finished editing"
    And I should see "default description"

    When I follow "Finished editing"
    And I click on "Stop customising this page" "button"

    Then I should not see "Edit content"
    And I should not see "Finished editing"
    And I should see the "Featured Links" block
    And I should see "default description"

  Scenario: Check that the sub tiles are rendered in the manage content form
    When I set the following fields to these values:
      | Tile type | Gallery |
    And I click on "Save and Edit content" "button"
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | default description |
    And I click on "Save changes" "button"
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | default description2 |
    And I click on "Save changes" "button"

    Then I should see "default description"
    And I should see "default description2"

  Scenario: Check that the second time you configure a gallery tile the save button is different
    When I set the following fields to these values:
      | Tile type | Gallery |
    And I click on "Save and Edit content" "button"
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | default description |
    And I click on "Save changes" "button"
    And I follow "Finished editing"
    And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "#actionmenuaction-1" "css_element" in the "Featured Links" "block"
    Then "Save changes" "button" should exist
    When I click on "Save changes" "button"
    Then I should see the "Featured Links" block

  Scenario: Check canceling the add tile form returns to the previous page
    When I click on "Cancel" "button"
    Then I should see the "Featured Links" block

  Scenario: Check that the control options work hide and show the controls
    When I set the following fields to these values:
      | Tile type | Gallery |
    And I click on "Save and Edit content" "button"
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | default description|
    And I click on "Save changes" "button"
    And I follow "Finished editing"
    Then ".slick-arrow" "css_element" in the "Featured Links" "block" should not be visible
    And ".slick-dots" "css_element" should exist
    When I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "#actionmenuaction-1" "css_element" in the "Featured Links" "block"
    And I set the following fields to these values:
      | Prev/Next          | 0 |
      | Position indicator | 0 |
    And I click on "Save changes" "button"
    Then ".slick-arrow" "css_element" should not exist
    And ".slick-dots" "css_element" should not exist

  Scenario: Check that interval works and repeat makes the tiles loop
    When I set the following fields to these values:
      | Tile type | Gallery |
    And I set the following fields to these values:
      | Interval | 5 |
    And I click on "Save and Edit content" "button"
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | Tile one |
    And I click on "Save changes" "button"
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | Tile two |
    And I click on "Save changes" "button"
    And I follow "Finished editing"
    Then I should see "Tile one" in the ".slick-current" "css_element"
    And I should not see "Tile two" in the ".slick-current" "css_element"
    When I wait "5" seconds
    Then I should see "Tile two" in the ".slick-current" "css_element"
    And I should not see "Tile one" in the ".slick-current" "css_element"
    When I wait "5" seconds
    Then I should see "Tile one" in the ".slick-current" "css_element"
    And I should not see "Tile two" in the ".slick-current" "css_element"

  Scenario: Check that disabling repeat stops looping
    When I set the following fields to these values:
      | Tile type | Gallery |
    And I set the following fields to these values:
      | Interval | 5 |
      | Repeat   | 0 |
    And I click on "Save and Edit content" "button"
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | Tile one |
    And I click on "Save changes" "button"
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | Tile two |
    And I click on "Save changes" "button"
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | Tile three |
    And I click on "Save changes" "button"
    And I follow "Finished editing"
    Then I should see "Tile one" in the ".slick-current" "css_element"
    And I should not see "Tile two" in the ".slick-current" "css_element"
    And I should not see "Tile three" in the ".slick-current" "css_element"
    When I wait "5" seconds
    Then I should not see "Tile one" in the ".slick-current" "css_element"
    And I should see "Tile two" in the ".slick-current" "css_element"
    And I should not see "Tile three" in the ".slick-current" "css_element"
    When I wait "5" seconds
    Then I should not see "Tile one" in the ".slick-current" "css_element"
    And I should not see "Tile two" in the ".slick-current" "css_element"
    And I should see "Tile three" in the ".slick-current" "css_element"
    When I wait "5" seconds
    Then I should not see "Tile one" in the ".slick-current" "css_element"
    And I should see "Tile two" in the ".slick-current" "css_element"
    And I should not see "Tile three" in the ".slick-current" "css_element"

  Scenario: Check that autoplay stop any switching without click on controls
    When I set the following fields to these values:
      | Tile type | Gallery |
    And I set the following fields to these values:
      | Autoplay | 0 |
    And I click on "Save and Edit content" "button"
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | Tile one |
    And I click on "Save changes" "button"
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | Tile two |
    And I click on "Save changes" "button"
    And I follow "Finished editing"
    Then I should see "Tile one" in the ".slick-current" "css_element"
    And I should not see "Tile two" in the ".slick-current" "css_element"
    When I wait "5" seconds
    Then I should see "Tile one" in the ".slick-current" "css_element"
    And I should not see "Tile two" in the ".slick-current" "css_element"

  Scenario: Check that pause on hover works
    When I set the following fields to these values:
      | Tile type | Gallery |
    And I set the following fields to these values:
      | Interval       | 3 |
      | Pause on hover | 1 |
    And I click on "Save and Edit content" "button"
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | Tile one |
    And I click on "Save changes" "button"
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | Tile two |
    And I click on "Save changes" "button"
    And I follow "Finished editing"
    And I hover ".slick-current" "css_element"
    Then I should see "Tile one" in the ".slick-current" "css_element"
    And I should not see "Tile two" in the ".slick-current" "css_element"
    When I wait "5" seconds
    Then I should see "Tile one" in the ".slick-current" "css_element"
    And I should not see "Tile two" in the ".slick-current" "css_element"

  Scenario: I add the gallery tile and add a tile
    When I set the following fields to these values:
      | Tile type | Gallery |
    And I click on "Save and Edit content" "button"

    Then I should see "Edit content"
    And I should see "Finished editing"

    When I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | default description |
    And I click on "Save changes" "button"

    Then I should see "Edit content"
    And I should see "Finished editing"
    And I should see "default description"

    When I follow "Finished editing"
    And I click on "Stop customising this page" "button"

    Then I should not see "Edit content"
    And I should not see "Finished editing"
    And I should see the "Featured Links" block
    And I should see "default description"
    And "//a[@href='https://www.example.com']" "xpath_element" should exist
    And ".block-totara-featured-links-gallery-subtiles" "css_element" should exist

  Scenario: Check that the sub tiles are rendered in the manage content form
    When I set the following fields to these values:
      | Tile type | Gallery |
    And I click on "Save and Edit content" "button"
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | default description |
    And I click on "Save changes" "button"
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL   | https://www.example.com |
      | Title | title           |
      | Description | default description2 |
    And I click on "Save changes" "button"

    Then I should see "default description"
    And I should see "title"
    And I should see "default description2"

  Scenario: Check that the second time you configure a gallery tile the save button is different
    When I set the following fields to these values:
      | Tile type | Gallery |
    And I click on "Save and Edit content" "button"
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL | https://www.example.com |
      | Description | default description |
    And I click on "Save changes" "button"
    And I follow "Finished editing"
    And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Configure" "link" in the ".block-totara-featured-links-edit" "css_element"
    Then "Save changes" "button" should exist
    When I click on "Save changes" "button"
    Then I should see the "Featured Links" block

  Scenario: Check canceling the add tile form returns to the previous page
    When I click on "Cancel" "button"
    Then I should see the "Featured Links" block

  Scenario: Check that course featured link gallery tile visibility can be set by audience
    When I click on "Cancel" "button"
    Then I should see the "Featured Links" block

    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And the following "users" exist:
      | username | firstname | lastname | email | idnumber |
      | user1    | First     | User     | first@example.com  | T1       |
      | user2    | Second    | User     | second@example.com | T2       |
    And the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | CH1      |
      | Cohort 2 | CH2      |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | CH1    |
      | user2 | CH1    |
      | user2 | CH2    |
    And I am on "Course 1" course homepage
    And I add the "Featured Links" block
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
      | Tile type | Gallery |
    And I click on "Save and Edit content" "button"
    Then I should see "Edit content"
    And I should see "Finished editing"

    When I click on "Add Tile" "link"
    And I set the following fields to these values:
      | URL         | https://www.example.com     |
      | Description | default description |
    And I click on "Save changes" "button"
    Then I should see "Finished editing"
    And I should see "default description"
    And I follow "Finished editing"

    When I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link" in the ".block-totara-featured-links-layout" "css_element"
    And I set the "Access" Totara form field to "Apply rules"
    And I set the "Define access by audience rules" Totara form field to "1"

    When I click on "Add audiences" "button"
    Then I should see "Cohort 2"
    When I click on "Cohort 2" "link"
    And I click on "OK" "button"
    And I wait "1" seconds
    Then I should see "Cohort 2"
    When I click on "Save changes" "button"
    Then I should see "Hidden" in the ".block-totara-featured-links-disabled" "css_element"
