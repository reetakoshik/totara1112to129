@tool @tool_usertours
Feature: Apply tour filters to a tour
  In order to give more directed tours
  As an administrator
  I need to create a user tour with filters applied

  @javascript
  Scenario: Add a tour for a different theme
    Given I log in as "admin"
    And I add a new user tour with:
      | Name                | First tour |
      | Description         | My first tour |
      | Apply to URL match  | /my/% |
      | Tour is enabled     | 1 |
      | Theme               | Basis |
    And I add steps to the "First tour" tour:
      | targettype                  | Title             | Content |
      | Display in middle of page   | Welcome           | Welcome to your personal learning space. We'd like to give you a quick tour to show you some of the areas you may find helpful |
    When I am on homepage
    Then I should not see "Welcome to your personal learning space. We'd like to give you a quick tour to show you some of the areas you may find helpful"

  @javascript
  Scenario: Add a tour for a specific role
    Given the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 1 | C1        | topics | 1                |
    And the following "users" exist:
      | username |
      | editor1  |
      | teacher1 |
      | student1 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | editor1  | C1     | editingteacher |
      | teacher1 | C1     | teacher        |
      | student1 | C1     | student        |
    And I log in as "admin"
    And I add a new user tour with:
      | Name                | First tour |
      | Description         | My first tour |
      | Apply to URL match  | /course/view.php% |
      | Tour is enabled     | 1 |
      | Role                | Student,Non-editing teacher |
    And I add steps to the "First tour" tour:
      | targettype                  | Title             | Content |
      | Display in middle of page   | Welcome           | Welcome to your course tour.|
    And I log out
    And I log in as "editor1"
    And I am on "Course 1" course homepage
    Then I should not see "Welcome to your course tour."
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Welcome to your course tour."
    And I click on "End tour" "button"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I should see "Welcome to your course tour."

#    Test for TL-17755 regression
  @javascript
  Scenario: Add a tour with settings block disabled
    Given I log in as "admin"
    And I follow "Dashboard"
    And I press "Customise this page"
    And I configure the "Administration" block
    And I expand all fieldsets
    And I set the field "Visible" to "Yes"
    And I press "Save changes"
    And I am on site homepage
    And I add a new user tour with:
      | Name                | First tour |
      | Description         | My first tour |
      | Apply to URL match  | /dashboard/% |
      | Tour is enabled     | 1 |
    And I add steps to the "First tour" tour:
      | targettype                  | Title             | Content |
      | Display in middle of page   | Welcome           | Welcome to your personal learning space. We'd like to give you a quick tour to show you some of the areas you may find helpful |
    When I follow "Dashboard"
    Then I should see "Welcome to your personal learning space. We'd like to give you a quick tour to show you some of the areas you may find helpful"

  @javascript
  Scenario: Aria tags should not exist
    Given the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 1 | C1        | topics | 1                |
    And the following "users" exist:
      | username |
      | teacher1 |
      | student1 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | teacher        |
      | student1 | C1     | student        |
    And I log in as "admin"
    And I open the User tour settings page
    And I add a new user tour with:
      | Name                | First tour |
      | Description         | My first tour |
      | Apply to URL match  | /course/view.php% |
      | Tour is enabled     | 1 |
      | Role                | Student,Non-editing teacher |
    And I add steps to the "First tour" tour:
      | targettype                  | Title            | Content |
      | Display in middle of page   | Welcome          | Welcome to your course tour.|
    And I add steps to the "First tour" tour:
      | targettype                  | targetvalue_selector | Title             | Content |
      | Selector                    | .usermenu            | User menu         | This is your personal user menu. You'll find your personal preferences and your user profile here. |
    And I add steps to the "First tour" tour:
      | targettype                  | Title                        | Content |
      | Display in middle of page   | Informative message          | Informative message about the course tour.|
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    When I click on "Next" "button"
    Then "div.usermenu[aria-describedby^='tour-step-tool_usertours']" "css_element" should exist
    And "div.usermenu[tabindex]" "css_element" should exist
    When I click on "Next" "button"
    Then "div.usermenu[aria-describedby^='tour-step-tool_usertours']" "css_element" should not exist
    And "div.usermenu[tabindex]" "css_element" should not exist
    When I click on "Previous" "button"
    Then "div.usermenu[aria-describedby^='tour-step-tool_usertours']" "css_element" should exist
    And "div.usermenu[tabindex]" "css_element" should exist
    When I click on "End tour" "button"
    Then "div.usermenu[aria-describedby^='tour-step-tool_usertours']" "css_element" should not exist
    And "div.usermenu[tabindex]" "css_element" should not exist
