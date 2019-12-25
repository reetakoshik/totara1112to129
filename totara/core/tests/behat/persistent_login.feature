@totara @totara_core @javascript
Feature: Test persistent login feature

  Scenario: Test persistent login setting is disabled by default
    Given I am on a totara site

    When I use magic for persistent login to open the login page
    And I should see "You are not logged in."
    And I set the field "Username" to "admin"
    And I set the field "Password" to "admin"
    And I set the field "Remember username" to "1"
    And I press "Log in"
    And I should see "Admin User"
    And I use magic for persistent login to simulate session timeout
    And I use magic for persistent login to open the login page
    Then I should see "You are not logged in."

  Scenario: Test persistent login setting can be enabled and works from login page
    Given I am on a totara site
    And I log in as "admin"
    And I set the following administration settings values:
      | Persistent login | 1 |
    And I log out

    When I use magic for persistent login to open the login page
    And I should see "You are not logged in."
    And I set the field "Username" to "admin"
    And I set the field "Password" to "admin"
    And I press "Log in"
    And I should see "Admin User"
    And I use magic for persistent login to simulate session timeout
    And I use magic for persistent login to open the login page
    Then I should see "You are not logged in."

    When I use magic for persistent login to open the login page
    And I should see "You are not logged in."
    And I set the field "Username" to "admin"
    And I set the field "Password" to "admin"
    And I set the field "Remember my login" to "1"
    And I press "Log in"
    And I should see "Admin User"
    And I use magic for persistent login to simulate session timeout
    And I am on homepage
    Then I should see "Admin User"

    When I use magic for persistent login to simulate session timeout
    And I use magic for persistent login to open the login page
    And I should see "You are already logged in as Admin User, you need to log out before logging in as different user."
    And I press "Log out"
    Then I should see "You are not logged in."

  Scenario: Test persistent login setting can be enabled and works from login block
    Given I am on a totara site
    And I log in as "admin"
    And I set the following administration settings values:
      | Persistent login | 1 |
      | forcelogin       | 0 |
    And I am on site homepage
    And I navigate to "Turn editing on" node in "Front page settings"
    And I add the "Login" block
    And I log out

    When I am on site homepage
    And I should see "You are not logged in."
    And I set the field "Username" to "admin"
    And I set the field "Password" to "admin"
    And I press "Log in"
    And I should see "Admin User"
    And I use magic for persistent login to simulate session timeout
    And I am on site homepage
    Then I should see "You are not logged in."

    When I am on site homepage
    And I should see "You are not logged in."
    And I set the field "Username" to "admin"
    And I set the field "Password" to "admin"
    And I set the field "Remember my login" to "1"
    And I press "Log in"
    And I should see "Admin User"
    And I use magic for persistent login to simulate session timeout
    And I am on homepage
    Then I should see "Admin User"

    When I use magic for persistent login to simulate session timeout
    And I use magic for persistent login to open the login page
    And I should see "You are already logged in as Admin User, you need to log out before logging in as different user."
    And I press "Log out"
    Then I should see "You are not logged in."

  Scenario: Test persistent login cookie is deleted during login-as
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email          |
      | user1    | user      | one      | u1@example.com |
    And I log in as "admin"
    And I set the following administration settings values:
      | Persistent login | 1 |
    And I log out
    And I use magic for persistent login to open the login page
    And I should see "You are not logged in."
    And I set the field "Username" to "admin"
    And I set the field "Password" to "admin"
    And I set the field "Remember my login" to "1"
    And I press "Log in"
    And I should see "Admin User"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "user one"
    And I follow "Log in as"
    And I should see "You are logged in as user one"
    When I use magic for persistent login to simulate session timeout
    And I use magic for persistent login to open the login page
    Then I should see "You are not logged in."

  Scenario: Test persistent login cookie is deleted when changing own password
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email          |
      | user1    | Prvni     | Uzivatel | u1@example.com |
    And I log in as "admin"
    And I set the following administration settings values:
      | Persistent login | 1 |
    And I log out
    And I use magic for persistent login to open the login page
    And I should see "You are not logged in."
    And I set the field "Username" to "user1"
    And I set the field "Password" to "user1"
    And I set the field "Remember my login" to "1"
    And I press "Log in"
    And I should see "Prvni Uzivatel"
    And I use magic for persistent login to purge cookies
    And I use magic for persistent login to open the login page
    And I should see "You are not logged in."
    And I set the field "Username" to "user1"
    And I set the field "Password" to "user1"
    And I set the field "Remember my login" to "1"
    And I press "Log in"
    And I should see "Prvni Uzivatel"
    And I follow "Profile" in the user menu
    And I follow "Browser sessions"
    And I should see "Yes" in the "Current session" "table_row"
    And I should see "Yes" in the "now" "table_row"
    And I follow "Preferences" in the user menu
    And I follow "Change password"
    And I set the field "Current password" to "user1"
    And I set the field "New password" to "Userpass-1"
    And I set the field "New password (again)" to "Userpass-1"
    When I press "Save changes"
    And I follow "Profile" in the user menu
    And I follow "Browser sessions"
    Then I should see "No" in the "Current session" "table_row"
    And I should see "No" in the "now" "table_row"

  Scenario: Test persistent login cookie is deleted when setting new password
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email          |
      | user1    | Prvni     | Uzivatel | u1@example.com |
    And I log in as "admin"
    And I set the following administration settings values:
      | Persistent login | 1 |
    And I log out
    And I use magic for persistent login to open the login page
    And I should see "You are not logged in."
    And I set the field "Username" to "user1"
    And I set the field "Password" to "user1"
    And I set the field "Remember my login" to "1"
    And I press "Log in"
    And I should see "Prvni Uzivatel"
    And I use magic for persistent login to purge cookies
    And I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Prvni Uzivatel"
    And I follow "Edit profile"
    And I set the field "New password" to "Userpass-1"
    When I press "Update profile"
    And I log out
    And I use magic for persistent login to open the login page
    And I should see "You are not logged in."
    And I set the field "Username" to "user1"
    And I set the field "Password" to "Userpass-1"
    And I set the field "Remember my login" to "1"
    And I press "Log in"
    And I should see "Prvni Uzivatel"
    And I follow "Profile" in the user menu
    And I follow "Browser sessions"
    Then I should see "Yes" in the "Current session" "table_row"
    And I should see "No" in the "now" "table_row"

  Scenario: Test user sessions report without persistent login
    Given I am on a totara site
    And I log in as "admin"
    And I use magic for persistent login to purge cookies
    And I log in as "admin"
    And I follow "Profile" in the user menu
    When I follow "Browser sessions"
    Then I should see "Current session"
    And I should see "Log out"
    And I should not see "Persistent login"

  Scenario: Test user sessions report with persistent login
    Given I am on a totara site
    And I log in as "admin"
    And I set the following administration settings values:
      | Persistent login | 1 |
    And I log out
    And I use magic for persistent login to open the login page
    And I set the field "Username" to "admin"
    And I set the field "Password" to "admin"
    And I set the field "Remember my login" to "0"
    And I press "Log in"
    And I use magic for persistent login to purge cookies
    And I use magic for persistent login to open the login page
    And I set the field "Username" to "admin"
    And I set the field "Password" to "admin"
    And I set the field "Remember my login" to "1"
    And I press "Log in"
    And I use magic for persistent login to simulate session timeout
    And I use magic for persistent login to purge cookies
    And I use magic for persistent login to open the login page
    And I set the field "Username" to "admin"
    And I set the field "Password" to "admin"
    And I set the field "Remember my login" to "1"
    And I press "Log in"
    And I follow "Profile" in the user menu

    When I follow "Browser sessions"
    Then I should see "Persistent login"
    And I should see "No" in the "now" "table_row"
    And I should see "Log out" in the "now" "table_row"
    And I should see "Yes" in the "Current session" "table_row"
    And I should see "Yes" in the "10 days ago" "table_row"
    And I should see "Log out" in the "10 days ago" "table_row"

    When I click on "Log out" "link" in the "now" "table_row"
    Then I should not see "now"

    When I click on "Log out" "link" in the "10 days ago" "table_row"
    Then I should not see "10 days ago"

  Scenario: Test persistent login is considered a risk in secureity overview report
    Given I am on a totara site
    And I log in as "admin"

    When I navigate to "Security overview" node in "Site administration > Security"
    Then I should see "OK" in the "Persistent login" "table_row"

    When I set the following administration settings values:
      | Persistent login | 1 |
    And I navigate to "Security overview" node in "Site administration > Security"
    Then I should see "Warning" in the "Persistent login" "table_row"
