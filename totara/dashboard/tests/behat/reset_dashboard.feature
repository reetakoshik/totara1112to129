@totara @totara_dashboard @javascript
Feature: Reset dashboard layout for all users
  As an admin
  I must be able to reset the dashboard layout for all users

  Background:
    Given I am on a totara site
    And the following "users" exist:
        | username | firstname | lastname | email                     |
        | learner1 | Bob1      | Learner1 | bob1.learner1@example.com |
        | learner2 | Sam2      | Learner2 | sam2.learner2@example.com |

    When I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    Then "Recent Learning" "block" should not exist
    When I press "Customise this page"
    And I add the "Recent Learning" block
    And I press "Stop customising this page"
    Then "Recent Learning" "block" should exist
    And I log out

    When I log in as "learner2"
    And I click on "Dashboard" in the totara menu
    Then "Recent Learning" "block" should not exist
    When I press "Customise this page"
    And I add the "Recent Learning" block
    And I press "Stop customising this page"
    Then "Recent Learning" "block" should exist
    And I log out

  Scenario: Reset dashboard for all users
    When I log in as "admin"
    And I click on "Dashboard" in the totara menu
    And I press "Manage dashboards"
    And I follow "My Learning"
    And I press "Reset dashboard for all users"
    And I press "Continue"
    Then I should see "Dashboard reset successful"
    And I log out

    When I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    Then "Recent Learning" "block" should not exist
    And I log out

    When I log in as "learner2"
    And I click on "Dashboard" in the totara menu
    Then "Recent Learning" "block" should not exist
    And I log out

  Scenario: Reset dashboard when deleted users exist
    When I log in as "admin"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    Then I should see "Bob1 Learner1"
    And I should see "Sam2 Learner2"

    When I follow "Delete Bob1 Learner1"
    And I press "Delete"
    Then I should not see "Bob1 Learner1"

    When I click on "Dashboard" in the totara menu
    And I press "Manage dashboards"
    And I follow "My Learning"
    And I press "Reset dashboard for all users"
    And I press "Continue"
    Then I should see "Dashboard reset successful"
    And I log out

    When I log in as "learner2"
    And I click on "Dashboard" in the totara menu
    Then "Recent Learning" "block" should not exist
    And I log out

  Scenario: Dashboard is reset for undeleted user
    When I log in as "admin"
    And I navigate to "Manage authentication" node in "Site administration > Plugins > Authentication"
    And I set the following fields to these values:
      | User deletion | Keep username, email and ID number (legacy) |
    And I press "Save changes"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    Then I should see "Bob1 Learner1"
    And I should see "Sam2 Learner2"

    When I follow "Delete Bob1 Learner1"
    And I press "Delete"
    And I navigate to "Deleted user accounts" node in "Site administration > User data management"
    Then I should see "Bob1 Learner1"
    And "Undelete Bob1 Learner1" "link" should exist

    When I click on "Dashboard" in the totara menu
    And I press "Manage dashboards"
    And I follow "My Learning"
    And I press "Reset dashboard for all users"
    And I press "Continue"
    Then I should see "Dashboard reset successful"
    And I log out

    When I log in as "learner2"
    And I click on "Dashboard" in the totara menu
    Then "Recent Learning" "block" should not exist
    And I log out

    When I log in as "admin"
    And I navigate to "Deleted user accounts" node in "Site administration > User data management"
    And I follow "Undelete Bob1 Learner1"
    And I press "Undelete"
    Then I should see "Undeleted Bob1 Learner1"
    And I log out

    When I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    Then "Recent Learning" "block" should not exist
    And I log out

