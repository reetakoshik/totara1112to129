@totara @totara_core @totara_program
Feature: Test program settings available for the user

  Scenario: Test that no empty setting is shown in the program administration block
    Given I am on a totara site
    And I log in as "admin"
    And the following "programs" exist in "totara_program" plugin:
      | fullname       | shortname | idnumber |
      | Test Program 1 | program1  | program1 |
    And the following "roles" exist:
      | name                | shortname   | contextlevel | reference |
      | Program coordinator | progcoord   | System       |           |
      | Program manager     | progmanager | System       |           |
      | Program lead        | proglead    | System       |           |
      | Program lead 2      | proglead2   | Program      | program1  |
    And the following "permission overrides" exist:
      | capability                      | permission | role        | contextlevel | reference |
      | totara/program:configuredetails | Allow      | progmanager | System       |           |
      | totara/program:configuredetails | Allow      | progcoord   | System       |           |
      | moodle/role:review              | Allow      | progcoord   | System       |           |
      | totara/program:configuredetails | Allow      | proglead    | System       |           |
      | moodle/role:assign              | Allow      | proglead    | System       |           |
      | totara/program:configuredetails | Allow      | proglead2   | Program      | program1  |
      | moodle/role:assign              | Allow      | proglead2   | Program      | program1  |
    And the following "users" exist:
      | username | firstname | lastname | email          |
      | user1    | User      | One      | user1@test.com |
      | user2    | User      | Two      | user2@test.com |
      | user3    | User      | Three    | user3@test.com |
      | user4    | User      | Four     | user4@test.com |
      | user5    | User      | Five     | user5@test.com |
    And the following "role assigns" exist:
      | user  | role        | contextlevel | reference |
      | user1 | manager     | System       |           |
      | user2 | progcoord   | System       |           |
      | user3 | progmanager | System       |           |
      | user4 | proglead    | System       |           |
      | user5 | proglead2   | Program      | program1  |
    And I log out

    When I log in as "user1"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Settings" "link" in the "Test Program 1" "table_row"
    Then I should see "Users" in the "Administration" "block"
    When I expand "Users" node
    Then I should see "Permissions" in the "Administration" "block"
    When I expand "Permissions" node
    Then I should see "Assigned roles" in the "Administration" "block"
    And I should see "Check permissions" in the "Administration" "block"

    When I log out
    And I log in as "user2"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Settings" "link" in the "Test Program 1" "table_row"
    Then I should see "Users" in the "Administration" "block"
    When I expand "Users" node
    Then I should see "Permissions" in the "Administration" "block"
    # Ideally you would check that permission cannot be expanded here, just checking that it is a link.
    And "Permissions" "link" should exist in the "Administration" "block"
    And I should not see "Assigned roles" in the "Administration" "block"
    And I should not see "Check permissions" in the "Administration" "block"

    When I log out
    And I log in as "user3"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Settings" "link" in the "Test Program 1" "table_row"
    Then I should not see "Users" in the "Administration" "block"
    And I should not see "Permissions" in the "Administration" "block"
    And I should not see "Assigned roles" in the "Administration" "block"
    And I should not see "Check permissions" in the "Administration" "block"

    When I log out
    And I log in as "user4"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Settings" "link" in the "Test Program 1" "table_row"
    Then I should see "Users" in the "Administration" "block"
    When I expand "Users" node
    Then I should see "Permissions" in the "Administration" "block"
    And "Permissions" "link" should not exist in the "Administration" "block"
    When I expand "Permissions" node
    Then I should see "Assigned roles" in the "Administration" "block"
    And I should see "Check permissions" in the "Administration" "block"

    When I log out
    And I log in as "user5"
    And I am on "Test Program 1" program homepage
    And I press "Edit program details"
    Then I should see "Users" in the "Administration" "block"
    When I expand "Users" node
    Then I should see "Permissions" in the "Administration" "block"
    And "Permissions" "link" should not exist in the "Administration" "block"
    When I expand "Permissions" node
    Then I should see "Assigned roles" in the "Administration" "block"
    And I should see "Check permissions" in the "Administration" "block"

    When I log out
    And I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Settings" "link" in the "Test Program 1" "table_row"
    Then I should see "Users" in the "Administration" "block"
    When I expand "Users" node
    Then I should see "Permissions" in the "Administration" "block"
    When I expand "Permissions" node
    Then I should see "Assigned roles" in the "Administration" "block"
    And I should see "Check permissions" in the "Administration" "block"
