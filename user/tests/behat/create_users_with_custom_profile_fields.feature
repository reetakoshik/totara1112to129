@core @core_user @javascript
Feature: Create users with custom profile fields
  In order to use custom profile fields
  As an admin
  I need to be able to create multiple users without providing a value for the custom fields

  Scenario: Can create multiple users without specifying value for unique required custom field
    Given I log in as "admin"
    And I navigate to "User profile fields" node in "Site administration > Users"
    And I set the following fields to these values:
      | datatype | text |
    #redirect
    And I set the following fields to these values:
      | Short name                      | requiredfield     |
      | Name                            | Required Field    |
      | Is this field required          | Yes               |
      | Is this field locked            | Yes               |
      | Should the data be unique       | Yes               |
      | Who is this field visible to    | Not visible       |
    And I press "Save changes"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I press "Add a new user"
    And I set the following fields to these values:
      | Username                        | user1             |
      | New password                    | A.New.Pw.123      |
      | First name                      | User              |
      | Surname                         | One               |
      | Email address                   | a1@example.com    |
    And I press "Create user"
    Then the following should exist in the "system_browse_users" table:
      | Username | User's Email   |
      | user1    | a1@example.com |
    When I press "Add a new user"
    And I set the following fields to these values:
      | Username                        | user2             |
      | New password                    | A.New.Pw.123      |
      | First name                      | User              |
      | Surname                         | Two               |
      | Email address                   | a2@example.com   |
    And I press "Create user"
    Then the following should exist in the "system_browse_users" table:
      | Username | User's Email   |
      | user1    | a1@example.com |
      | user2    | a2@example.com |

  Scenario: Can create users with custom fields
    Given I log in as "admin"
    And I navigate to "User profile fields" node in "Site administration > Users"
    And I set the following fields to these values:
      | datatype | text |
    #redirect
    And I set the following fields to these values:
      | Short name                      | textfield     |
      | Name                            | Text Field    |
      | Is this field required          | Yes               |
    And I press "Save changes"
    And I set the following fields to these values:
      | datatype | menu |
    # redirect
    And I set the following fields to these values:
      | Short name                      | menufield     |
      | Name                            | Menu Field    |
    And I set the field "Menu options (one per line)" to multiline:
"""
AAA
BBB
CCC
"""
    And I press "Save changes"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I press "Add a new user"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Username                        | user1             |
      | New password                    | A.New.Pw.123      |
      | First name                      | User              |
      | Surname                         | One               |
      | Email address                   | a1@example.com    |
      | Text Field                      | testing123        |
      | Menu Field                      | CCC               |
    And I press "Create user"
    Then the following should exist in the "system_browse_users" table:
      | Username | User's Email   |
      | user1    | a1@example.com |
    When I press "Add a new user"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Username                        | user2             |
      | New password                    | A.New.Pw.123      |
      | First name                      | User              |
      | Surname                         | Two               |
      | Email address                   | a2@example.com    |
      | Text Field                      | testing456        |
      | Menu Field                      | AAA               |
    And I press "Create user"
    Then the following should exist in the "system_browse_users" table:
      | Username | User's Email   |
      | user1    | a1@example.com |
      | user2    | a2@example.com |
    And I follow "User One"
    And I should see "testing123"
    And I should see "CCC"
