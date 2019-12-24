@core @core_user @javascript
Feature: Basic editing of users
  In order to use the user/edit.php page comfortably
  As a user
  It needs to be redirecting back to the original page

  Scenario: Edit own user info from profile
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | First     | User     | user1@example.com |
    And I log in as "user1"
    And I follow "Profile" in the user menu
    And I should see "User details"
    And I follow "Edit profile"
    And I set the following fields to these values:
      | First name | Prvni    |
      | Surname    | Uzivatel |
    When I press "Update profile"
    Then I should see "Prvni Uzivatel"
    And I should see "User details"

  Scenario: Cancel editing of  own user info from profile
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | First     | User     | user1@example.com |
    And I log in as "user1"
    And I follow "Profile" in the user menu
    And I should see "User details"
    And I follow "Edit profile"
    And I set the following fields to these values:
      | First name | Prvni    |
      | Surname    | Uzivatel |
    When I press "Cancel"
    Then I should see "First User"
    And I should see "User details"

  Scenario: Edit own user info from preferences
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | First     | User     | user1@example.com |
    And I log in as "user1"
    And I follow "Preferences" in the user menu
    And I should see "Notification preferences"
    And I follow "Edit profile"
    And I set the following fields to these values:
      | First name | Prvni    |
      | Surname    | Uzivatel |
    When I press "Update profile"
    Then I should see "Prvni Uzivatel"
    And I should see "Notification preferences"

  Scenario: Cancel editing of  own user info from preferences
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | First     | User     | user1@example.com |
    And I log in as "user1"
    And I follow "Preferences" in the user menu
    And I should see "Notification preferences"
    And I follow "Edit profile"
    And I set the following fields to these values:
      | First name | Prvni    |
      | Surname    | Uzivatel |
    When I press "Cancel"
    Then I should see "First User"
    And I should see "Notification preferences"
