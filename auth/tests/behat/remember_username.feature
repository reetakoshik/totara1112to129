@core @core_auth
Feature: Remember username during log in
  To make logging in easier
  While I log in to Totara
  My username can be saved

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             | password |
      | user1    | user      | one      | user1@example.com | p@ssw0rd |
      | user2    | user      | two      | user2@example.com | p4ssw@rd |

  @javascript
  Scenario: When someone else has made remember username cookie, I should still only be able to log in as myself
    When I follow "Log in"
    And I set the following fields to these values:
      | Username          | user1    |
      | Password          | p@ssw0rd |
      | Remember username | 1        |
    And I press "Log in"
    Then I should not see "Log in"
    When I log out
    Then the following fields match these values:
      | Username          | user1 |
      | Password          |       |
      | Remember username | 1     |
    When I set the field "password" to "p4ssw@rd"
    Then I press "Log in"
    Then I should see "Invalid login, please try again"
    When I set the following fields to these values:
      | Username          | user2    |
      | Password          | p4ssw@rd |
      | Remember username | 1        |
    And I press "Log in"
    Then I should not see "Log in"

  @javascript
  Scenario: When someone else has made remember username cookie, I should be able to overwrite with my own
    When I follow "Log in"
    And I set the following fields to these values:
      | Username          | user1    |
      | Password          | p@ssw0rd |
      | Remember username | 1        |
    And I press "Log in"
    Then I should not see "Log in"
    When I log out
    Then the following fields match these values:
      | Username          | user1 |
      | Password          |       |
      | Remember username | 1     |
    When I set the following fields to these values:
      | Username          | user2    |
      | Password          | p4ssw@rd |
      | Remember username | 1        |
    And I press "Log in"
    Then I should not see "Log in"
    When I log out
    Then the following fields match these values:
      | Username          | user2 |
      | Password          |       |
      | Remember username | 1     |

  @javascript
  Scenario: When someone else has made remember username cookie, I should be able to delete it by unchecking and logging in
    When I follow "Log in"
    And I set the following fields to these values:
      | Username          | user1    |
      | Password          | p@ssw0rd |
      | Remember username | 1        |
    And I press "Log in"
    Then I should not see "Log in"
    When I log out
    Then the following fields match these values:
      | Username          | user1 |
      | Password          |       |
      | Remember username | 1     |
    When I set the following fields to these values:
      | Username          | user2    |
      | Password          | p4ssw@rd |
      | Remember username | 0        |
    And I press "Log in"
    Then I should not see "Log in"
    When I log out
    Then the following fields match these values:
      | Username          | |
      | Password          | |
      | Remember username | |

  @javascript
  Scenario: When I enter my username incorrectly, it should be remembered on redirect to the page only, but not in cookie
    When I follow "Log in"
    And I set the following fields to these values:
      | Username          | user1    |
      | Password          | p@ssw0rd |
      | Remember username | 1        |
    And I press "Log in"
    Then I should not see "Log in"
    When I log out
    Then the following fields match these values:
      | Username          | user1 |
      | Password          |       |
      | Remember username | 1     |
    When I set the following fields to these values:
      | Username          | user2    |
      | Password          | wrongpwd |
      | Remember username | 1        |
    And I press "Log in"
    Then I should see "Invalid login, please try again"
    And the following fields match these values:
      | Username          | user2 |
      | Password          |       |
      | Remember username | 1     |
    When I reload the page
    Then the following fields match these values:
      | Username          | user1 |
      | Password          |       |
      | Remember username | 1     |

  @javascript
  Scenario: When I enter my username incorrectly and uncheck remember username, an incorrect log in should clear the cookie
    When I follow "Log in"
    And I set the following fields to these values:
      | Username          | user1    |
      | Password          | p@ssw0rd |
      | Remember username | 1        |
    And I press "Log in"
    Then I should not see "Log in"
    When I log out
    Then the following fields match these values:
      | Username          | user1 |
      | Password          |       |
      | Remember username | 1     |
    When I set the following fields to these values:
      | Username          | user2    |
      | Password          | wrongpwd |
      | Remember username | 0        |
    And I press "Log in"
    Then I should see "Invalid login, please try again"
    And the following fields match these values:
      | Username          | user2 |
      | Password          |       |
      | Remember username | 0     |
    When I reload the page
    Then the following fields match these values:
      | Username          |       |
      | Password          |       |
      | Remember username |       |

  @javascript
  Scenario: When the rememberusername config setting is set to always remember, an incorrect log in will not clear the cookie
    When the following config values are set as admin:
      | rememberusername | 1 |
    And I follow "Log in"
    Then I should not see "Remember username"
    And I set the following fields to these values:
      | Username          | user1    |
      | Password          | p@ssw0rd |
    And I press "Log in"
    Then I should not see "Log in"
    When I log out
    Then the following fields match these values:
      | Username          | user1 |
      | Password          |       |
    And I should not see "Remember username"
    When I set the following fields to these values:
      | Username          | user2    |
      | Password          | wrongpwd |
    And I press "Log in"
    Then I should see "Invalid login, please try again"
    And I should not see "Remember username"
    And the following fields match these values:
      | Username          | user2 |
      | Password          |       |
    When I reload the page
    Then the following fields match these values:
      | Username          | user1 |
      | Password          |       |
    And I should not see "Remember username"

  @javascript
  Scenario: When the rememberusername config setting is off, no username cookie is created
    When the following config values are set as admin:
      | rememberusername | 0 |
    And I follow "Log in"
    Then I should not see "Remember username"
    When I set the following fields to these values:
      | Username          | user1    |
      | Password          | p@ssw0rd |
    And I press "Log in"
    Then I should not see "Log in"
    When I log out
    Then the following fields match these values:
      | Username          |       |
      | Password          |       |
    And I should not see "Remember username"
    When I set the following fields to these values:
      | Username          | user2    |
      | Password          | wrongpwd |
    And I press "Log in"
    Then I should see "Invalid login, please try again"
    And the following fields match these values:
      | Username          | user2 |
      | Password          |       |
    And I should not see "Remember username"
    When I reload the page
    Then the following fields match these values:
      | Username          |       |
      | Password          |       |
    And I should not see "Remember username"
