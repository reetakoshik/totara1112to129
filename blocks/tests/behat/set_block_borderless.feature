@core @core_block @totara @javascript
Feature: Any block can be set so that it has no border
    A User that can edit a block should be able to set it so that it has no border
    some block by default will have no border

  Background:
    When I log in as "admin"
    And I follow "Dashboard"
    And I click on "Customise this page" "button"
    And I add the "HTML" block

  Scenario: Test that a block can be set to have no border
    Then ".chromeless" "css_element" should not exist
    When I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Override default block title    | Yes                       |
      | Block title                     | New Title                 |
      | Content                         | some content              |
      | Show border                     | 0                         |
    When I set the field "Content" to "some content"
    And I click on "Save changes" "button"
    Then ".chromeless" "css_element" should exist
    When I press "Stop customising this page"
    Then ".chromeless" "css_element" should exist

  Scenario: Test that a block can be set to have a border from borderless
    Then ".chromeless" "css_element" should not exist
    When I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Override default block title    | Yes                       |
      | Block title                     | HTML                      |
      | Content                         | some content              |
      | Show border                     | 0                         |
    And I click on "Save changes" "button"
    Then ".chromeless" "css_element" should exist
    When I configure the "HTML" block
    And I set the following fields to these values:
      | Show border                     | 1 |
    And I click on "Save changes" "button"
    Then ".chromeless" "css_element" should not exist

    # Test a block that is chromeless by default.
    # However there is not one of them at the moment.
