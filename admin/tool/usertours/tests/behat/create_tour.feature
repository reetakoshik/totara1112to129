@tool @tool_usertours
Feature: Add a new user tour
  In order to help users learn of new features
  As an administrator
  I need to create a user tour

  @javascript
  Scenario: Add a new tour
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |
    And I log in as "admin"
    And I add a new user tour with:
      | Name                | First tour |
      | Description         | My first tour |
      | Apply to URL match  | /totara/dashboard/% |
      | Tour is enabled     | 1 |
    And I add steps to the "First tour" tour:
      | targettype                  | Title             | Content |
      | Display in middle of page   | Welcome           | Welcome to your personal learning space. We'd like to give you a quick tour to show you some of the areas you may find helpful |
    And I add steps to the "First tour" tour:
      | targettype                  | targetvalue_block | Title             | Content |
      | Block                       | Current Learning  | Current Learning  | This area shows you what's happening in your courses |
      | Block                       | Upcoming events   | Upcoming events   | Here is a list of upcoming events in your calendar   |
    And I add steps to the "First tour" tour:
      | targettype                  | targetvalue_selector | Title             | Content |
      | Selector                    | .usermenu            | User menu         | This is your personal user menu. You'll find your personal preferences and your user profile here. |
    When I click on "Dashboard" in the totara menu
    Then I should see "Welcome to your personal learning space. We'd like to give you a quick tour to show you some of the areas you may find helpful"
    And I press "Next"
    And I should see "This area shows you what's happening in your courses"
    And I should not see "Here is a list of upcoming events in your calendar"
    And I press "Next"
    And I should see "Here is a list of upcoming events in your calendar"
    And I should not see "This area shows you what's happening in your courses"
    And I press "Prev"
    And I should not see "Here is a list of upcoming events in your calendar"
    And I should see "This area shows you what's happening in your courses"
    And I press "End tour"
    And I should not see "This area shows you what's happening in your courses"
    When I click on "Dashboard" in the totara menu
    And I should not see "Welcome to your personal learning space. We'd like to give you a quick tour to show you some of the areas you may find helpful"
    And I should not see "This area shows you what's happening in your courses"
    And I follow "Reset user tour on this page"
    And I should see "Welcome to your personal learning space. We'd like to give you a quick tour to show you some of the areas you may find helpful"

  @javascript
  Scenario: A hidden tour should not be visible
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |
    And I log in as "admin"
    And I add a new user tour with:
      | Name                | First tour |
      | Description         | My first tour |
      | Apply to URL match  | /totara/dashboard/% |
      | Tour is enabled     | 0 |
    And I add steps to the "First tour" tour:
      | targettype                  | Title             | Content |
      | Display in middle of page   | Welcome           | Welcome to your personal learning space. We'd like to give you a quick tour to show you some of the areas you may find helpful |
    When I click on "Dashboard" in the totara menu
    Then I should not see "Welcome to your personal learning space. We'd like to give you a quick tour to show you some of the areas you may find helpful"

  @javascript
  Scenario: Tour visibility can be toggled
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |
    And I log in as "admin"
    And I add a new user tour with:
      | Name                | First tour |
      | Description         | My first tour |
      | Apply to URL match  | /totara/dashboard/% |
      | Tour is enabled     | 0 |
    And I add steps to the "First tour" tour:
      | targettype                  | Title             | Content |
      | Display in middle of page   | Welcome           | Welcome to your personal learning space. We'd like to give you a quick tour to show you some of the areas you may find helpful |
    And I open the User tour settings page
    When I click on "Enable" "link" in the "My first tour" "table_row"
    And I click on "Dashboard" in the totara menu
    Then I should see "Welcome to your personal learning space. We'd like to give you a quick tour to show you some of the areas you may find helpful"
