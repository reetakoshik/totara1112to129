@core @core_admin @javascript
Feature: Verify functionality of user report.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                     | maildisplay |
      | learner1 | Bob1      | Learner1 | bob1.learner1@example.com | 0           |

    And I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"

  Scenario: Verify expected users are in user report
    Then I should see "Browse list of users: 3 records shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              | User Status | Last Login                   |
      | Guest user      | guest    | root@localhost            | Active      |                              |
      | Admin User      | admin    | moodle@example.com        | Active      | Within the last five minutes |
      | Bob1 Learner1   | learner1 | bob1.learner1@example.com | Active      |                              |

  Scenario: Verify saved search features are not available in user report.

    When I set the field "user-fullname" to "Bob"
    # Press Search button.
    And I click on "#id_submitgroupstandard_addfilter" "css_element"
    Then I should see "Browse list of users: 1 record shown"
    And "Save this search" "button" should not exist

  Scenario: Verify column sorting in user report.

    When I follow "User's Fullname"
    Then the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              |
      | Admin User      | admin	   | moodle@example.com        |
      | Bob1 Learner1	| learner1 | bob1.learner1@example.com |
      | Guest user      | guest    | root@localhost            |

    When I follow "User's Fullname"
    Then the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              |
      | Guest user      | guest    | root@localhost            |
      | Bob1 Learner1	| learner1 | bob1.learner1@example.com |
      | Admin User      | admin	   | moodle@example.com        |

    When I follow "User's Email"
    Then the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              |
      | Bob1 Learner1	| learner1 | bob1.learner1@example.com |
      | Admin User      | admin	   | moodle@example.com        |
      | Guest user      | guest    | root@localhost            |

    When I follow "Last Login"
    And I follow "Last Login"
    Then the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              | Last Login                   |
      | Admin User      | admin	   | moodle@example.com        | Within the last five minutes |
      | Bob1 Learner1	| learner1 | bob1.learner1@example.com |                              |
      | Guest user      | guest    | root@localhost            |                              |

  Scenario: Verify adding a new user from user report.

    Given I press "Add a new user"
    Then I should see "Add a new user"

    When I set the following fields to these values:
      | Username      | learner2                  |
      | New password  | P4ssword!                 |
      | First name    | Bob2                      |
      | Surname       | Learner4                  |
      | Email address | bob2.learner2@example.com |
    And I press "Create user"
    Then I should see "Browse list of users: 4 records shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              |
      | Bob2 Learner4   | learner2 | bob2.learner2@example.com |

  Scenario: Verify editing user record in user report.

    Given I follow "Edit Bob1 Learner1"
    When I set the field "First name" to "Sir Bob1"
    And I press "Update profile"
    Then the "reportbuilder-table" table should contain the following:
      | User's Fullname   | Username | User's Email              |
      | Sir Bob1 Learner1 | learner1 | bob1.learner1@example.com |

  Scenario: Verify suspend and unsuspend of user in user report.

    Given I follow "Suspend Bob1 Learner1"
    And I set the field "user-deleted" to "any value"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              | User Status |
      | Bob1 Learner1   | learner1 | bob1.learner1@example.com | Suspended   |

    When I follow "Unsuspend Bob1 Learner1"
    Then the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              | User Status |
      | Bob1 Learner1   | learner1 | bob1.learner1@example.com | Active      |

  Scenario: Verify delete of user in user report.

    Given the following config values are set as admin:
      | authdeleteusers | full |
    When I follow "Delete Bob1 Learner1"
    Then I should see "Delete user"

    When I press "Delete"
    Then I should see "Browse list of users: 2 records shown"
    And I set the field "user-deleted" to "any value"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I should not see "Bob1 Learner1"

  Scenario: Verify confirm new self-registration user in user report.

    Given I navigate to "Manage authentication" node in "Site administration > Plugins > Authentication"
    When I click on "Enable" "link" in the "Email-based self-registration" "table_row"
    And the following config values are set as admin:
      | registerauth | email |
    And I log out
    Then I should see "Is this your first time here?"

    When I press "Create new account"
    Then I should see "New account"

    When I set the following fields to these values:
      | Username      | learner2                  |
      | Password      | P4ssword!                 |
      | First name    | Bob2                      |
      | Surname       | Learner2                  |
      | Email address | bob2.learner2@example.com |
      | Email (again) | bob2.learner2@example.com |
    And I press "Create my new account"
    Then I should see "An email should have been sent to your address at bob2.learner2@example.com"

    When I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I set the field "user-deleted" to "any value"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              | User Status |
      | Bob2 Learner2   | learner2 | bob2.learner2@example.com | Unconfirmed |

    When I follow "Confirm Bob2 Learner2"
    Then I should not see "Confirm Bob2 Learner2"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              | User Status |
      | Bob2 Learner2   | learner2 | bob2.learner2@example.com | Active      |

  Scenario: Verify unlock of user account in user report.

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
    And I navigate to "Browse list of users" node in "Site administration > Users"
    Then the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              | User Status |
      | Bob1 Learner1   | learner1 | bob1.learner1@example.com | Active      |

    When I follow "Unlock Bob1 Learner1"
    Then I should not see "Unlock Bob1 Learner1"
    And I log out

    # Login successfully after being locked out.
    When I log in as "learner1"
    Then I should see "Bob1 Learner1" in the "nav" "css_element"

  Scenario: Verify Global Report Restrictions works on the report in user report.

    Given the following "users" exist:
      | username | firstname | lastname | email                     |
      | learner2 | Bob2      | Learner2 | bob2.learner2@example.com |
      | learner3 | Bob3      | Learner3 | bob3.learner3@example.com |
      | learner4 | Bob4      | Learner4 | bob4.learner4@example.com |
    And the following "cohorts" exist:
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

    When I navigate to "Assign system roles" node in "Site administration > Permissions"
    And I follow "Site Manager"
    And I set the field "Potential users" to "Bob1 Learner1 (bob1.learner1@example.com)"
    And I press "Add"
    And I set the field "Potential users" to "Bob3 Learner3 (bob3.learner3@example.com)"
    And I press "Add"
    Then I should see "Bob1 Learner1 (bob1.learner1@example.com)" in the "#removeselect" "css_element"
    And I should see "Bob3 Learner3 (bob3.learner3@example.com)" in the "#removeselect" "css_element"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I press "Edit this report"
    Then I should see "Edit Report 'Browse list of users'"

    When I switch to "Content" tab
    And I set the field "Global report restrictions" to "1"
    And I press "Save changes"
    Then I should see "Report Updated"

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
      | Bob1 Learner1 | Audience Audience 1 |
      | Bob2 Learner2 | Audience Audience 1 |

    When I switch to "Users allowed to select restriction" tab
    And I set the field "menugroupselector" to "Audience"
    And I click on "Audience 2" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    Then the following should exist in the "datatable" table:
      | Learner       | Assigned Via        |
      | Bob3 Learner3 | Audience Audience 2 |
      | Bob4 Learner4 | Audience Audience 2 |
    And I log out

    # Learner1 should not have any restrictions on what data it can see.
    When I log in as "learner1"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    Then I should see "Browse list of users: 6 records shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              | User Status |
      | Bob1 Learner1   | learner1 | bob1.learner1@example.com | Active      |
      | Bob2 Learner2   | learner2 | bob2.learner2@example.com | Active      |
      | Bob3 Learner3   | learner3 | bob3.learner3@example.com | Active      |
      | Bob4 Learner4   | learner4 | bob4.learner4@example.com | Active      |
    And I log out

    # Learner3 should be restricted to a report containing only learner1 and 2.
    When I log in as "learner3"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    Then I should see "Browse list of users: 2 records shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email              | User Status |
      | Bob1 Learner1   | learner1 | bob1.learner1@example.com | Active      |
      | Bob2 Learner2   | learner2 | bob2.learner2@example.com | Active      |
    And the following should not exist in the "reportbuilder-table" table:
      | User's Fullname | Username | User's Email              | User Status |
      | Bob3 Learner3   | learner3 | bob3.learner3@example.com | Active      |
      | Bob4 Learner4   | learner4 | bob4.learner4@example.com | Active      |
