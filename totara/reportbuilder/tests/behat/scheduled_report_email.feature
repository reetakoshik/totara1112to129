@totara @totara_reportbuilder @totara_scheduledreports @tabexport @javascript
Feature: Test that report builder reports can be scheduled to be emailed
  Create a report
  Go to Reports
  Create a schedule
  Check that it shows the scheduled report in the list
  #NOTE: This only confirms that the emails are added to the list, it can't test that the e-mails actually get sent

  Background: Set up a schedulable report
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Schedulable Report"
    And I set the field "Source" to "User"
    And I press "Create report"
    And I click on "Reports" in the totara menu
    And I press "Add scheduled report"

  Scenario: Add audiences to report e-mail recipients
    Given the following "cohorts" exist:
      | name | idnumber |
      | CH1  | CH1      |
      | CH2  | CH2      |
    When I press "Add audiences"
    And I click on "CH1" "link" in the "Add audiences" "totaradialogue"
    And I click on "Save" "button" in the "Add audiences" "totaradialogue"
    And I wait "1" seconds
    Then I should see "CH1"
    And I should not see "CH2"

    When I press "Add audiences"
    And I click on "CH2" "link" in the "Add audiences" "totaradialogue"
    And I click on "Save" "button" in the "Add audiences" "totaradialogue"
    And I wait "1" seconds
    Then I should see "CH1"
    And I should see "CH2"

    # This requires that the audience id to be correct
    When I click on "Delete" "link" in the "#audiences_1" "css_element"
    Then I should not see "CH1"
    And I should see "CH2"

    When I press "Save changes"
    And I click on "Edit" "link" in the "Schedulable Report" "table_row"
    Then I should not see "CH1"
    And I should see "CH2"

  Scenario: Add individual users to report e-mail recipients
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
    When I press "Add system user(s)"
    And I click on "user1@example.com" "link" in the "Add system user(s)" "totaradialogue"
    And I click on "Save" "button" in the "Add system user(s)" "totaradialogue"
    And I wait "1" seconds
    Then I should see "User One"
    And I should not see "User Two"

    When I press "Add system user(s)"
    And I click on "user2@example.com" "link" in the "Add system user(s)" "totaradialogue"
    And I click on "Save" "button" in the "Add system user(s)" "totaradialogue"
    And I wait "1" seconds
    Then I should see "User One"
    And I should see "User Two"

    # This requires that the user id to be correct
    When I click on "Delete" "link" in the "#systemusers_3" "css_element"
    Then I should not see "User One"
    And I should see "User Two"

    When I press "Save changes"
    And I click on "Edit" "link" in the "Schedulable Report" "table_row"
    Then I should not see "User One"
    And I should see "User Two"

  Scenario: Add external users to report e-mail recipients
    When I set the field "External email address to add" to "a@example.com"
    And I press "Add email"
    Then I should see "a@example.com"
    And I should not see "b@example.com"

    When I set the field "External email address to add" to "b@example.com"
    And I press "Add email"
    Then I should see "a@example.com"
    And I should see "b@example.com"

    # This requires that the audience id to be correct
    When I click on "Delete" "link" in the ".list-externalemails div[data-id='a@example.com']" "css_element"
    Then I should not see "a@example.com"
    And I should see "b@example.com"

    When I press "Save changes"
    And I click on "Edit" "link" in the "Schedulable Report" "table_row"
    Then I should not see "a@example.com"
    And I should see "b@example.com"

    # Now to quickly test a few valid email addresses.
    When I set the field "External email address to add" to "firstname.lastname@example.com"
    And I press "Add email"
    Then I should see "firstname.lastname@example.com"

    When I set the field "External email address to add" to "firstname+subject@example.com"
    And I press "Add email"
    Then I should see "firstname+subject@example.com"

    And I set the field "External email address to add" to "address@subdomain.example.com"
    And I press "Add email"
    Then I should see "address@subdomain.example.com"

    And I set the field "External email address to add" to "firstname&lastname@example.com"
    And I press "Add email"
    Then I should see "firstname&lastname@example.com"

    And I set the field "External email address to add" to "!#$%&amp`*+/=?^`{|}~@example.com"
    And I press "Add email"
    Then I should see "!#$%&amp`*+/=?^`{|}~@example.com"

    And I set the field "External email address to add" to "firstname@localhost"
    And I press "Add email"
    Then I should see "firstname@localhost"

  Scenario: Add myself as a recipient of the scheduled report
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |

    When I press "Add system user(s)"
    And I click on "user1@example.com" "link" in the "Add system user(s)" "totaradialogue"
    And I should not see "Admin User" in the "Add system user(s)" "totaradialogue"
    And I click on "Save" "button" in the "Add system user(s)" "totaradialogue"
    And I wait "1" seconds
    Then I should see "User One"
    And I should not see "User Two"

    When I press "Save changes"
    And I click on "Edit" "link" in the "Schedulable Report" "table_row"
    Then I should see "User One"
    And I should not see "User Two"

    When I set the field "Send to self" to "1"
    And I press "Save changes"
    And I click on "Edit" "link" in the "Schedulable Report" "table_row"
    Then I should see "User One"
    And I should not see "User Two"

    When I press "Add system user(s)"
    And I click on "user2@example.com" "link" in the "Add system user(s)" "totaradialogue"
    And I should not see "Admin User" in the "Add system user(s)" "totaradialogue"
    And I click on "Save" "button" in the "Add system user(s)" "totaradialogue"
    And I wait "1" seconds
    Then I should see "User One"
    And I should see "User Two"

    # This requires that the user id to be correct
    When I click on "Delete" "link" in the "#systemusers_3" "css_element"
    Then I should not see "User One"
    And I should see "User Two"

    When I press "Save changes"
    And I click on "Edit" "link" in the "Schedulable Report" "table_row"
    Then I should not see "User One"
    And I should see "User Two"

  Scenario: Delete single email entries but keep at least one recipient email address
    Given the following "cohorts" exist:
      | name | idnumber |
      | CH1  | CH1      |
      | CH2  | CH2      |
    When I press "Add audiences"
    And I click on "CH1" "link" in the "Add audiences" "totaradialogue"
    And I click on "Save" "button" in the "Add audiences" "totaradialogue"
    And I wait "1" seconds
    Then I should see "CH1"
    And I should not see "CH2"
    And  I set the field "Send to self" to "0"

    When I set the field "External email address to add" to "a@example.com"
    And I press "Add email"
    Then I should see "a@example.com"

    When I click on "Delete" "link" in the ".list-externalemails div[data-id='a@example.com']" "css_element"
    Then I should not see "a@example.com"

    When I press "Save changes"
    And I click on "Edit" "link" in the "Schedulable Report" "table_row"
    Then I should not see "a@example.com"

  # This requires that the audience id to be correct
    When I click on "Delete" "link" in the "#audiences_1" "css_element"
    Then I should not see "CH1"

    When I press "Save changes"
    Then I should see "At least one recipient email address is required for export option you selected"
