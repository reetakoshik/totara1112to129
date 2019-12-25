@totara @core @core_reminder @javascript
Feature: Settings for feedback reminders are displayed as expected
  In order to set up feedback reminders
  As an admin
  I need to see information on their settings

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname   | shortname | enablecompletion |
      | Course One | course1   | 1                |
    And I log in as "admin"
    And I am on "Course One" course homepage with editing mode on
    And I add a "Feedback" to section "1" and I fill the form with:
      | Name                | Feedback1             |
      | Description         | Feedback1 description |

  Scenario: No notification is shown when global Max days since completion is not set
    Given I navigate to "Manage activities" node in "Site administration > Plugins > Activity modules"
    And I click on "Settings" "link" in the "Feedback" "table_row"
    Then the following fields match these values:
      | Maximum days since completion | 0 |
    And I am on "Course One" course homepage
    And I navigate to "Reminders" node in "Course administration"
    Then I should not see "The global setting, 'Maximum days since completion', is currently set to 0 days. Any messages with a 'Period' value greater than or equal to this will not be sent."
    And I expand all fieldsets
    And I set the following fields to these values:
     | Title            | Reminder1 |
     | Requirement      | Feedback1 |
     | invitationperiod | Same day  |
     | reminderperiod   | 10        |
    And I press "Save changes"
    Then I should not see "The global setting, 'Maximum days since completion', is currently set to 0 days. Any messages with a 'Period' value greater than or equal to this will not be sent."

  Scenario: No notification is shown when global Max days since completion is set lower than any message period
    Given I set the following administration settings values:
      | Maximum days since completion | 15 |
    And I am on "Course One" course homepage
    And I navigate to "Reminders" node in "Course administration"
    Then I should not see "The global setting, 'Maximum days since completion', is currently set to 15 days. Any messages with a 'Period' value greater than or equal to this will not be sent."
    And I expand all fieldsets
    And I set the following fields to these values:
      | Title            | Reminder1 |
      | Requirement      | Feedback1 |
      | invitationperiod | Same day  |
      | reminderperiod   | 10        |
    And I press "Save changes"
    Then I should not see "The global setting, 'Maximum days since completion', is currently set to 15 days. Any messages with a 'Period' value greater than or equal to this will not be sent."

  Scenario: A notification is shown when global Max days since completion is set lower than any message period
    Given I set the following administration settings values:
      | Maximum days since completion | 8 |
    And I am on "Course One" course homepage
    And I navigate to "Reminders" node in "Course administration"
    Then I should not see "The global setting, 'Maximum days since completion', is currently set to 8 days. Any messages with a 'Period' value greater than or equal to this will not be sent."
    And I expand all fieldsets
    And I set the following fields to these values:
      | Title            | Reminder1 |
      | Requirement      | Feedback1 |
      | invitationperiod | Same day  |
      | reminderperiod   | 10        |
    And I press "Save changes"
    Then I should see "The global setting, 'Maximum days since completion', is currently set to 8 days. Any messages with a 'Period' value greater than or equal to this will not be sent."