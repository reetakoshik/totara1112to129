@javascript @tool @tool_sitepolicy @totara @auth_approved
Feature: Give consent during sign-up
  If a site policy exist and a new user signs up then the user
  will be required to accept the site policy before signing up.

  Background:
    Given I am on a totara site
    And  I log in as "admin"
    And I set the following administration settings values:
      | Enable site policies | 1 |
    And the following config values are set as admin:
      | passwordpolicy | 0 |

    And I set these auth approval plugin settings:
      | active       | true                                    |
      | instructions | Nothing; everything is self explanatory |
      | whitelist    | example.com, example.org                |
    And the following "multiversionpolicies" exist in "tool_sitepolicy" plugin:
      | hasdraft | numpublished | allarchived | title    | languages |statement          | numoptions | consentstatement       | providetext | withholdtext | mandatory |
      | 0        | 1            | 0           | Policy 1 | en        |Policy 1 statement | 1          | P1 - Consent statement | Yes         | No           | true      |
      | 0        | 1            | 0           | Policy 2 | en        |Policy 2 statement | 1          | P2 - Consent statement | Yes         | No           | true      |
    And I log out

  Scenario: No signup requests should exist
    Given I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then I should see "There are no records in this report"

  Scenario: User must view sitepolicies before being allowed to sign up
    Given I click on "Create new account" "button"
    Then I should see "1 of 2 policies"
    And I should see "Policy 1"
    And I should see "Policy 1 statement"
    And I should see "P1 - Consent statement 1"
    And I should see "Consent is required to access the site"

    When I set the "P1 - Consent statement 1 (Consent is required to access the site)" Totara form field to "1"
    And I press "Submit"
    Then I should see "2 of 2 policies"
    And I should see "Policy 2"
    And I should see "Policy 2 statement"
    And I should see "P2 - Consent statement 1"
    And I should see "Consent is required to access the site"

    When I set the "P2 - Consent statement 1 (Consent is required to access the site)" Totara form field to "1"
    And I press "Submit"
    Then I should see "New account"

    When I set the following fields to these values:
      | Username   | user1           |
      | Password   | user1           |
      | Email      | user1@email.com |
      | First name | User            |
      | Surname    | One             |
      | City       | City            |
      | Country    | New Zealand     |
    And I press "Request account"
    Then I should see "An email should have been sent to your address at user1@email.com"

    # Successful signup outcome: plugin table has record.
    When I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    Then "user1" row "User First Name" column of "auth_approved_pending_requests" table should contain "User"
    And "user1" row "User Last Name" column of "auth_approved_pending_requests" table should contain "One"
    And "user1" row "User's Email" column of "auth_approved_pending_requests" table should contain "user1@email.com"
    And "user1" row "Email confirmed" column of "auth_approved_pending_requests" table should contain "No"

    # Approve request confirmation
    When I click on "Approve" "link" in the "user1" "table_row"
    Then I should see "Are you sure you want to approve this request?"

    # Approve request
    When I click on "Approve" "button"
    Then I should see "Account request \"user1@email.com\" was approved"

    # Logging in as user should not prompt for site policy consent.
    When I log out
    And I log in as "user1"
    Then I should see "Current Learning"
