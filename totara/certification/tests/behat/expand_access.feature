@totara @totara_certification
Feature: Users can expand the certification info
  In order to expand certification info
  As a user
  I need to login if forcelogin enabled

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And I log in as "admin"
    And I set the following administration settings values:
      | catalogtype | enhanced |
    And I click on "Certifications" in the totara menu
    And I press "Create Certification"
    And I press "Save changes"
    And I set the following administration settings values:
      | Guest login button | Show     |
      | enableprograms     | Disable  |
    And I log out

  @javascript
  Scenario: Allow not logged in users to expand certification when forcelogin disabled
    Given I log in as "admin"
    And I set the following administration settings values:
      | forcelogin | 0 |
    And I log out
    And I click on "Certifications" in the totara menu
    And I click on ".rb-display-expand" "css_element"
    Then I should see "View certification"

  @javascript
  Scenario: Allow guest account to expand certification when forcelogin enabled
    Given I click on "#guestlogin input[type=submit]" "css_element"
    And I click on "Certifications" in the totara menu
    And I click on ".rb-display-expand" "css_element"
    Then I should see "View certification"

  @javascript
  Scenario: Allow user to expand certification when forcelogin enabled
    Given I click on "#guestlogin input[type=submit]" "css_element"
    And I log in as "student1"
    And I click on "Certifications" in the totara menu
    And I click on ".rb-display-expand" "css_element"
    Then I should see "View certification"
