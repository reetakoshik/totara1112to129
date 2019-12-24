@totara @auth @auth_approved
Feature: Test basic functionality of self-registration with approval
  In order to use self registraton with approval
  I need to be able to enable to sign up, confirm and approve or reject

  @javascript
  Scenario: Test sign up email confirmation
    Given I log in as "admin"
    And I navigate to "Manage authentication" node in "Site administration > Plugins > Authentication"
    And I click on "Enable" "link" in the "Self-registration with approval" "table_row"
    And I set the following administration settings values:
      | registerauth | Self-registration with approval |
    And I log out
    And I follow "Log in"
    And I press "Create new account"
    And I set the following fields to these values:
      | Username      | test1             |
      | Password      | Password_1        |
      | Email address | test1@example.com |
      | First name    | Test              |
      | Surname       | Account           |
    And I press "Request account"
    And I should see "An email should have been sent to your address at test1@example.com"
    When I confirm self-registration request from email "test1@example.com"
    Then I should see "an email should have been sent to your address at test1@example.com with information describing the account approval process"

    When I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    And I click on "Approve" "link" in the "test1@example.com" "table_row"
    And I press "Approve"
    Then I should see "Account request \"test1@example.com\" was approved"

    When I log out
    And I follow "Log in"
    And I set the following fields to these values:
      | Username      | test1             |
      | Password      | Password_1        |
    And I press "Log in"
    Then I should see "Test Account"
    And I should see "Current Learning"
