@core @core_user
Feature: Update user forum preferences
  Any user
  Can update his forum preferences regardless of uppercase letters
  in his username or not

  Background:
    Given I am on a totara site
    And the following "users" exist:
    | username | firstname | lastname | email          |
    | UserOne  | User      | One      | u1@example.com |
    | user2    | User      | Two      | u2@example.com |


  Scenario: User with uppercase letters in his username can update his forum preferences
    # We can't login directly with username containing uppercase
    Given I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    Then I should see "User One"
    And I should see "User Two"

    When I follow "User One"
    And I follow "Log in as"
    And I click on "Continue" "button"
    Then I should see "You are logged in as User One"

    When I follow "Preferences" in the user menu
    And I follow "Forum preferences"
    And I set the field "Forum tracking" to "Yes: highlight new posts for me"
    And I press "Save changes"
    # Should be back on the Preferences page - checking for text not on the Forum prefences page
    Then I should see "Edit profile"
    And I log out

  Scenario: User without uppercase letters in his username can update his forum preferences
    Given I log in as "user2"
    And I follow "Preferences" in the user menu
    And I follow "Forum preferences"
    And I set the field "Forum tracking" to "Yes: highlight new posts for me"
    And I press "Save changes"
    # Should be back on the Preferences page - checking for text not on the Forum prefences page
    Then I should see "Edit profile"
    And I log out
