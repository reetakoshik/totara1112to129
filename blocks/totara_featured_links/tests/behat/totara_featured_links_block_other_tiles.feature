@block @totara @javascript @block_totara_featured_links
  Feature: Tests adding the functionality of the other tiles packed with the block
    There are other tiles that come with the featured links block other than the static tile
      - Test the content form works
      - Test that it is displayed as expected
      - Test that the correct values are passed accross

  Background:
    When I log in as "admin"
    And I follow "Dashboard"
    And I click on "Customise this page" "button"
    And I add the "Featured Links" block
    And I click on "Add Tile" "link"

    Scenario: Gallery tile content
      When I start watching to see if a new page loads
      And I set the following fields to these values:
        | Tile type | Gallery |
      And I set the following Totara form fields to these values:
        | URL         | www.example.com       |
        | Title       | this is a title       |
        | Description | this is a description |
      Then a new page should have loaded since I started watching
      When I set the following Totara form fields to these values:
        | Interval (seconds) | 12 |
      And I click on "Save changes" "button"

    Scenario: values passed correctly to and from Gallery
      When I set the following Totara form fields to these values:
        | URL | www.example.com |
        | Title | this is the title |
        | Description | this is the description |
        | Alternate text | this is the alt text |
      And I click on "Save changes" "button"
      And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
      And I click on "Edit" "link" in the "Featured Links" "block"
      And I set the following fields to these values:
        | Tile type | Gallery |
      Then the following fields match these values:
        | URL | https://www.example.com |
        | Title | this is the title |
        | Description | this is the description |
        | Alternate text | this is the alt text |
      When I set the following fields to these values:
        | URL | www.example2.com |
        | Title | this is the title2 |
        | Description | this is the description2 |
        | Alternate text | this is the alt text2 |
      And I click on "Save changes" "button"
      And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
      And I click on "Edit" "link" in the "Featured Links" "block"
      And I set the following fields to these values:
        | Tile type | Static |
      Then the following fields match these values:
        | URL | http://www.example2.com |
        | Title | this is the title2 |
        | Description | this is the description2 |
        | Alternate text | this is the alt text2 |