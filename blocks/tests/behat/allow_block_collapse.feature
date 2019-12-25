@core @core_block @totara @javascript
Feature: Allow block collapsable
  In order to configure blocks hide
  As a admin
  I need to expand and collapse block on a page

  Background:
    When I log in as "admin"
    And I follow "Dashboard"
    And I click on "Customise this page" "button"
    And I add the "HTML" block

  Scenario: configure that block can not collapse
    When I configure the "(new HTML block)" block
    And I expand all fieldsets
    Then I should see "Block appearance"
    Then I should see "Show header"
    Then I should see "Block appearance"
    And I set the following fields to these values:
      | Override default block title    | Yes                       |
      | Block title                     | New Title                 |
      | Content                         | some content              |
      | Allow block hiding              | 0                         |
    And I click on "Save changes" "button"
    When I press "Stop customising this page"
    Then I should not see "Hide New Title"

  Scenario: configure that block can collapse
    When I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Override default block title    | Yes                       |
      | Block title                     | New Title                 |
      | Content                         | some content              |
    And I click on "Save changes" "button"
    When I press "Stop customising this page"
    Then I should see "Hide New Title"
