@totara @totara_program
Feature: Users visibility of programs can be toggled
  In order to view a program
  As a user
  I need to login if forcelogin enabled

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email |
      | user001 | fn_001 | ln_001 | user001@example.com |
      | user002 | fn_002 | ln_002 | user002@example.com |
      | user003 | fn_003 | ln_003 | user003@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And the following "programs" exist in "totara_program" plugin:
      | fullname                 | shortname |
      | Visibility Program Tests | vistest   |
    And the following "program assignments" exist in "totara_program" plugin:
      | user    | program |
      | user001 | vistest |
    And the following "cohorts" exist:
      | name      | idnumber | contextlevel | reference |
      | Audience1 | aud1     | System       |           |
    And the following "cohort members" exist:
      | user    | cohort |
      | user002 | aud1   |

  @javascript
  Scenario: A user can view programs on the catalog with old visibility (show).
    When I log in as "user001"
    And I click on "Programs" in the totara menu
    Then I should see "Visibility Program Tests"

    When I log out
    And I log in as "user002"
    And I click on "Programs" in the totara menu
    Then I should see "Visibility Program Tests"

    When I log out
    And I log in as "user003"
    And I click on "Programs" in the totara menu
    Then I should see "Visibility Program Tests"

    Given I log out
    And I log in as "admin"
    And I set the following administration settings values:
        | Enhanced catalog | 0 |
    And I press "Save changes"

    When I log out
    And I log in as "user001"
    And I click on "Programs" in the totara menu
    Then I should see "Visibility Program Tests"

    When I log out
    And I log in as "user002"
    And I click on "Programs" in the totara menu
    Then I should see "Visibility Program Tests"

    When I log out
    And I log in as "user003"
    And I click on "Programs" in the totara menu
    Then I should see "Visibility Program Tests"

  @javascript
  Scenario: A user can't view programs on the catalog with old visibility (hide).
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I click on "Visibility Program Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Details" "link"
    And I click on "visible" "checkbox"
    And I press "Save changes"
    And I log out

    When I log in as "user001"
    And I click on "Programs" in the totara menu
    Then I should not see "Visibility Program Tests"

    When I log out
    And I log in as "user002"
    And I click on "Programs" in the totara menu
    Then I should not see "Visibility Program Tests"

    When I log out
    And I log in as "user003"
    And I click on "Programs" in the totara menu
    Then I should not see "Visibility Program Tests"

    Given I log out
    And I log in as "admin"
    And I set the following administration settings values:
        | Enhanced catalog | 0 |
    And I press "Save changes"
    And I log out

    When I log in as "user001"
    And I click on "Programs" in the totara menu
    Then I should not see "Visibility Program Tests"

    When I log out
    And I log in as "user002"
    And I click on "Programs" in the totara menu
    Then I should not see "Visibility Program Tests"

    When I log out
    And I log in as "user003"
    And I click on "Programs" in the totara menu
    Then I should not see "Visibility Program Tests"

  @javascript
  Scenario: A user can view programs on the catalog with audience visibility (all users).
    Given I log in as "admin"
    And I set the following administration settings values:
        | Enable audience-based visibility | 1 |
    And I press "Save changes"
    And I log out

    When I log in as "user001"
    And I click on "Programs" in the totara menu
    Then I should see "Visibility Program Tests"

    When I log out
    And I log in as "user002"
    And I click on "Programs" in the totara menu
    Then I should see "Visibility Program Tests"

    When I log out
    And I log in as "user003"
    And I click on "Programs" in the totara menu
    Then I should see "Visibility Program Tests"

    Given I log out
    And I log in as "admin"
    And I set the following administration settings values:
        | Enhanced catalog | 0 |
    And I press "Save changes"
    And I log out

    When I log in as "user001"
    And I click on "Programs" in the totara menu
    Then I should see "Visibility Program Tests"

    When I log out
    And I log in as "user002"
    And I click on "Programs" in the totara menu
    Then I should see "Visibility Program Tests"

    When I log out
    And I log in as "user003"
    And I click on "Programs" in the totara menu
    Then I should see "Visibility Program Tests"

  @javascript
  Scenario: A user can't view programs on the catalog with audience visibility (no users).
    Given I log in as "admin"
    And I set the following administration settings values:
        | Enable audience-based visibility | 1 |
    And I press "Save changes"
    And I navigate to "Manage programs" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I click on "Visibility Program Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Details" "link"
    And I click on "No users" "option" in the "#id_audiencevisible" "css_element"
    And I press "Save changes"
    And I log out

    When I log in as "user001"
    And I click on "Programs" in the totara menu
    Then I should not see "Visibility Program Tests"

    When I log out
    And I log in as "user002"
    And I click on "Programs" in the totara menu
    Then I should not see "Visibility Program Tests"

    When I log out
    And I log in as "user003"
    And I click on "Programs" in the totara menu
    Then I should not see "Visibility Program Tests"

    Given I log out
    And I log in as "admin"
    And I set the following administration settings values:
        | Enhanced catalog | 0 |
    And I press "Save changes"
    And I log out

    When I log in as "user001"
    And I click on "Programs" in the totara menu
    Then I should not see "Visibility Program Tests"

    When I log out
    And I log in as "user002"
    And I click on "Programs" in the totara menu
    Then I should not see "Visibility Program Tests"

    When I log out
    And I log in as "user003"
    And I click on "Programs" in the totara menu
    Then I should not see "Visibility Program Tests"

  @javascript
  Scenario: Only an enrolled user can view programs on the catalog with audience visibility (enrolled users).
    Given I log in as "admin"
    And I set the following administration settings values:
        | Enable audience-based visibility | 1 |
    And I press "Save changes"
    And I navigate to "Manage programs" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I click on "Visibility Program Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Details" "link"
    And I click on "Enrolled users only" "option" in the "#id_audiencevisible" "css_element"
    And I press "Save changes"
    And I log out

    When I log in as "user001"
    And I click on "Programs" in the totara menu
    Then I should see "Visibility Program Tests"

    When I log out
    And I log in as "user002"
    And I click on "Programs" in the totara menu
    Then I should not see "Visibility Program Tests"

    When I log out
    And I log in as "user003"
    And I click on "Programs" in the totara menu
    Then I should not see "Visibility Program Tests"

    Given I log out
    And I log in as "admin"
    And I set the following administration settings values:
        | Enhanced catalog | 0 |
    And I press "Save changes"
    And I log out

    When I log in as "user001"
    And I click on "Programs" in the totara menu
    Then I should see "Visibility Program Tests"

    When I log out
    And I log in as "user002"
    And I click on "Programs" in the totara menu
    Then I should not see "Visibility Program Tests"

    When I log out
    And I log in as "user003"
    And I click on "Programs" in the totara menu
    Then I should not see "Visibility Program Tests"

  @javascript
  Scenario: Only an enrolled user or audience memeber can view programs on the catalog with audience visibility (audience members).
    Given I log in as "admin"
    And I set the following administration settings values:
        | Enable audience-based visibility | 1 |
    And I press "Save changes"
    And I navigate to "Manage programs" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I click on "Visibility Program Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Details" "link"
    And I click on "Enrolled users and members of the selected audiences" "option" in the "#id_audiencevisible" "css_element"
    And I click on "Add visible audiences" "button"
    And I click on "Audience1" "link" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I click on "OK" "button" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I log out

    When I log in as "user001"
    And I click on "Programs" in the totara menu
    Then I should see "Visibility Program Tests"

    When I log out
    And I log in as "user002"
    And I click on "Programs" in the totara menu
    Then I should see "Visibility Program Tests"

    When I log out
    And I log in as "user003"
    And I click on "Programs" in the totara menu
    Then I should not see "Visibility Program Tests"

    Given I log out
    And I log in as "admin"
    And I set the following administration settings values:
        | Enhanced catalog | 0 |
    And I press "Save changes"
    And I log out

    When I log in as "user001"
    And I click on "Programs" in the totara menu
    Then I should see "Visibility Program Tests"

    When I log out
    And I log in as "user002"
    And I click on "Programs" in the totara menu
    Then I should see "Visibility Program Tests"

    When I log out
    And I log in as "user003"
    And I click on "Programs" in the totara menu
    Then I should not see "Visibility Program Tests"

    # TODO - programs availability.
    # TODO - programs disabled.
