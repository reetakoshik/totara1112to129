@core @totara
Feature: User can change their password
  In order to test that a user can change their password
  I must log in as the user
  Change my password
  And Login again

  Scenario: A user can change their own password
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email |
      | learner | learner | 1 | learner@example.com |
    And I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "learner 1" "link"
    And I click on "Edit profile" "link"
    And I set the following fields to these values:
      | New password | Pass-w0rd1 |
    And I press "Update profile"
    Then I should see "User details"

    When I log out
    Then I should see "Log in"
    And I should not see the "Navigation" block

    When I set the following fields to these values:
      | Username | learner |
      | Password | Pass-w0rd1 |
    And I press "Log in"
    And I follow "Profile" in the user menu
    Then I should see "learner 1"
    And I should see the "Navigation" block

    When I click on "Preferences" "link" in the ".userprofile" "css_element"
    Then I should see "Preferences"
    And I should see "User account"
    And I should see the "Navigation" block

    When I click on "Change password" "link"
    Then I should see "Change password"

    When I set the following fields to these values:
    | Current password | Pass-w0rd1 |
    | New password     | Pass-w0rd2 |
    | New password (again) | Pass-w0rd2 |
    And I press "Save changes"
    Then I should see "Password has been changed"

    When I press "Continue"
    And I log out
    Then I should see "Log in"
    And I should not see the "Navigation" block

    When I set the following fields to these values:
    | Username | learner |
    | Password | Pass-w0rd2 |
    And I press "Log in"
    Then I should not see "Invalid login, please try again"
    And I should see "learner 1" in the ".usertext" "css_element"

  Scenario: A user can change their password when forced to
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email | password |
      | learner | learner | 1 | learner@example.com | monkey |

    When I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "learner 1" "link"
    And I click on "Edit profile" "link"
    And I set the following fields to these values:
     | Force password change | 1 |
    And I press "Update profile"
    Then I should see "User details"

    When I log out
    Then I should see "Log in"
    And I should not see the "Navigation" block

    When I set the following fields to these values:
      | Username | learner |
      | Password | monkey |
    And I press "Log in"
    Then I should see "Change password"

    When I set the following fields to these values:
      | Current password | monkey |
      | New password     | Pass-w0rd1 |
      | New password (again) | Pass-w0rd1 |
    And I press "Save changes"
    Then I should see "Password has been changed"

    When I press "Continue"
    And I log out
    Then I should see "Log in"
    And I should not see the "Navigation" block

    When I set the following fields to these values:
      | Username | learner |
      | Password | Pass-w0rd1 |
    And I press "Log in"
    Then I should not see "Invalid login, please try again"
    And I should see "learner 1" in the ".usertext" "css_element"