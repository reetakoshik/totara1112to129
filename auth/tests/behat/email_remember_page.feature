@core @core_auth @totara
Feature: Email base self registation page redirection
    In order to check page redirection
    As a user
    I need to go to a page that requires login

  Scenario: Navigate to the course catalog while logged out and ensure the page is remembered
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Manage authentication" node in "Site administration > Plugins > Authentication"
    And I click on "Enable" "link" in the "Email-based self-registration" "table_row"
    And I set the field "Self registration" to "Email-based self-registration"
    And I press "Save changes"
    And I log out

    When I am on course index
    Then I should see "Is this your first time here?"

    When I press "Create new account"
    And I set the following fields to these values:
        | Username      | bob             |
        | Password      | Bob_111!        |
        | Email address | bob@example.com |
        | Email (again) | bob@example.com |
        | First name    | Bob             |
        | Surname       | Bobbery         |
    And I press "Create my new account"
    And I press "Continue"
    And confirm self-registered login as user "bob"
    Then I should see "Your registration has been confirmed"
    When I press "Continue"
    Then I should see the "totara" catalog page
