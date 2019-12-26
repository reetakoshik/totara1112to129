@totara @profile_fields
Feature: Users can be forced to fill new required custom profile fields

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname    | lastname | email               |
      | user1    | John         | Smith    | user1@example.com   |
      | user2    | Jan          | Kovar    | user2@example.com   |
      | user3    | Karel        | Omacka   | user3@example.com   |
    And the following "roles" exist:
      | name               | shortname     |
      | No profile edit    | noprofileedit |
    And the following "permission overrides" exist:
      | capability                 | permission | role          | contextlevel | reference |
      | moodle/user:editownprofile | Prohibit   | noprofileedit | System       |           |
    And the following "system role assigns" exist:
      | user        | role          |
      | user3       | noprofileedit |
    And the following "custom profile fields" exist in "totara_core" plugin:
      | datatype | shortname  | name                  | required |
      | text     | reqtxt     | Required profile text | 1        |

  Scenario: Admin is not redirected to edit profile when new required custom profile field present
    Given I log in as "admin"
    Then I should not see "Edit profile"
    And I should see "Acceptance test site"

  Scenario: Users are redirectred to profile if new required custom profile field present
    When I log in as "user1"
    Then I should see "Required profile text"
    And I should see "There are required fields in this form marked"

    When I set the field "Required profile text" to "something"
    And I press "Update profile"
    Then I should see "Change password"

  Scenario: Users without ability to edit profile are not redirected to edit profile when new required custom profile field present
    When I log in as "user3"
    Then I should see "You do not have any current learning."

  @javascript
  Scenario: Loginas user is not redirected to edit profile when new required custom profile field present
    Given I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Jan Kovar"
    When I follow "Log in as"
    And I should see "You are logged in as Jan Kovar"
    And I press "Continue"
    Then I should see "You do not have any current learning."
