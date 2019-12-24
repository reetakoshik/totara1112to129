@totara @totara_program
Feature: Users can expand the program info
  In order to expand program info
  As a user
  I need to login if forcelogin enabled

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And the following "programs" exist in "totara_program" plugin:
      | fullname                 | shortname |
      | Visibility Program Tests | vistest   |

  @javascript
  Scenario: Allow not logged in users to expand program when forcelogin disabled
    Given I log in as "admin"
    Given I set the following administration settings values:
      | forcelogin | 0 |
    And I log out
    And I click on "Programs" in the totara menu
    And I click on ".rb-display-expand" "css_element"
    Then I should see "View program"

  @javascript
  Scenario: Allow guest account to expand program when forcelogin enabled
    Given I log in as "admin"
    And I set the following administration settings values:
      | Guest login button | Show |
    And I log out
    And I click on "#guestlogin input[type=submit]" "css_element"
    And I click on "Programs" in the totara menu
    And I click on ".rb-display-expand" "css_element"
    Then I should see "View program"

  @javascript
  Scenario: Allow user to expand program when forcelogin enabled
    Given I log in as "admin"
    And I set the following administration settings values:
      | Guest login button | Show |
    And I log out
    And I click on "#guestlogin input[type=submit]" "css_element"
    And I log in as "student1"
    And I click on "Programs" in the totara menu
    And I click on ".rb-display-expand" "css_element"
    Then I should see "View program"
