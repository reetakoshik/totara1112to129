@core @core_admin @core_admin_roles
Feature: Assigning administrator role to a user must be logged inside logstore system
  Scenario: Admin is assigning administrator role to a user and check the log
    Given the following "users" exist:
      | username | firstname | lastname | email         |
      | bolobala | Kian      | Nguyen   | a@example.com |
    And I log in as "admin"
    And I navigate to "Permissions > Site administrators" in site administration

    And I set the field "Users" to "Kian Nguyen (bolobala, a@example.com)"
    And I click on "Add" "button"
    And I click on "Continue" "button"
    And I navigate to "Server > Logs" in site administration
    When I click on "Get these logs" "button"
    Then I should see "Kian Nguyen"
    And I should see "Admin user group updated"
    And "Kian Nguyen" "text" should appear before "Admin user group updated" "text"