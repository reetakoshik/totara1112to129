@core @core_user @javascript
Feature: Enable/disable password field based on authentication selected.
  In order edit a user password properly
  As an admin
  I need to be able to notice if the change in password is allowed by athuentication plugin or not

  Background:
    Given I am on a totara site

  Scenario: Verify the password field is enabled/disabled based on authentication selected when creating a new user.

    Given I log in as "admin"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I press "Add a new user"
    Then the "New password" "field" should be enabled
    And "Language" "field" should exist
    And I set the field "auth" to "Web services authentication"
    And the "New password" "field" should be disabled
    And I set the field "auth" to "Email-based self-registration"
    And the "New password" "field" should be enabled
    # We need to cancel/submit a form that has been modified.
    And I press "Create user"

  Scenario: Verify the password field is enabled/disabled based on authentication selected when editing an existing user.

    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Leonard   | Learner1 | learner1@example.com |
    And I log in as "admin"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Edit" "link" in the "Leonard Learner1" "table_row"
    Then "Language" "field" should not exist
    And the "New password" "field" should be enabled
    And I set the field "auth" to "Web services authentication"
    And the "newpassword" "field" should be disabled
    And I set the field "auth" to "Email-based self-registration"
    And the "newpassword" "field" should be enabled
    # We need to cancel/submit a form that has been modified.
    And I press "Update profile"
