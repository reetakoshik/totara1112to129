@core @core_block @totara
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
    And I expand all fieldsets
    Then I should see "Display block border"
    When I set the following fields to these values:
      | Content | some content |
      | Display block border | 0     |
    And I click on "Save changes" "button"
    Then ".chromeless" "css_element" should exist
    When I press "Stop customising this page"
    Then ".chromeless" "css_element" should exist

  Scenario: Test that a block can be set to have a border from borderless
    Then ".chromeless" "css_element" should not exist
    When I configure the "(new HTML block)" block
    And I expand all fieldsets
    Then I should see "Display block border"
    When I set the following fields to these values:
      | Content | some content |
      | Display block border | 0     |
    And I click on "Save changes" "button"
    Then ".chromeless" "css_element" should exist
    When I configure the "HTML" block
    And I set the field "Display block border" to "1"
    And I click on "Save changes" "button"
    Then ".chromeless" "css_element" should not exist

    # Test a block that is chromeless by default.
    # However there is not one of them at the moment.
