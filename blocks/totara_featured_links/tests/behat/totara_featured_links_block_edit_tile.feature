@block @totara @javascript @block_totara_featured_links
Feature: Block edit test block
  In order to use the block
  The user must be able
    - to edit the content of the tiles
    - edit the config of the block

  Background:
    When I log in as "admin"
    And I am on site homepage
    And I follow "Turn editing on"
    And I add the "Featured Links" block
    And I click on "Add Tile" "link"
    And I set the following fields to these values:
     | URL | https://www.example.com |
     | textbody | default description |
    And I click on "Save changes" "button"

  Scenario: Check that the tile can be created and that it contains the initial value
    Then ".block_totara_featured_links" "css_element" should exist
    And I should see "default description"

  Scenario Outline: Editing the for actually changes the values in the tile
    When I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Edit" "link" in the "Featured Links" "block"

    # There doesnt seem to be a way of testing file uploads in a totara form
    # TODO check upload of file works once files can be uploaded in totara form

    And I set the following fields to these values:
      | Title       | <heading>  |
      | Description | <body>     |
      | URL         | <link>     |
    And I press "Save changes"
    Then I should see "<heading>"
    And I should see "<body>"
    And I should not see "default description"
    # Needs to be like this as the link could either have the heading or the body string in it
    When I click on ".block-totara-featured-links-layout > div > a" "css_element"
    Then I should not see "totara"

    Examples:
      | heading      | link                   | body      |
      |              | http://www.example.com | textbody  |
      | Some Heading | http://www.example.com | some body |
      | heading      | http://www.example.com |           |

  Scenario: Can the admin get to the edit form and cancel without effecting anything
    When I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Edit" "link" in the "Featured Links" "block"
    And I set the following fields to these values:
      | Title    | Some Heading           |
      | textbody | some body              |
      | URL      | http://www.example.com |
    And I press "Cancel"
    And I am on site homepage
    Then I should see "default description"
    And I should not see "Some Heading"
    And I should not see "some body"

  Scenario: Selecting icons
    When I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Edit" "link" in the "Featured Links" "block"
    And I set the following fields to these values:
      | Title    | Some Heading           |
      | textbody | some body              |
      | URL      | http://www.example.com |
    Then "Clear" "text" in the "//div[span[@class='icon-picker-delete-icon']]" "xpath_element" should not be visible
    When I click on "//span[@id='show-iconPicker-dialog']" "xpath_element"
    And I click on "//li[contains(@class, 'icon-picker-item')][5]" "xpath_element"
    And I click on "OK" "button"

    Then "Remove" "text" in the "//div[span[@class='icon-picker-delete-icon']]" "xpath_element" should be visible

    When I click on "Save changes" "button"
    Then ".block-totara-featured-links-icon" "css_element" should exist

  Scenario: Check that the background appearance uses the right classes
    When I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Edit" "link" in the "Featured Links" "block"
    And I set the following fields to these values:
      | Title       | title                  |
      | Description | description            |
      | URL         | http://www.example.com |
      | Fill tile   | 1                      |
    And I click on "Save changes" "button"

    Then ".background-cover" "css_element" should exist
    And ".background-contain" "css_element" should not exist

    When I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Edit" "link" in the "Featured Links" "block"
    And I set the following fields to these values:
      | Fit inside tile | 1 |
    And I click on "Save changes" "button"

    Then ".background-contain" "css_element" should exist
    And ".background-cover" "css_element" should not exist