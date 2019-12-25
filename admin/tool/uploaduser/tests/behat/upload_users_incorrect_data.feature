@tool @tool_uploaduser @_file_upload @javascript
Feature: Upload users with incorrect data
  In order to test users with incorrect data cannot be added into the system
  As an admin
  I need to upload files containing a mix of valid and invalid users data

  Scenario: Upload users with invalid emails and enrol them on courses and groups
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Maths    | math102   | 0        |
    And the following "groups" exist:
      | name      | course  | idnumber |
      | Section 1 | math102 | S1 |
      | Section 3 | math102 | S3 |
    And I log in as "admin"
    And I navigate to "Upload users" node in "Site administration > Users"
    When I upload "lib/tests/fixtures/upload_users_incorrect_data.csv" file to "File" filemanager
    And I press "Upload users"
    Then I should see "Upload users preview"
    And I should see "Tom"
    And I should see "Jones"
    And I should see "Jane"
    And I should see "verysecret"
    And I should see "Reznor"
    And I should see "course1"
    And I should see "math102"
    And I should see "group1"
    And I should see "Invalid email address"
    And I press "Upload users"
    And I should see "Users created: 1"
    And I should see "Errors: 2"
    And I press "Continue"
    And I navigate to "Users > Browse list of users" in site administration
    And I should see "Jane Doe"
    And I should not see "Tom Jones"
    And I should not see "Trent Reznor"
    And I am on "Maths" course homepage
    And I navigate to "Users > Groups" in current page administration
    And I set the field "groups" to "Section 3 (1)"
    And the "members" select box should contain "Jane Doe"

  Scenario: Upload existing users and check only the ones with valid data are modified.
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | jonest   | Tom       | Jonest   | jonest@example.com  |
      | doe      | Jane      | Doe      | jonedoe@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Maths    | math102   | 0        |
    And the following "groups" exist:
      | name      | course  | idnumber |
      | Section 1 | math102 | S1       |
      | Section 3 | math102 | S3       |
    And I log in as "admin"
    And I navigate to "Upload users" node in "Site administration > Users"
    When I upload "lib/tests/fixtures/upload_users_incorrect_data.csv" file to "File" filemanager
    And I press "Upload users"
    Then I should see "Upload users preview"
    And I should see "Tom"
    And I should see "Jones"
    And I should see "Jane"
    And I should see "verysecret"
    And I should see "Reznor"
    And I should see "course1"
    And I should see "math102"
    And I should see "group1"
    And I should see "Invalid email address"
    And I set the field "Upload type" to "Add new and update existing users"
    And I set the field "Existing user details" to "Override with file"
    And I press "Upload users"
    And I should see "Users updated: 1"
    And I should see "Errors: 2"
    And I press "Continue"
    And I navigate to "Users > Browse list of users" in site administration
    And I should see "Jane Doe"
    And I should see "Tom Jonest"
    And I should not see "Trent Reznor"
    And I follow "Jane Doe"
    And I should see "doe@example.com"
