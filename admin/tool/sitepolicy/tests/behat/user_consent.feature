@javascript @tool @tool_sitepolicy @totara
Feature: Give or withhold user consent
  As a user
  I am required to view all user published policies and give
  my consent to all mandatory option statements.
  I am also able to view the statements I gave my consent to and
  change my mind on whether I want to give my consent or not

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Learner   | One      | learner1@example.com |
    And  I log in as "admin"
    And I set the following administration settings values:
      | Enable site policies | 1    |
      | Guest login button   | Show |
    And I log out

  Scenario: User must view single optional sitepolicy before being allowed to log in
    Given the following "multiversionpolicies" exist in "tool_sitepolicy" plugin:
      | hasdraft | numpublished | allarchived | title    | languages |statement          | numoptions | consentstatement       | providetext | withholdtext | mandatory |
      | 0        | 1            | 0           | Policy 1 | en        |Policy 1 statement | 1          | P1 - Consent statement | Yes         | No           | none      |
    And I log in as "learner1"
    Then I should see "1 of 1 policies"
    And I should see "Policy 1"
    And I should see "Policy 1 statement"
    And I should see "P1 - Consent statement 1"
    And I should not see "Consent is required to access the site"

    And I set the "P1 - Consent statement 1" Totara form field to "0"
    And I press "Submit"
    Then I should see "Current Learning"

    When I log out
    And I log in as "learner1"
    Then I should see "Current Learning"

  Scenario: User must view all optional sitepolicies before being allowed to log in
    Given the following "multiversionpolicies" exist in "tool_sitepolicy" plugin:
      | hasdraft | numpublished | allarchived | title    | languages |statement          | numoptions | consentstatement       | providetext | withholdtext | mandatory |
      | 0        | 1            | 0           | Policy 1 | en        |Policy 1 statement | 1          | P1 - Consent statement | Yes         | No           | none      |
      | 0        | 1            | 0           | Policy 2 | en        |Policy 2 statement | 1          | P2 - Consent statement | Yes         | No           | none      |
    And I log in as "learner1"

    Then I should see "1 of 2 policies"
    And I should see "Policy 1"
    And I should see "Policy 1 statement"
    And I should see "P1 - Consent statement 1"
    And I should not see "Consent is required to access the site"

    When I set the "P1 - Consent statement 1" Totara form field to "0"
    And I press "Submit"
    Then I should see "2 of 2 policies"
    And I should see "Policy 2"
    And I should see "Policy 2 statement"
    And I should see "P2 - Consent statement 1"
    And I should not see "Consent is required to access the site"

    When I set the "P2 - Consent statement 1" Totara form field to "1"
    And I press "Submit"
    Then I should see "Current Learning"

  Scenario: User is only required to view an optional policy once
    Given the following "multiversionpolicies" exist in "tool_sitepolicy" plugin:
      | hasdraft | numpublished | allarchived | title    | languages |statement          | numoptions | consentstatement       | providetext | withholdtext | mandatory |
      | 0        | 1            | 0           | Policy 1 | en        |Policy 1 statement | 1          | P1 - Consent statement | Yes         | No           | none      |
      | 0        | 1            | 0           | Policy 2 | en        |Policy 2 statement | 1          | P2 - Consent statement | Yes         | No           | none      |
    And I log in as "learner1"

    Then I should see "1 of 2 policies"
    And I should see "Policy 1"
    And I should see "Policy 1 statement"
    And I should see "P1 - Consent statement 1"
    And I should not see "Consent is required to access the site"

    When I set the "P1 - Consent statement 1" Totara form field to "0"
    And I press "Submit"
    And I log out
    And I log in as "learner1"

    Then I should see "1 of 1 policies"
    And I should see "Policy 2"
    And I should see "Policy 2 statement"
    And I should see "P2 - Consent statement 1"
    And I should not see "Consent is required to access the site"


  Scenario: User must consent to all published mandatory statements before being allowed to log in
    Given the following "multiversionpolicies" exist in "tool_sitepolicy" plugin:
      | hasdraft | numpublished | allarchived | title    | languages |statement          | numoptions | consentstatement       | providetext | withholdtext | mandatory |
      | 0        | 1            | 0           | Policy 1 | en        |Policy 1 statement | 1          | P1 - Consent statement | Yes         | No           | none      |
      | 0        | 1            | 0           | Policy 2 | en        |Policy 2 statement | 1          | P2 - Consent statement | Yes         | No           | true      |
      | 0        | 1            | 1           | Policy 3 | en        |Policy 3 statement | 1          | P3 - Consent statement | Yes         | No           | true      |
      | 0        | 1            | 0           | Policy 4 | en        |Policy 4 statement | 1          | P4 - Consent statement | Yes         | No           | true      |
    And I log in as "learner1"

    Then I should see "1 of 3 policies"
    And I should see "Policy 1"
    And I should see "Policy 1 statement"
    And I should see "P1 - Consent statement 1"
    And I should not see "Consent is required to access the site"

    When I set the "P1 - Consent statement 1" Totara form field to "0"
    And I press "Submit"
    Then I should see "2 of 3 policies"
    And I should see "Policy 2"
    And I should see "Policy 2 statement"
    And I should see "P2 - Consent statement 1 (Consent is required to access the site)"

    When I set the "P2 - Consent statement 1 (Consent is required to access the site)" Totara form field to "0"
    And I press "Submit"
    Then I should see "You are about to lose access"

    When I press "Go back to policy"
    And I set the "P2 - Consent statement 1 (Consent is required to access the site)" Totara form field to "1"
    And I press "Submit"
    Then I should see "3 of 3 policies"
    And I should see "Policy 4"
    And I should see "Policy 4 statement"
    And I should see "P4 - Consent statement 1 (Consent is required to access the site)"

    When I set the "P4 - Consent statement 1 (Consent is required to access the site)" Totara form field to "0"
    And I press "Submit"
    Then I should see "You are about to lose access"

    When I press "Log me out"
    And I log in as "learner1"
    Then I should see "1 of 1 policies"
    And I should see "Policy 4"
    And I should see "Policy 4 statement"
    And I should see "P4 - Consent statement 1 (Consent is required to access the site)"

    When I set the "P4 - Consent statement 1 (Consent is required to access the site)" Totara form field to "1"
    And I press "Submit"
    Then I should see "Current Learning"

    When I log out
    And I log in as "learner1"
    Then I should see "Current Learning"


  Scenario: Guest user must view all policies and consent to all mandatory policies on every login
    Given the following "multiversionpolicies" exist in "tool_sitepolicy" plugin:
      | hasdraft | numpublished | allarchived | title    | languages |statement          | numoptions | consentstatement       | providetext | withholdtext | mandatory |
      | 0        | 1            | 0           | Policy 1 | en        |Policy 1 statement | 1          | P1 - Consent statement | Yes         | No           | none      |
      | 0        | 1            | 0           | Policy 2 | en        |Policy 2 statement | 1          | P2 - Consent statement | Yes         | No           | true      |
    And I am on homepage
    And I click on "#guestlogin input[type=submit]" "css_element"

    Then I should see "1 of 2 policies"
    And I should see "Policy 1"
    And I should see "Policy 1 statement"
    And I should see "P1 - Consent statement 1"
    And I should not see "Consent is required to access the site"

    When I set the "P1 - Consent statement 1" Totara form field to "1"
    And I press "Submit"
    Then I should see "2 of 2 policies"
    And I should see "Policy 2"
    And I should see "Policy 2 statement"
    And I should see "P2 - Consent statement 1 (Consent is required to access the site)"

    When I set the "P2 - Consent statement 1 (Consent is required to access the site)" Totara form field to "1"
    And I press "Submit"
    Then I should see "You are currently using guest access"

    # guest user's consent is still valid for the session
    When I follow "Log in"
    And I log in as "learner1"
    Then I should see "1 of 2 policies"
    And I should see "Policy 1"

    # new session after logout
    When I log out
    And I log in as "learner1"
    Then I should see "1 of 2 policies"
    And I should see "Policy 1"

    # new session - guest have to re-consent all
    When I log out
    And I click on "#guestlogin input[type=submit]" "css_element"
    Then I should see "1 of 2 policies"
    And I should see "Policy 1"

  Scenario: Admin should not view site policy if log in as leaner
    Given I log in as "admin"
    And the following "multiversionpolicies" exist in "tool_sitepolicy" plugin:
      | hasdraft | numpublished | allarchived | title    | languages |statement          | numoptions | consentstatement       | providetext | withholdtext | mandatory |
      | 0        | 1            | 0           | Policy 1 | en        |Policy 1 statement | 1          | P1 - Consent statement | Yes         | No           | none      |
    And I navigate to "Users > Browse list of users" in site administration
    And I follow "Learner One"
    And I follow "Log in as"
    And I should see "You are logged in as Learner One"
    When I press "Continue"
    Then I should see "Current Learning"
    And I log out
