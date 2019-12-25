@totara @totara_core @totara_core_menu
Feature: Test visibility control of menu items
  In order to test the visiblity controls for menu items
  I must log in as admin and configure an advanced menu
  Then log in as various users to ensure expected visibility of the menu items

  @javascript
  Scenario: access controls cant be set on always shown menu items
    Given I am on a totara site
    And I log in as "admin"
    And I set the following administration settings values:
      | Guest login button | Show |
    And I navigate to "Main menu" node in "Site administration > Appearance"
    And I press "Add new menu item"
    And I set the following fields to these values:
     | Menu title | Test one |
     | Menu default url address | http://totaralms.com |
    When I press "Add new menu item"
    And I click on "Edit" "link" in the "Test one" "table_row"
    Then I should see "Edit menu item"
    And I should see the "Access" tab is disabled

  @javascript
  Scenario: access controls can be set on always items set to use custom access rules
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Main menu" node in "Site administration > Appearance"
    And I press "Add new menu item"
    And I set the following fields to these values:
      | Menu title | Test one |
      | Menu default url address | http://totaralms.com |
      | Visibility               | Use custom access rules |
    And I press "Add new menu item"
    And I should see "Edit menu item"
    And I click on "Access" "link"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Restrict access by role | 1   |
      | Role aggregation        | Any |
      | Context                 | site |
      | Authenticated user      | 1 |
    When I press "Save changes"
    Then I should see "Test one" in the totara menu

  @javascript
  Scenario: role aggregation works as expected for menu item visibility
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Main menu" node in "Site administration > Appearance"
    And I press "Add new menu item"
    And I set the following fields to these values:
      | Menu title | Test one |
      | Menu default url address | http://totaralms.com |
      | Visibility               | Use custom access rules |
    And I press "Add new menu item"
    And I should see "Edit menu item"
    And I click on "Access" "link"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Restrict access by role         | 1   |
      | Role aggregation                | All |
      | Authenticated user              | 1   |
      | Authenticated user on frontpage | 1   |
      | Context                         | any |
    When I press "Save changes"
    And I should see "Test one" in the totara menu

  @javascript
  Scenario: roles see only the menu items they are configured to see
    Given I am on a totara site
    And the following "users" exist:
      | username |
      | user1    |
      | user2    |
      | user3    |
    And the following "courses" exist:
      | fullname | shortname | summary | format |
      | Course 1 | C1 | <p>Course summary</p> | topics |
    And the following "course enrolments" exist:
      | user | course | role |
      | user1 | C1 | student |
      | user2 | C1 | editingteacher |
      | user3 | C1 | manager |
    When I log in as "admin"
    And I set the following administration settings values:
      | Guest login button | Show |
    And I create the following totara menu items:
      | Menu title          | Visibility              | Menu default url address | Restrict access by role | Context                            | Learner | Editing Trainer | Site Manager | Guest |
      | Students only       | Use custom access rules | http://totaralms.com     | 1                       | Users may have role in any context | 1       |                 |              |       |
      | Teachers only       | Use custom access rules | http://totaralms.com     | 1                       | Users may have role in any context |         | 1               |              |       |
      | Managers only       | Use custom access rules | http://totaralms.com     | 1                       | Users may have role in any context |         |                 | 1            |       |
      | Students + Teachers | Use custom access rules | http://totaralms.com     | 1                       | Users may have role in any context | 1       | 1               |              |       |
      | Teachers + Managers | Use custom access rules | http://totaralms.com     | 1                       | Users may have role in any context |         | 1               | 1            |       |
      | Managers + Students | Use custom access rules | http://totaralms.com     | 1                       | Users may have role in any context | 1       |                 | 1            |       |
      | Guest only          | Use custom access rules | http://totaralms.com     | 1                       | Users may have role in any context |         |                 |              | 1     |
      | Everyone            | Use custom access rules | http://totaralms.com     | 1                       | Users may have role in any context | 1       | 1               | 1            | 1     |
      | No one              | Use custom access rules | http://totaralms.com     | 1                       | Users may have role in any context |         |                 |              |       |
    And I log out
    # User 1 - the learner.
    Then I log in as "user1"
    And I should see "Students only" in the totara menu
    And I should see "Students + Teachers" in the totara menu
    And I should see "Managers + Students" in the totara menu
    And I should see "Everyone" in the totara menu
    And I log out
    # User 2 - the trainer
    And I log in as "user2"
    And I should see "Teachers only" in the totara menu
    And I should see "Students + Teachers" in the totara menu
    And I should see "Teachers + Managers" in the totara menu
    And I should see "Everyone" in the totara menu
    And I log out
    # User 3 - the manager
    And I log in as "user3"
    And I should see "Managers only" in the totara menu
    And I should see "Managers + Students" in the totara menu
    And I should see "Teachers + Managers" in the totara menu
    And I should see "Everyone" in the totara menu
    And I log out
    # Guest user
    And I log in as "guest"
    And I should see "Guest only" in the totara menu
    And I should see "Everyone" in the totara menu

    @javascript
    Scenario: audience members see only the menu items they are configured to see
      Given I am on a totara site
      And the following "users" exist:
        | username | firstname | lastname | email               |
        | user1    | User      | One      | one@totaralms.com   |
        | user2    | User      | Two      | two@totaralms.com   |
        | user3    | User      | Three    | three@totaralms.com |
      And the following "cohorts" exist:
        | name     | idnumber |
        | Cohort 1 | CH1      |
        | Cohort 2 | CH2      |
      And the following "cohort members" exist:
        | user  | cohort |
        | user2 | CH1    |
        | user3 | CH2    |
      When I log in as "admin"
      And I create the following totara menu items:
        | Menu title          | Visibility              | Menu default url address | Restrict access by audience |
        | CH1 members only    | Use custom access rules | http://totaralms.com     | 1                           |
        | CH2 members only    | Use custom access rules | http://totaralms.com     | 1                           |
      And I click on "Main menu" "link"
      And I edit "CH1 members only" totara menu item
      And I click on "Access" "link"
      And I expand all fieldsets
      And I click on "Restrict access by audience" "checkbox"
      And I press "Add audiences"
      And I click on "Cohort 1" "link"
      And I press "OK"
      And I wait "1" seconds
      And I press "Save changes"
      And I navigate to "Main menu" node in "Site administration > Appearance"
      And I edit "CH2 members only" totara menu item
      And I click on "Access" "link"
      And I expand all fieldsets
      And I click on "Restrict access by audience" "checkbox"
      And I press "Add audiences"
      And I click on "Cohort 2" "link"
      And I press "OK"
      And I wait "1" seconds
      And I press "Save changes"
      And I log out
      Then I log in as "user1"
      And I should not see "CH1 members only" in the totara menu
      And I should not see "CH2 members only" in the totara menu
      And I log out
      And I log in as "user2"
      And I should see "CH1 members only" in the totara menu
      And I should not see "CH2 members only" in the totara menu
      And I log out
      And I log in as "user3"
      And I should not see "CH1 members only" in the totara menu
      And I should see "CH2 members only" in the totara menu
      And I log out
