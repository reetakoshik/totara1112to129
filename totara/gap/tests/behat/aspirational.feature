@javascript @totara @totara_gap
Feature: Add aspirational position to user profile
  In order to manage aspirational positions
  As a manager
  I need to add, edit, and remove aspirational position to user profile

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Merry1    | Manager1 | manager1@example.com |
      | student1 | Sam1      | Student1 | student1@example.com |
    And the following "role assigns" exist:
      | user     | role    | contextlevel | reference |
      | manager1 | manager | System       |           |
    And the following job assignments exist:
      | user     | manager  |
      | student1 | manager1 |
    And the following "position" frameworks exist:
      | fullname               | idnumber | description |
      | Competency Framework 1 | FW1      | Description |
    And the following "position" hierarchy exists:
      | framework | fullname     | idnumber |
      | FW1       | Position1    | P1       |

  Scenario: Manage aspirational position in user profile
    Given I log in as "manager1"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "Sam1 Student1" "link"
    And I click on "Edit profile" "link" in the "div.userprofile" "css_element"
    And I should see "Aspirational position"
    And I expand all fieldsets
    And I press "Choose position"
    And I click on "Position1" "link"
    And I click on "OK" "button" in the "div.ui-dialog-buttonset" "css_element"
    And I should see "Position1"
    And I press "Update profile"

    And I click on "Sam1 Student1" "link"
    And I click on "Edit profile" "link" in the "div.userprofile" "css_element"
    And I expand all fieldsets
    And I should see "Position1"
    And I click on "Delete" "link" in the "#aspirationalpositiontitle" "css_element"
    And I should not see "Position1"
    And I press "Update profile"

    And I click on "Sam1 Student1" "link"
    And I click on "Edit profile" "link" in the "div.userprofile" "css_element"
    And I expand all fieldsets
    Then I should not see "Position1"

  Scenario: User profile form works when user cannot manage aspirational positions.
    Given I log in as "student1"
    And I follow "Profile" in the user menu
    And I click on "Edit profile" "link" in the "div.userprofile" "css_element"
    And I expand all fieldsets
    And I should not see "Aspirational position"
    And I should not see "User details"
    And I press "Update profile"
    And I should see "User details"