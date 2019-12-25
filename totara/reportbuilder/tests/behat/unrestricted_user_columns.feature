@totara @totara_reportbuilder
Feature: Test unrestricted user columns can be added and viewed by the admin
  As an admin
  I create a report using the user report source
  I test that I can add the unrestricted fields
  I test that I can view the unrestricted fields

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                 | maildisplay |
      | user1    | User      | One      | user1@example.invalid | 1           |
      | user2    | User      | Two      | user2@example.invalid | 1           |
      | user3    | User      | Three    | user3@example.invalid | 0           |
      | user4    | User      | Four     | user4@example.invalid | 0           |
      | user5    | User      | Five     | user5@example.invalid | 0           |
      | user6    | User      | Six      | user6@example.invalid | 0           |
    And the following "position" frameworks exist:
      | fullname             | idnumber |
      | Position framework 1 | PF1      |
    And the following "position" hierarchy exists:
      | framework | idnumber | fullname   |
      | PF1       | P1       | Position 1 |
    And the following job assignments exist:
      | user   | position | idnumber | manager  |
      | user2  | P1       | 1        | user1    |
      | user3  | P1       | 1        | user1    |
      | user5  | P1       | 1        | user4    |
      | user6  | P1       | 1        | user4    |
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | User report |
      | Source      | User        |
    And I press "Create report"
    And I should see "Edit Report 'User report'"
    And I switch to "Columns" tab
    And I add the "User's Email (ignoring user display setting)" column to the report
    And I add the "User's Email" column to the report
    And I add the "User's Manager Email(s) (ignoring user display setting)" column to the report
    And I add the "User's Manager Email(s)" column to the report
    And I switch to "Access" tab
    And I set the following fields to these values:
      | role_activeroles[7] | 1 |
    And I press "Save changes"

  Scenario: Test that I can add and view unrestricted user columns in report builder with email visible
    Given I navigate to "User policies" node in "Site administration > Permissions"
    And I set the following fields to these values:
      | s__showuseridentity[email] | 1 |
    And I press "Save changes"
    When I navigate to my "User report" report
    # Check User Two row
    Then I should see "user2@example.invalid" in the "user_email" report column for "User Two"
    And I should see "user2@example.invalid" in the "user_emailunobscured" report column for "User Two"
    And I should see "user1@example.invalid" in the "job_assignment_allmanagerobsemails" report column for "User Two"
    And I should see "user1@example.invalid" in the "job_assignment_allmanagerunobsemails" report column for "User Two"
    # Check User Three row
    And I should see "Email is private" in the "user_email" report column for "User Three"
    And I should see "user3@example.invalid" in the "user_emailunobscured" report column for "User Three"
    And I should see "user1@example.invalid" in the "job_assignment_allmanagerobsemails" report column for "User Three"
    And I should see "user1@example.invalid" in the "job_assignment_allmanagerunobsemails" report column for "User Three"
    # Check User Five row
    And I should see "Email is private" in the "user_email" report column for "User Five"
    And I should see "user5@example.invalid" in the "user_emailunobscured" report column for "User Five"
    And I should see "Email is private" in the "job_assignment_allmanagerobsemails" report column for "User Five"
    And I should see "user4@example.invalid" in the "job_assignment_allmanagerunobsemails" report column for "User Five"
    And I log out

    # Test as a authenticated user.
    When I log in as "user1"
    And I navigate to my "User report" report
    # Check User Two row
    Then I should see "user2@example.invalid" in the "user_email" report column for "User Two"
    And I should not see "user2@example.invalid" in the "user_emailunobscured" report column for "User Two"
    And I should see "user1@example.invalid" in the "job_assignment_allmanagerobsemails" report column for "User Two"
    And I should not see "user1@example.invalid" in the "job_assignment_allmanagerunobsemails" report column for "User Two"
    # Check User Three row
    And I should see "Email is private" in the "user_email" report column for "User Three"
    And I should not see "user3@example.invalid" in the "user_emailunobscured" report column for "User Three"
    And I should see "user1@example.invalid" in the "job_assignment_allmanagerobsemails" report column for "User Three"
    And I should not see "user1@example.invalid" in the "job_assignment_allmanagerunobsemails" report column for "User Three"
    # Check User Five row
    And I should see "Email is private" in the "user_email" report column for "User Five"
    And I should not see "user5@example.invalid" in the "user_emailunobscured" report column for "User Five"
    And I should see "Email is private" in the "job_assignment_allmanagerobsemails" report column for "User Five"
    And I should not see "user4@example.invalid" in the "job_assignment_allmanagerunobsemails" report column for "User Five"

  Scenario: Test that I can add and view unrestricted user columns in report builder when email is hidden
    Given I navigate to "User policies" node in "Site administration > Permissions"
    And I set the following fields to these values:
      | s__showuseridentity[email] | 0 |
    And I press "Save changes"
    When I navigate to my "User report" report
    # Check User Two row
    Then I should see "user2@example.invalid" in the "user_email" report column for "User Two"
    And I should see "user2@example.invalid" in the "user_emailunobscured" report column for "User Two"
    And I should see "user1@example.invalid" in the "job_assignment_allmanagerobsemails" report column for "User Two"
    And I should see "user1@example.invalid" in the "job_assignment_allmanagerunobsemails" report column for "User Two"
    # Check User Three row
    And I should see "Email is private" in the "user_email" report column for "User Three"
    And I should see "user3@example.invalid" in the "user_emailunobscured" report column for "User Three"
    And I should see "user1@example.invalid" in the "job_assignment_allmanagerobsemails" report column for "User Three"
    And I should see "user1@example.invalid" in the "job_assignment_allmanagerunobsemails" report column for "User Three"
    # Check User Five row
    And I should see "Email is private" in the "user_email" report column for "User Five"
    And I should see "user5@example.invalid" in the "user_emailunobscured" report column for "User Five"
    And I should see "Email is private" in the "job_assignment_allmanagerobsemails" report column for "User Five"
    And I should see "user4@example.invalid" in the "job_assignment_allmanagerunobsemails" report column for "User Five"
    And I log out

    # Test as a authenticated user.
    When I log in as "user1"
    And I navigate to my "User report" report
    # Check User Two row
    Then I should see "user2@example.invalid" in the "user_email" report column for "User Two"
    And I should not see "user2@example.invalid" in the "user_emailunobscured" report column for "User Two"
    And I should see "user1@example.invalid" in the "job_assignment_allmanagerobsemails" report column for "User Two"
    And I should not see "user1@example.invalid" in the "job_assignment_allmanagerunobsemails" report column for "User Two"
    # Check User Three row
    And I should see "Email is private" in the "user_email" report column for "User Three"
    And I should not see "user3@example.invalid" in the "user_emailunobscured" report column for "User Three"
    And I should see "user1@example.invalid" in the "job_assignment_allmanagerobsemails" report column for "User Three"
    And I should not see "user1@example.invalid" in the "job_assignment_allmanagerunobsemails" report column for "User Three"
    # Check User Five row
    And I should see "Email is private" in the "user_email" report column for "User Five"
    And I should not see "user5@example.invalid" in the "user_emailunobscured" report column for "User Five"
    And I should see "Email is private" in the "job_assignment_allmanagerobsemails" report column for "User Five"
    And I should not see "user4@example.invalid" in the "job_assignment_allmanagerunobsemails" report column for "User Five"

  @javascript
  Scenario: Test that a user can view unrestricted user columns in report builder when email is hidden
    Given I set the following system permissions of "Authenticated user" role:
      | capability | permission |
      | moodle/site:viewuseridentity | Allow |
    And I navigate to "User policies" node in "Site administration > Permissions"
    And I set the following fields to these values:
      | s__showuseridentity[email] | 0 |
    And I press "Save changes"
    And I log out

    When I log in as "user1"
    And I navigate to my "User report" report
    # Check User Two row
    Then I should see "user2@example.invalid" in the "user_email" report column for "User Two"
    And I should not see "user2@example.invalid" in the "user_emailunobscured" report column for "User Two"
    And I should see "user1@example.invalid" in the "job_assignment_allmanagerobsemails" report column for "User Two"
    And I should not see "user1@example.invalid" in the "job_assignment_allmanagerunobsemails" report column for "User Two"
    # Check User Three row
    And I should see "Email is private" in the "user_email" report column for "User Three"
    And I should not see "user3@example.invalid" in the "user_emailunobscured" report column for "User Three"
    And I should see "user1@example.invalid" in the "job_assignment_allmanagerobsemails" report column for "User Three"
    And I should not see "user1@example.invalid" in the "job_assignment_allmanagerunobsemails" report column for "User Three"
    # Check User Five row
    And I should see "Email is private" in the "user_email" report column for "User Five"
    And I should not see "user5@example.invalid" in the "user_emailunobscured" report column for "User Five"
    And I should see "Email is private" in the "job_assignment_allmanagerobsemails" report column for "User Five"
    And I should not see "user4@example.invalid" in the "job_assignment_allmanagerunobsemails" report column for "User Five"

  @javascript
  Scenario: Test that a user can view unrestricted user columns in report builder when email is visible
    Given I set the following system permissions of "Authenticated user" role:
      | capability | permission |
      | moodle/site:viewuseridentity | Allow |
    And I navigate to "User policies" node in "Site administration > Permissions"
    And I set the following fields to these values:
      | s__showuseridentity[email] | 1 |
    And I press "Save changes"
    And I log out

    When I log in as "user1"
    And I navigate to my "User report" report
    # Check User Two row
    Then I should see "user2@example.invalid" in the "user_email" report column for "User Two"
    And I should see "user2@example.invalid" in the "user_emailunobscured" report column for "User Two"
    And I should see "user1@example.invalid" in the "job_assignment_allmanagerobsemails" report column for "User Two"
    And I should see "user1@example.invalid" in the "job_assignment_allmanagerunobsemails" report column for "User Two"
    # Check User Three row
    And I should see "Email is private" in the "user_email" report column for "User Three"
    And I should see "user3@example.invalid" in the "user_emailunobscured" report column for "User Three"
    And I should see "user1@example.invalid" in the "job_assignment_allmanagerobsemails" report column for "User Three"
    And I should see "user1@example.invalid" in the "job_assignment_allmanagerunobsemails" report column for "User Three"
    # Check User Five row
    And I should see "Email is private" in the "user_email" report column for "User Five"
    And I should see "user5@example.invalid" in the "user_emailunobscured" report column for "User Five"
    And I should see "Email is private" in the "job_assignment_allmanagerobsemails" report column for "User Five"
    And I should see "user4@example.invalid" in the "job_assignment_allmanagerunobsemails" report column for "User Five"
