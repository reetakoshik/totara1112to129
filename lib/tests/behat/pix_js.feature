@core @javascript
Feature: Javascript template rendering

  Background:
    Given I log in as "admin"
    And I navigate to "Template library" node in "Site administration > Development"

  Scenario: Template library naviagtion works
    Given I set the field "Component" to "Current Learning"
    And I follow "block_current_learning/block"
    Then I should see "Displaying 1 - 1 of 1 results"

  Scenario: Javascript string rendering
    Given I follow "core/test"
    Then I should see "View My Team" in the "#template_string_check" "css_element"
    And I should see "Skip hello" in the "#template_string_with_variable" "css_element"
    And I should see "View My Team" in the "#template_string_with_variable_key" "css_element"

  Scenario: Javascript pix/flex rendering works
    Given I set the field "Component" to "Subsystem (core)"
    And I set the field with xpath "//*[@data-region='list-templates']//*[@id='search']" to "test"
    And I follow "core/test"

    # Old style pix
    And "#template_pix_alt img[alt='test case']" "css_element" should exist
    And "#template_pix_alt img[title='test case']" "css_element" should exist
    And "#template_pix_alt img.icon" "css_element" should exist

    # Old style pix with string
    And "#template_pix_alt_variable img[alt='']" "css_element" should exist
    And "#template_pix_alt_variable img[title='']" "css_element" should exist
    And "#template_pix_alt_variable img.icon" "css_element" should exist

    # Old style pix with data
    And "#template_pix_alt_json img[alt='']" "css_element" should exist
    And "#template_pix_alt_json img.muppet" "css_element" should exist

    # Pix replacement with no data
    And "#template_flex_pix .flex-icon.fa-asterisk" "css_element" should exist
    And "#template_flex_pix .sr-only" "css_element" should not exist

    # Pix replacement with text alt
    And "#template_flex_pix_alt .flex-icon.fa-asterisk" "css_element" should exist
    And "#template_flex_pix_alt .flex-icon[data-flex-icon='core|req']" "css_element" should exist
    And I should see "test case" in the "#template_flex_pix_alt" "css_element"

    # Pix replacement with string alt
    And "#template_flex_pix_alt_variable .flex-icon.fa-asterisk" "css_element" should exist
    And "#template_flex_pix_alt_variable .flex-icon[data-flex-icon='core|req']" "css_element" should exist

    # Pix replacement with data
    And "#template_flex_pix_alt_variable .flex-icon.fa-asterisk" "css_element" should exist
    And "#template_flex_pix_alt_variable .flex-icon[data-flex-icon='core|req']" "css_element" should exist
    And "#template_flex_pix_alt_json .flex-icon.muppet" "css_element" should exist

    # Flex without alt
    And "#template_flex .flex-icon.fa-caret-right" "css_element" should exist
    And "#template_flex .sr-only" "css_element" should not exist

    # Flex with alt
    And "#template_flex_alt .flex-icon.fa-cog" "css_element" should exist
    And I should see "test case" in the "#template_flex_alt .sr-only" "css_element"

    # Flex with alt string
    And "#template_flex_alt .flex-icon.fa-cog" "css_element" should exist
    And "#template_flex_string_alt .sr-only" "css_element" should not exist

    # Flex with alt data
    And "#template_flex_string_data .sr-only" "css_element" should not exist
    And "#template_flex_string_data .flex-icon.muppet" "css_element" should exist

    # Userdate helper - should never be used as it is an XSS risk
    And I should see "1 January 2011" in the "#template_userdata_helper" "css_element"

    # shorten helper
    And I should see "Lorem ipsum dolor sit amet, ..." in the "#template_shorten_helper" "css_element"
