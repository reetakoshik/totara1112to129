@totara @totara_core @totara_core_select
Feature: Test text search select element

  @javascript
  Scenario: The text search select element should behave correctly
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to the "select_search_text" fixture in the "totara/core" plugin

    # Use xpath as CSS doesn't have a parent selector
    Then I should see "Test full text search title 1"
    And the field "testsearchtext1_input" matches value "Test start text 1"
    And "//*[@class='tw-selectSearchText']//*[@id='testsearchtext1_input']/parent::*/a[contains(@class, 'tw-selectSearchText__hidden')]" "xpath_element" should not exist
    And I should see "Test full text search title 2 as placeholder"
    And the field "Test full text search title 2 as placeholder" matches value ""
    And "//*[@class='tw-selectSearchText']//*[@id='testsearchtext2_input']/parent::*/a[contains(@class, 'tw-selectSearchText__hidden')]" "xpath_element" should exist

    When I click on "//*[@class='tw-selectSearchText']//*[@id='testsearchtext1_input']/parent::*/a[contains(@class, 'tw-selectSearchText__field_clear')]" "xpath_element"
    Then the field "testsearchtext1_input" matches value ""
    And "//*[@class='tw-selectSearchText']//*[@id='testsearchtext1_input']/parent::*/a[contains(@class, 'tw-selectSearchText__hidden')]" "xpath_element" should exist

    When I set the field "Test full text search title 2 as placeholder" to "asdf"
    And I press key "13" in the field "Test full text search title 2 as placeholder"
    Then "//*[@class='tw-selectSearchText']//*[@id='testsearchtext2_input']/parent::*/a[contains(@class, 'tw-selectSearchText__hidden')]" "xpath_element" should not exist

    When I set the field "Test full text search title 2 as placeholder" to "qwer"
    And I click on "Search" "button" in the "//*[@class='tw-selectSearchText']//*[@id='testsearchtext1_input']/ancestor::*[@class='tw-selectSearchText']" "xpath_element"
    And "//*[@class='tw-selectSearchText']//*[@id='testsearchtext1_input']/parent::*/a[contains(@class, 'tw-selectSearchText__hidden')]" "xpath_element" should exist