@totara @totara_reportbuilder @javascript
Feature: Verify the User System Role filter.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Bob1      | Learner1 | learner1@example.com |
      | learner2 | Bob2      | Learner2 | learner2@example.com |
      | learner3 | Bob3      | Learner3 | learner3@example.com |
      | learner4 | Bob4      | Learner4 | learner4@example.com |
      | manager1 | Dave1     | Manager1 | manager1@example.com |
    And the following job assignments exist:
      | user     | manager  |
      | learner1 | manager1 |
      | learner2 | manager1 |
      | learner3 | manager1 |
      | learner4 | manager1 |
    When I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | My User Report |
      | Source      | User           |
    And I press "Create report"
    Then I should see "Edit Report 'My User Report'"

    When I switch to "Filters" tab
    And I set the field "newstandardfilter" to "User System Role"
    And I press "Save changes"
    Then I should see "Filters updated"

    When I navigate to "Assign system roles" node in "Site administration > Permissions"
    And I follow "Site Manager"
    And I set the field "Potential users" to "Bob1 Learner1 (learner1@example.com)"
    And I press "Add"
    Then I should see "Bob1 Learner1 (learner1@example.com)" in the "#removeselect" "css_element"

    When I set the field "Assign another role" to "Course creator (0)"
    And I set the field "Potential users" to "Bob2 Learner2 (learner2@example.com)"
    And I press "Add"
    And I set the field "Potential users" to "Bob3 Learner3 (learner3@example.com)"
    And I press "Add"
    Then I should see "Bob2 Learner2 (learner2@example.com)" in the "#removeselect" "css_element"
    And I should see "Bob3 Learner3 (learner3@example.com)" in the "#removeselect" "css_element"

    When I set the field "Assign another role" to "Staff Manager (0)"
    And I set the field "Potential users" to "Bob3 Learner3 (learner3@example.com)"
    And I press "Add"
    And I set the field "Potential users" to "Bob4 Learner4 (learner4@example.com)"
    And I press "Add"
    Then I should see "Bob3 Learner3 (learner3@example.com)" in the "#removeselect" "css_element"
    And I should see "Bob4 Learner4 (learner4@example.com)" in the "#removeselect" "css_element"

  Scenario: Verify User System User filter with no role selected returns no result.

    Given I navigate to "Manage user reports" node in "Site administration > Reports"
    And I click on "View" "link" in the "User" "table_row"

    When I click on "Assigned" "radio"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "My User Report: 7 records shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username |
      | Guest user      | guest    |
      | Admin User      | admin    |
      | Bob1 Learner1   | learner1 |
      | Bob2 Learner2   | learner2 |
      | Bob3 Learner3   | learner3 |
      | Bob4 Learner4   | learner4 |
      | Dave1 Manager1  | manager1 |

    When I click on "Not assigned" "radio"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "My User Report: 7 records shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username |
      | Guest user      | guest    |
      | Admin User      | admin    |
      | Bob1 Learner1   | learner1 |
      | Bob2 Learner2   | learner2 |
      | Bob3 Learner3   | learner3 |
      | Bob4 Learner4   | learner4 |
      | Dave1 Manager1  | manager1 |

    When I press "Save this search"
    Then I should see "Create a saved search"
    And I should see "No role selected"

    When I set the field "Search Name" to "No role selected"
    And I press "Save changes"
    Then I should see "My User Report: 7 records shown"
    And I should see "No role selected" in the "sid" "select"

  Scenario: Verify User System User filter with 'any role' option selected.

    Given I navigate to "Manage user reports" node in "Site administration > Reports"
    And I click on "View" "link" in the "User" "table_row"

    When I click on "Assigned" "radio"
    And I set the field "user-roleid" to "Any role"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "My User Report: 4 records shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username |
      | Bob1 Learner1   | learner1 |
      | Bob2 Learner2   | learner2 |
      | Bob3 Learner3   | learner3 |
      | Bob4 Learner4   | learner4 |
    And the "reportbuilder-table" table should not contain the following:
      | User's Fullname | Username |
      | Guest user      | guest    |
      | Admin User      | admin    |
      | Dave1 Manager1  | manager1 |

    When I press "Save this search"
    Then I should see "Create a saved search"
    And I should see "Assigned any role"

    When I set the field "Search Name" to "Assigned any role"
    And I press "Save changes"
    Then I should see "My User Report: 4 records shown"
    And I should see "Assigned any role" in the "sid" "select"

    When I click on "Not assigned" "radio"
    And I set the field "user-roleid" to "Any role"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "My User Report: 3 records shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username |
      | Guest user      | guest    |
      | Admin User      | admin    |
      | Dave1 Manager1  | manager1 |
    And the "reportbuilder-table" table should not contain the following:
      | Bob1 Learner1   | learner1 |
      | Bob2 Learner2   | learner2 |
      | Bob3 Learner3   | learner3 |
      | Bob4 Learner4   | learner4 |

    When I press "Save this search"
    Then I should see "Create a saved search"
    And I should see "Not assigned any role"

    When I set the field "Search Name" to "Not assigned any role"
    And I press "Save changes"
    Then I should see "My User Report: 3 records shown"
    And I should see "Not assigned any role" in the "sid" "select"

  Scenario: Verify User System Role filter with 'assigned' role works.

    Given I navigate to "Manage user reports" node in "Site administration > Reports"
    And I click on "View" "link" in the "User" "table_row"

    # Check the Site Manager search result.
    When I click on "Assigned" "radio"
    And I set the field "user-roleid" to "Site Manager"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "My User Report: 1 record shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username |
      | Bob1 Learner1   | learner1 |
    And the "reportbuilder-table" table should not contain the following:
      | User's Fullname | Username |
      | Guest user      | guest    |
      | Admin User      | admin    |
      | Bob2 Learner2   | learner2 |
      | Bob3 Learner3   | learner3 |
      | Bob4 Learner4   | learner4 |
      | Dave1 Manager1  | manager1 |

    # Check the Course Creator search result.
    When I set the field "user-roleid" to "Course creator"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "My User Report: 2 records shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username |
      | Bob2 Learner2   | learner2 |
      | Bob3 Learner3   | learner3 |
    And the "reportbuilder-table" table should not contain the following:
      | User's Fullname | Username |
      | Guest user      | guest    |
      | Admin User      | admin    |
      | Bob1 Learner1   | learner1 |
      | Bob4 Learner4   | learner4 |
      | Dave1 Manager1  | manager1 |

    # Check the Staff Manager search result.
    When I set the field "user-roleid" to "Staff Manager"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "My User Report: 2 records shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username |
      | Bob3 Learner3   | learner3 |
      | Bob4 Learner4   | learner4 |
    And the "reportbuilder-table" table should not contain the following:
      | User's Fullname | Username |
      | Guest user      | guest    |
      | Admin User      | admin    |
      | Bob1 Learner1   | learner1 |
      | Bob2 Learner2   | learner2 |
      | Dave1 Manager1  | manager1 |

    When I press "Save this search"
    Then I should see "Create a saved search"
    And I should see "Assigned role 'Staff Manager'"

    When I set the field "Search Name" to "Assigned role 'Staff Manager'"
    And I press "Save changes"
    Then I should see "My User Report: 2 records shown"
    And I should see "Assigned role 'Staff Manager'" in the "sid" "select"

  Scenario: Verify User System Role filter with 'not assigned' role works.

    Given I navigate to "Manage user reports" node in "Site administration > Reports"
    And I click on "View" "link" in the "User" "table_row"

    # Check the Site Manager search result.
    When I click on "Not assigned" "radio"
    And I set the field "user-roleid" to "Site Manager"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "My User Report: 6 records shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username |
      | Guest user      | guest    |
      | Admin User      | admin    |
      | Bob2 Learner2   | learner2 |
      | Bob3 Learner3   | learner3 |
      | Bob4 Learner4   | learner4 |
      | Dave1 Manager1  | manager1 |
    And the "reportbuilder-table" table should not contain the following:
      | User's Fullname | Username |
      | Bob1 Learner1   | learner1 |

    # Check the Course Creator search result.
    When I set the field "user-roleid" to "Course creator"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "My User Report: 5 records shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username |
      | Guest user      | guest    |
      | Admin User      | admin    |
      | Bob1 Learner1   | learner1 |
      | Bob4 Learner4   | learner4 |
      | Dave1 Manager1  | manager1 |
    And the "reportbuilder-table" table should not contain the following:
      | User's Fullname | Username |
      | Bob2 Learner2   | learner2 |
      | Bob3 Learner3   | learner3 |

    # Check the Staff Manager search result.
    When I set the field "user-roleid" to "Staff Manager"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "My User Report: 5 records shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username |
      | Guest user      | guest    |
      | Admin User      | admin    |
      | Bob1 Learner1   | learner1 |
      | Bob2 Learner2   | learner2 |
      | Dave1 Manager1  | manager1 |
    And the "reportbuilder-table" table should not contain the following:
      | User's Fullname | Username |
      | Bob3 Learner3   | learner3 |
      | Bob4 Learner4   | learner4 |

    When I press "Save this search"
    Then I should see "Create a saved search"
    And I should see "Not assigned role 'Staff Manager'"

    When I set the field "Search Name" to "Not assigned role 'Staff Manager'"
    And I press "Save changes"
    Then I should see "My User Report: 5 records shown"
    And I should see "Not assigned role 'Staff Manager'" in the "sid" "select"

  Scenario: Verify User System Role filter can be used in embedded reports using the user source.

    # Check you can't add the filter to the alerts report.
    Given I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I click on "Settings" "link" in the "Alerts" "table_row"
    And I switch to "Filters" tab
    Then I should not see "User System Role" in the "newstandardfilter" "select"

    # Add the filter to the Browse List of Users embedded report.
    When I follow "All embedded reports"
    And I click on "Settings" "link" in the "Browse list of users" "table_row"
    And I switch to "Filters" tab
    And I set the field "newstandardfilter" to "User System Role"
    And I press "Save changes"
    Then I should see "Filters updated"

    # Check the system roles are present in the filter menu.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    Then I should see "Site Manager" in the "user-roleid" "select"
    And I should see "Course creator" in the "user-roleid" "select"
    And I should see "Staff Manager" in the "user-roleid" "select"

    # Do a simple test of the filter to prove it's present and working.
    When I click on "Assigned" "radio"
    And I set the field "user-roleid" to "Any role"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "Browse list of users: 4 records shown"
    And the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username |
      | Bob1 Learner1   | learner1 |
      | Bob2 Learner2   | learner2 |
      | Bob3 Learner3   | learner3 |
      | Bob4 Learner4   | learner4 |
    And the "reportbuilder-table" table should not contain the following:
      | User's Fullname | Username |
      | Guest user      | guest    |
      | Admin User      | admin    |
      | Dave1 Manager1  | manager1 |

    # Add teh filter to the team members embedded report.
    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Team"
    And I press "id_submitgroupstandard_addfilter"
    And I click on "Settings" "link" in the "Team Members" "table_row"

    When I switch to "Filters" tab
    And I set the field "newstandardfilter" to "User System Role"
    And I press "Save changes"
    Then I should see "Filters updated"
    And I log out

    # Login as the manager to view the Team members embedded report
    # and check the system roles are present in the filter menu.
    When I log in as "manager1"
    And I click on "Team" in the totara menu
    Then I should see "Site Manager" in the "user-roleid" "select"
    And I should see "Course creator" in the "user-roleid" "select"
    And I should see "Staff Manager" in the "user-roleid" "select"

    # Do a simple test of the filter to prove it's present and working.
    When I click on "Assigned" "radio"
    And I set the field "user-roleid" to "Site Manager"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "Team Members: 1 record shown"
    And the "reportbuilder-table" table should contain the following:
      | Name          | Last Login |
      | Bob1 Learner1 |            |
    And the "reportbuilder-table" table should not contain the following:
      | Name          | Last Login |
      | Bob2 Learner2 |            |
      | Bob3 Learner3 |            |
      | Bob4 Learner4 |            |
