@totara @totara_reportbuilder @totara_scheduledreports @javascript
Feature: Test that report builder scheduled report recipient settings restrict who can be added as a recipient

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
    And the following "cohorts" exist:
      | name | idnumber |
      | CH1  | CH1      |
      | CH2  | CH2      |
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Schedulable Report"
    And I set the field "Source" to "User"
    And I press "Create report"
    And I log out

  Scenario: I can add audiences, system users, external emails, and myself by default
    When I log in as "admin"
    And I click on "Reports" in the totara menu
    And I press "Add scheduled report"
    Then I should see "Send to self"
    And I should see "Audiences"
    And I should see "System users"
    And I should see "External users email"
    And I should not see "Other recipients"

    When I press "Add audiences"
    And I click on "CH1" "link" in the "Add audiences" "totaradialogue"
    And I click on "Save" "button" in the "Add audiences" "totaradialogue"
    And I wait "1" seconds
    Then I should see "CH1"
    And I should not see "CH2"

    When I press "Add system user(s)"
    And I click on "user1@example.com" "link" in the "Add system user(s)" "totaradialogue"
    And I click on "Save" "button" in the "Add system user(s)" "totaradialogue"
    And I wait "1" seconds
    Then I should see "User One"
    And I should not see "User Two"

    When I set the field "External email address to add" to "a@example.com"
    And I press "Add email"
    Then I should see "a@example.com"
    And I should not see "b@example.com"

    When I press "Save changes"
    And I click on "Edit" "link" in the "Schedulable Report" "table_row"
    Then I should see "CH1"
    And I should not see "CH2"
    And I should see "User One"
    And I should not see "User Two"
    And I should see "a@example.com"
    And I should not see "b@example.com"

  Scenario: Reduced settings do not remove recipients when editing a scheduled report
    When I log in as "admin"
    And I click on "Reports" in the totara menu
    And I press "Add scheduled report"

    When I press "Add audiences"
    And I click on "CH1" "link" in the "Add audiences" "totaradialogue"
    And I click on "Save" "button" in the "Add audiences" "totaradialogue"
    And I wait "1" seconds
    And I press "Add system user(s)"
    And I click on "user1@example.com" "link" in the "Add system user(s)" "totaradialogue"
    And I click on "Save" "button" in the "Add system user(s)" "totaradialogue"
    And I wait "1" seconds
    And I set the field "External email address to add" to "a@example.com"
    And I press "Add email"
    Then I should see "CH1"
    And I should see "User One"
    And I should see "a@example.com"

    When I press "Save changes"
    # Change what is allowed and set it to just external users.
    And the following config values are set as admin:
      | allowedscheduledrecipients | sendtoself | totara_reportbuilder |
    And I click on "Edit" "link" in the "Schedulable Report" "table_row"
    Then I should see "Send to self"
    And I should not see "Audiences"
    And I should not see "System users"
    And I should not see "External users email"
    And I should see "Other recipients"
    And I should see "Audience: CH1"
    And I should not see "User: Admin User"
    And I should see "User: User One"
    And I should see "a@example.com"

    When I press "Save changes"
    # Change what is allowed and set it to just external users.
    And the following config values are set as admin:
      | allowedscheduledrecipients | audiences,systemusers,emailexternalusers,sendtoself | totara_reportbuilder |
    And I click on "Edit" "link" in the "Schedulable Report" "table_row"
    Then I should see "Send to self"
    And I should see "Audiences"
    And I should see "System users"
    And I should see "External users email"
    And I should not see "Other recipients"
    And I should see "CH1"
    And I should not see "Admin User" in the ".mform" "css_element"
    And I should see "User One"
    And I should see "a@example.com"

    When I press "Save changes"
    # Change what is allowed and set it to just external users.
    And the following config values are set as admin:
      | allowedscheduledrecipients | emailexternalusers | totara_reportbuilder |
    And I click on "Edit" "link" in the "Schedulable Report" "table_row"
    Then I should not see "Send to self"
    And I should not see "Audiences"
    And I should not see "System users"
    And I should see "External users email"
    And I should see "Other recipients"
    And I should see "Audience: CH1"
    And I should see "User: Admin User"
    And I should see "User: User One"
    And I should see "a@example.com"

    When I set the following fields to these values:
      | User: User One | 0 |
      | User: Admin User | 0 |
      | Audience: CH1 | 0 |
    And I press "Save changes"
    And I click on "Edit" "link" in the "Schedulable Report" "table_row"
    Then I should not see "Send to self"
    And I should not see "Audiences"
    And I should not see "System users"
    And I should see "External users email"
    And I should not see "Other recipients"
    And I should not see "Audience: CH1"
    And I should not see "User: Admin User"
    And I should not see "User: User One"
    And I should see "a@example.com"
