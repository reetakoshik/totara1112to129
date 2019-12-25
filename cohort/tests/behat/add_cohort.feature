@core @core_cohort
Feature: Add cohorts of users
  In order to create site-wide groups
  As an admin
  I need to create cohorts and add users on them

# Totara: audiences are very different from upstream.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | user1 | First | User | first@example.com |
      | user2 | Second | User | second@example.com |
      | user3 | Third | User | third@example.com |
      | user4 | Forth | User | forth@example.com |
    And I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Add new audience"
    And I set the following fields to these values:
      | Name | Test cohort name |
      | Context | System |
      | Audience ID | 333 |
      | Description | Test cohort description |
    And I press "Save changes"

  Scenario: Add a cohort
    When I follow "Audiences"
    Then I should see "Test cohort name"
    And I should see "333"
#    And I should see "Test cohort description"
#    And I should see "Created manually"

  Scenario: Add users to a cohort selecting them from the system users list
    When I add "First User (first@example.com)" user to "333" cohort members
    And I add "Second User (second@example.com)" user to "333" cohort members
    And I navigate to "Audiences" node in "Site administration > Audiences"
    Then I should see "2" in the "td.cohort_numofmembers" "css_element"
    And I follow "Edit"
    And I follow "Edit members"
    And the "Current users" select box should contain "First User (first@example.com)"
    And the "Current users" select box should contain "Second User (second@example.com)"
    And the "Current users" select box should not contain "Forth User (forth@example.com)"

  @javascript
  Scenario: Add users to a cohort using a bulk user action
    When I navigate to "Bulk user actions" node in "Site administration > Users"
    And I set the field "Available" to "Third User"
    And I press "Add to selection"
    And I set the field "Available" to "Forth User"
    And I press "Add to selection"
    And I set the field "id_action" to "Add to audience"
    And I press "Go"
    And I set the field "Audience" to "Test cohort name [333]"
    And I press "Add to audience"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    Then I should see "2" in the "td.cohort_numofmembers" "css_element"
    And I follow "Edit"
    And I follow "Edit members"
    And the "Current users" select box should contain "Third User (third@example.com)"
    And the "Current users" select box should contain "Forth User (forth@example.com)"
    And the "Current users" select box should not contain "First User (first@example.com)"
