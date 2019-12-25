@totara @totara_job @javascript
Feature: Assigning a manager to a user via the job assignment page
  In order to assign a manager to a user
  As a user with correct permissions
  I must be able to select a manager and the manager's job assignment

  Background:
    Given I am on a totara site
    And the following "users" exist:
     | username | firstname | lastname | email                   |
     | user1    | User      | One      | user1@example.com       |
     | user2    | User      | Two      | user2@example.com       |
     | manager1 | Manager   | One      | manager1@example.com    |
     | manager2 | Manager   | Two      | manager2@example.com    |
     | jobadmin | Job       | Admin    | jobadmin@example.com    |
    And I log in as "admin"
    And I navigate to "Define roles" node in "Site administration > Permissions"
    And I press "Add a new role"
    And I press "Continue"
    And I set the following fields to these values:
     | Short name       | jobadmin |
     | Custom full name | jobadmin |
     | System           | 1        |
     | User             | 1        |
    And I press "Create this role"
    And I set the following system permissions of "jobadmin" role:
     | capability                          | permission |
     | totara/hierarchy:assignuserposition | Allow      |
     | moodle/user:update                  | Allow      |
     | moodle/user:viewdetails             | Allow      |
    And the following "role assigns" exist:
      | user     | role          | contextlevel | reference |
      | jobadmin | jobadmin      | System       |           |

  Scenario: A user who is allowed to alter their own job assignment details can select another user as their manager
    Given I set the following system permissions of "Authenticated user" role:
     | totara/hierarchy:assignselfposition | Allow      |
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Manager One" "link" in the "Manager One" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
     | Full name | Development Manager |
     | ID Number | 1                   |
    And I click on "Add job assignment" "button"
    Then I should see "Development Manager"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Manager Two" "link" in the "Manager Two" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Design Manager |
      | ID Number | 1              |
    And I click on "Add job assignment" "button"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Brand Manager |
      | ID Number | 2             |
    And I click on "Add job assignment" "button"
    Then I should see "Design Manager"
    And I should see "Brand Manager"
    When I log out
    And I log in as "user1"
    And I follow "Profile" in the user menu
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
     | Full name | Developer |
     | ID Number | 1         |
    And I press "Choose manager"

    # User cannot create a job without the necessary capability.
    Then I should see "User Two - requires job assignment entry"

    # If the manager has one selectable option (whether it's job assignment or create),
    # show that option on the one line with the manager's name.
    And I should see "Manager One - Development Manager"

    # If the manager has more than one option, it should expandable.
    And I should see "Manager Two"
    And I should not see "Design Manager"
    And I should not see "Brand Manager"
    When I click on "Manager Two" "link" in the "Choose manager" "totaradialogue"
    Then I should see "Design Manager"
    And I should see "Brand Manager"

    When I click on "Manager One - Development Manager" "link" in the "Choose manager" "totaradialogue"
    Then I should not see "Create empty job assignment"
    When I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Manager One - Development Manager"
    When I click on "Add job assignment" "button"
    Then I should see "Developer"
    When I click on "Developer" "link"
    Then I should see "Manager One - Development Manager"

  Scenario: A user with the permissions to alter any job assignment can select managers for any user
    Given I log out
    Given I log in as "jobadmin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User One" "link" in the "User One" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
     | Full name | Developer |
     | ID Number | 1         |
    And I press "Choose manager"
    And I click on "Manager One - create empty job assignment" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Manager One - create empty job assignment"
    When I click on "Add job assignment" "button"
    Then I should see "Developer"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Manager One" "link" in the "Manager One" "table_row"
    Then I should see "Unnamed job assignment (ID: 1)"
    When I click on "Unnamed job assignment (ID: 1)" "link"
    And I set the following fields to these values:
      | Full name | Development Manager |
      | ID Number | 1                   |
    And I click on "Update job assignment" "button"
    Then I should see "Development Manager"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User Two" "link" in the "User Two" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Tester |
      | ID Number | 1      |
    And I press "Choose manager"
    Then I should see "Manager One"
    And I should not see "Manager One - create empty job assignment"
    When I click on "Manager One" "link" in the "Choose manager" "totaradialogue"
    Then I should see "Development Manager" in the "Choose manager" "totaradialogue"
    And I should see "Create empty job assignment"
    When I click on "Create empty job assignment" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Manager One - create empty job assignment"
    When I click on "Add job assignment" "button"
    And I click on "Tester" "link"
    Then I should see "Manager One - Unnamed job assignment (ID: 2)"
    When I click on "Cancel" "button"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Manager One" "link" in the "Manager One" "table_row"
    And I click on "Unnamed job assignment (ID: 2)" "link"
    And I set the following fields to these values:
     | Full name | Testing Manager |
    And I click on "Update job assignment" "button"
    Then I should see "Testing Manager"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User Two" "link" in the "User Two" "table_row"
    And I click on "Tester" "link"
    Then I should see "Manager One - Testing Manager"

  Scenario: A user who is allowed to alter their own job assignment details has same options available when using search to assign manager
    Given I set the following system permissions of "Authenticated user" role:
      | totara/hierarchy:assignselfposition | Allow      |
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Manager One" "link" in the "Manager One" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Development Manager |
      | ID Number | 1                   |
    And I click on "Add job assignment" "button"
    Then I should see "Development Manager"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Manager Two" "link" in the "Manager Two" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Design Manager |
      | ID Number | 1              |
    And I click on "Add job assignment" "button"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Brand Manager |
      | ID Number | 2             |
    And I click on "Add job assignment" "button"
    Then I should see "Design Manager"
    And I should see "Brand Manager"
    When I log out
    And I log in as "user1"
    And I follow "Profile" in the user menu
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Developer |
      | ID Number | 1         |
    And I press "Choose manager"
    And I click on "Search" "link" in the "Choose manager" "totaradialogue"
    And I search for "User" in the "Choose manager" totara dialogue

    # User cannot create a job without the necessary capability.
    Then I should see "User Two - requires job assignment entry" in the "#search-tab" "css_element"

    # Options can't be expandable in search, so all available are shown on their own line in format {manager name} - {manager job assignment}.
    When I search for "Manager" in the "Choose manager" totara dialogue
    Then I should see "Manager One - Development Manager" in the "#search-tab" "css_element"
    And I should see "Manager Two - Design Manager" in the "#search-tab" "css_element"
    And I should see "Manager Two - Brand Manager" in the "#search-tab" "css_element"
    And I should not see "Manager One - requires job assignment entry" in the "#search-tab" "css_element"
    And I should not see "Manager One  - create empty job assignment" in the "#search-tab" "css_element"
    And I should not see "Manager Two - requires job assignment entry" in the "#search-tab" "css_element"
    And I should not see "Manager Two  - create empty job assignment" in the "#search-tab" "css_element"

    When I click on "Manager One - Development Manager" "link" in the "#search-tab" "css_element"
    When I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Manager One - Development Manager"
    When I click on "Add job assignment" "button"
    Then I should see "Developer"
    When I click on "Developer" "link"
    Then I should see "Manager One - Development Manager"

  Scenario: A user with permissions to alter any job assignment has the same dialog options available in Search as in Browse tabs
    Given I log out
    Given I log in as "jobadmin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User One" "link" in the "User One" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Developer |
      | ID Number | 1         |
    And I press "Choose manager"
    And I click on "Search" "link" in the "Choose manager" "totaradialogue"
    And I search for "Manager" in the "Choose manager" totara dialogue
    Then I should see "Manager Two - create empty job assignment" in the "#search-tab" "css_element"
    And I should see "Manager One - create empty job assignment" in the "#search-tab" "css_element"
    When I click on "Manager One - create empty job assignment" "link" in the "#search-tab" "css_element"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Manager One - create empty job assignment"
    When I click on "Add job assignment" "button"
    Then I should see "Developer"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Manager One" "link" in the "Manager One" "table_row"
    Then I should see "Unnamed job assignment (ID: 1)"
    When I click on "Unnamed job assignment (ID: 1)" "link"
    And I set the following fields to these values:
      | Full name | Development Manager |
      | ID Number | 1                   |
    And I click on "Update job assignment" "button"
    Then I should see "Development Manager"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User Two" "link" in the "User Two" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Tester |
      | ID Number | 1      |
    And I press "Choose manager"
    And I click on "Search" "link" in the "Choose manager" "totaradialogue"
    And I search for "Manager" in the "Choose manager" totara dialogue
    Then I should see "Manager One - Development Manager" in the "#search-tab" "css_element"
    And I should see "Manager One - create empty job assignment" in the "#search-tab" "css_element"
    And I should see "Manager Two - create empty job assignment" in the "#search-tab" "css_element"
    When I click on "Manager One - create empty job assignment" "link" in the "#search-tab" "css_element"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Manager One - create empty job assignment"
    When I click on "Add job assignment" "button"
    And I click on "Tester" "link"
    Then I should see "Manager One - Unnamed job assignment (ID: 2)"
    When I click on "Cancel" "button"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Manager One" "link" in the "Manager One" "table_row"
    And I click on "Unnamed job assignment (ID: 2)" "link"
    And I set the following fields to these values:
      | Full name | Testing Manager |
    And I click on "Update job assignment" "button"
    Then I should see "Testing Manager"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User Two" "link" in the "User Two" "table_row"
    And I click on "Tester" "link"
    Then I should see "Manager One - Testing Manager"

  Scenario: Disabling multiple job assignments removes the create empty job assignment option in the totara dialog
    And I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Allow multiple job assignments" to "Disable"
    And I press "Save changes"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User One" "link" in the "User One" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Developer |
      | ID Number | 1         |
    And I press "Choose manager"
    Then I should see "Manager One (manager1@example.com) - create empty job assignment" in the "Choose manager" "totaradialogue"
    And I should see "Manager Two (manager2@example.com) - create empty job assignment" in the "Choose manager" "totaradialogue"
    When I click on "Manager One (manager1@example.com) - create empty job assignment" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I click on "Add job assignment" "button"
    Then I should see "Job assignment saved"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User One" "link" in the "User One" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Developer |
      | ID Number | 1         |
    And I press "Choose manager"
    Then I should not see "Manager One (manager1@example.com) - create empty job assignment" in the "Choose manager" "totaradialogue"
    And I should see "Manager One (manager1@example.com)" in the "Choose manager" "totaradialogue"
    And I should see "Manager Two (manager2@example.com) - create empty job assignment" in the "Choose manager" "totaradialogue"
