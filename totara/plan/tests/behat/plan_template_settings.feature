@totara @totara_plan @javascript
Feature: Test plan teamplate settings
  In order to test plan settings from a template
  As an admin
  I need to be able to change settings for multiple templates

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | firstname1 | lastname1 | learner1@example.com |
      | manager2 | firstname2 | lastname2 | manager2@example.com |
    And the following job assignments exist:
      | user     | fullname       | manager  |
      | learner1 | jobassignment1 | manager2 |
    And I log in as "admin"
    And I navigate to "Manage templates" node in "Site administration > Learning Plans"
    And I set the following fields to these values:
      | Name             | template 1 |
      | id_enddate_month | December   |
      | id_enddate_day   | 31         |
      | id_enddate_year  | 2020       |
    And I press "Save changes"
    And I switch to "Workflow" tab
    And I click on "Custom workflow" "radio"
    And I press "Save changes"
    And I log out

  Scenario: Test Update plan setting on template
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I press "Create new learning plan"
    And I set the field "Plan template" to "template 1"
    When I press "Create plan"
    Then I should see "Plan creation successful"
    And I switch to "Courses" tab
    And "Add courses" "button" should exist

    And I log out
    And I log in as "admin"
    And I navigate to "Manage templates" node in "Site administration > Learning Plans"
    And I set the following fields to these values:
      | Name             | template 2 |
      | id_enddate_month | December   |
      | id_enddate_day   | 25         |
      | id_enddate_year  | 2025       |
    And I press "Save changes"
    And I switch to "Workflow" tab
    And I click on "Custom workflow" "radio"
    And I click on "Advanced workflow settings" "button"
    And I set the field "updatelearner" to "Deny"
    And I set the field "updatemanager" to "Deny"
    When I click on "Save changes" "button"
    Then I should see "Plan settings successfully updated"
    And I log out

    Then I log in as "learner1"
    Then I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I press "Create new learning plan"
    And I set the field "Plan template" to "template 2"
    When I press "Create plan"
    Then I should see "Plan creation successful"
    And I switch to "Courses" tab
    And "Add courses" "button" should not exist

    When I click on "template 1" "link"
    Then I should see "template 1"
    And I switch to "Courses" tab
    And "Add courses" "button" should exist
    And I log out

    # Check permissions for manager
    Then I log in as "manager2"
    And I click on "Team" in the totara menu
    And I click on "Plans" "link"
    And I should see "You are viewing firstname1 lastname1's plans."
    And I click on "template 1" "link"
    And I switch to "Courses" tab
    And "Add courses" "button" should exist

    Then I click on "template 2" "link"
    And I switch to "Courses" tab
    And "Add courses" "button" should not exist
    And I log out

    # The admin is a superuser so they can always see
    # add buttons (ignoring permissions)
    When I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "firstname1 lastname1" "link"
    And I click on "Learning Plans" "link"
    And I should see "You are viewing firstname1 lastname1's plans."
    And I click on "template 1" "link"
    And I switch to "Courses" tab
    And "Add courses" "button" should exist

    And I click on "template 2" "link"
    And I switch to "Courses" tab
    And "Add courses" "button" should exist

  Scenario: Test different update settings for learner/manager
    Given I log in as "admin"
    And I navigate to "Manage templates" node in "Site administration > Learning Plans"
    And I click on "template 1" "link"
    And I switch to "Workflow" tab
    And I click on "Advanced workflow settings" "button"
    And I set the field "updatelearner" to "Allow"
    And I set the field "updatemanager" to "Deny"
    When I click on "Save changes" "button"
    Then I should see "Plan settings successfully updated"
    And I log out

    Then I log in as "learner1"
    Then I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I press "Create new learning plan"
    And I set the field "Plan template" to "template 1"
    When I press "Create plan"
    Then I should see "Plan creation successful"
    And I switch to "Courses" tab
    And "Add courses" "button" should exist
    And I log out

    Then I log in as "manager2"
    And I click on "Team" in the totara menu
    And I click on "Plans" "link"
    And I should see "You are viewing firstname1 lastname1's plans."
    And I click on "template 1" "link"
    And I switch to "Courses" tab
    And "Add courses" "button" should not exist

  Scenario: Test approve settings for learner/manager
    Given I log in as "admin"
    And I navigate to "Manage templates" node in "Site administration > Learning Plans"
    And I click on "template 1" "link"
    And I switch to "Workflow" tab
    And I click on "Advanced workflow settings" "button"
    And I set the field "approvelearner" to "Allow"
    And I set the field "approvemanager" to "Deny"
    When I click on "Save changes" "button"
    Then I should see "Plan settings successfully updated"
    And I log out

    Then I log in as "learner1"
    Then I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I press "Create new learning plan"
    And I set the field "Plan template" to "template 1"
    When I press "Create plan"
    Then I should see "Plan creation successful"
    And "Activate Plan" "button" should exist
    And I log out

    Then I log in as "manager2"
    And I click on "Team" in the totara menu
    And I click on "Plans" "link"
    And I should see "You are viewing firstname1 lastname1's plans."
    And I click on "template 1" "link"
    And "Activate Plan" "button" should not exist
