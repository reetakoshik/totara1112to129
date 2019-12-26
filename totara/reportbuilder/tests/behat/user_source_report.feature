@totara @totara_reportbuilder @javascript
Feature: Verify functionality of user source report.

  # See admin/tests/behat/user_report.feature for more tests that are relevant
  # to the user source report but specific to the Browse List of Users report.

  Background:
    Given I am on a totara site
    # 'Learner2' case tests email display with non-standard characters.
    And the following "users" exist:
      | username | firstname | lastname | email                     | maildisplay |
      | learner1 | Bob1      | Learner1 | bob1.learner1@example.com | 1           |
      | learner2 | Bob2      | Learner2 | bob2&learner2@example.com | 1           |
      | learner3 | Bob3      | Learner3 | bob3.learner3@example.com | 0           |
      | learner4 | Bob4      | Learner4 | bob4.learner4@example.com | 2           |

    When I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | User Report |
      | Source      | User        |
    And I press "Create report"
    Then I should see "Edit Report 'User Report'"

    When I switch to "Columns" tab
    And I set the field "newcolumns" to "User's Email"
    And I press "Add"
    And I set the field "newcolumns" to "User Status"
    And I press "Add"
    And I set the field "newcolumns" to "Actions"
    And I press "Add"
    And I press "Save changes"
    Then I should see "Columns updated"

    When I switch to "Access" tab
    And I click on "All users can view this report" "radio"
    And I press "Save changes"
    Then I should see "Report Updated"

    When I follow "View This Report"
    Then I should see "User Report: 6 records shown"

  Scenario: Verify editing user record in user source report.

    Given I follow "Edit Bob1 Learner1"
    When I set the field "First name" to "Sir Bob1"
    And I press "Update profile"
    Then I should see "User Report: 6 records shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname   | Username | User's Email              | User Status |
      | Sir Bob1 Learner1 | learner1 | bob1.learner1@example.com | Active      |

  Scenario: Verify suspend and unsuspend of user in user source report.

    Given I follow "Suspend Bob1 Learner1"
    Then I should see "User Report: 6 records shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              | User Status |
      | Bob1 Learner1   | learner1 | bob1.learner1@example.com | Suspended   |

    When I follow "Unsuspend Bob1 Learner1"
    Then I should see "User Report: 6 records shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              | User Status |
      | Bob1 Learner1   | learner1 | bob1.learner1@example.com | Active      |

  Scenario: Verify delete of user in user source report.

    When I follow "Delete Bob1 Learner1"
    Then I should see "Delete user"

    When I press "Delete"
    Then I should see "User Report: 5 records shown"
    And I should not see "Bob1 Learner1"

  Scenario: Verify confirm new self-registration user in user source report.

    When I click on "Home" in the totara menu
    When I navigate to "Manage authentication" node in "Site administration > Plugins > Authentication"
    And I click on "Enable" "link" in the "Email-based self-registration" "table_row"
    And the following config values are set as admin:
      | registerauth | email |
    And I log out
    Then I should see "Is this your first time here?"

    When I press "Create new account"
    Then I should see "New account"

    When I set the following fields to these values:
      | Username      | learner5                  |
      | Password      | P4ssword!                 |
      | First name    | Bob5                      |
      | Surname       | Learner5                  |
      | Email address | bob5.learner5@example.com |
      | Email (again) | bob5.learner5@example.com |
    And I press "Create my new account"
    Then I should see "An email should have been sent to your address at bob5.learner5@example.com"

    When I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I click on "View" "link" in the "User Report" "table_row"
    Then the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email     | User Status |
      | Bob5 Learner5   | learner5 | Email is private | Unconfirmed |

    When I follow "Confirm Bob5 Learner5"
    Then I should not see "Confirm Bob5 Learner5"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email     | User Status |
      | Bob5 Learner5   | learner5 | Email is private | Active      |

  Scenario: Verify unlock of user account in user source report.

    When the following config values are set as admin:
      | lockoutthreshold | 3 |
    And I log out
    # Attempt three failed logins so the account locks.
    And I set the following fields to these values:
      | Username | learner1 |
      | Password | 12345678 |
    And I press "Log in"
    Then I should see "Invalid login, please try again"

    # Second failed login attempt.
    When I set the following fields to these values:
      | Username | learner1 |
      | Password | abcdefgh |
    And I press "Log in"
    Then I should see "Invalid login, please try again"

    # Third failed login attempt.
    When I set the following fields to these values:
      | Username | learner1 |
      | Password | !"£$%^&* |
    And I press "Log in"
    Then I should see "Invalid login, please try again"

    When I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I click on "View" "link" in the "User Report" "table_row"
    Then the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              | User Status |
      | Bob1 Learner1   | learner1 | bob1.learner1@example.com | Active      |

    When I follow "Unlock Bob1 Learner1"
    Then I should not see "Unlock Bob1 Learner1"
    And I log out

    # Login successfully after being locked out.
    When I log in as "learner1"
    Then I should see "Bob1 Learner1" in the "nav" "css_element"

  Scenario: Verify email address is displayed when correct permissions are used in user source report.

    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    When the following "course enrolments" exist:
      | user     | course | role    |
      | learner2 | C1     | student |
      | learner3 | C1     | student |
      | learner4 | C1     | student |
    # As admin we can see all the learner's record.
    Then I should see "User Report: 6 records shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              | User Status |
      | Bob1 Learner1   | learner1 | bob1.learner1@example.com | Active      |
      | Bob2 Learner2   | learner2 | bob2&learner2@example.com | Active      |
      | Bob3 Learner3   | learner3 | Email is private          | Active      |
      | Bob4 Learner4   | learner4 | Email is private          | Active      |
    And I log out

    When I log in as "learner1"
    And I click on "Reports" in the totara menu
    And I follow "User Report"
    # Email addresses is 'hidden from everyone' and only visible to course members.
    Then the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              | User Status |
      | Bob2 Learner2   | learner2 | bob2&learner2@example.com | Active      |
      | Bob3 Learner3   | learner3 | Email is private          | Active      |
      | Bob4 Learner4   | learner4 | Email is private          | Active      |
    And I log out

    When I log in as "learner2"
    And I click on "Reports" in the totara menu
    And I follow "User Report"
    # Email addresses is 'hidden from everyone' and only visible to course members.
    Then the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              | User Status |
      | Bob2 Learner2   | learner2 | bob2&learner2@example.com | Active      |
      | Bob3 Learner3   | learner3 | Email is private          | Active      |
      | Bob4 Learner4   | learner4 | Email is private          | Active      |

  Scenario: Verify Global Report Restrictions works on the report in user source report.

    Given the following "cohorts" exist:
      | name       | idnumber |
      | Audience 1 | A1       |
      | Audience 2 | A2       |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner1 | A1     |
      | learner2 | A1     |
      | learner3 | A2     |
      | learner4 | A2     |
    And the following config values are set as admin:
      | enableglobalrestrictions | 1 |

    When I click on "Home" in the totara menu
    And I navigate to "Global report restrictions" node in "Site administration > Reports"
    And I press "New restriction"
    And I set the following fields to these values:
      | Name   | User Report Restriction |
      | Active | 1                       |
    And I press "Save changes"
    Then I should see "New restriction \"User Report Restriction\" has been created."

    When I set the field "menugroupselector" to "Audience"
    And I click on "Audience 1" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    Then the following should exist in the "datatable" table:
      | Learner       | Assigned Via        |
      | Bob1 Learner1 |	Audience Audience 1 |
      | Bob2 Learner2 |	Audience Audience 1 |

    When I switch to "Users allowed to select restriction" tab
    And I set the field "menugroupselector" to "Audience"
    And I click on "Audience 2" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    Then the following should exist in the "datatable" table:
      | Learner       | Assigned Via        |
      | Bob3 Learner3 |	Audience Audience 2 |
      | Bob4 Learner4 |	Audience Audience 2 |
    And I log out

    # Learner1 should not have any restrictions on what data it can see.
    When I log in as "learner1"
    And I click on "Reports" in the totara menu
    And I follow "User Report"
    Then I should see "User Report: 6 records shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              | User Status |
      | Bob1 Learner1   | learner1 | bob1.learner1@example.com | Active      |
      | Bob2 Learner2   | learner2 | bob2&learner2@example.com | Active      |
      | Bob3 Learner3   | learner3 | Email is private          | Active      |
      | Bob4 Learner4   | learner4 | Email is private          | Active      |
    And I log out

    # Learner3 should be restricted to a report containing only learner1 and 2.
    When I log in as "learner3"
    And I click on "Reports" in the totara menu
    And I follow "User Report"
    Then I should see "User Report: 2 records shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              | User Status |
      | Bob1 Learner1   | learner1 | bob1.learner1@example.com | Active      |
      | Bob2 Learner2   | learner2 | bob2&learner2@example.com | Active      |

  Scenario: Verify reports extending from the user source class do not support the action column in user source report.

    When I click on "Home" in the totara menu
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Audiences Orphaned Users Report |
      | Source      | Audiences Orphaned Users        |
    And I press "Create report"
    Then I should see "Edit Report 'Audiences Orphaned Users Report'"

    When I switch to "Columns" tab
    Then I should not see "Actions" in the "newcolumns" "select"
