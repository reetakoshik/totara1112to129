@javascript @totara_cohort @totara
Feature: Apply audience membership rules using before and after criteria for custom date field
  Create and check rules for dynamic audience that use
  Custom date field with timezones

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                | timezone            |
      | siteman1 | Mad       | Max      | mad.max@example.com  | Australia/Melbourne |
      | student1 | Sam       | Student1 | student1@example.com | Australia/Melbourne |
      | student2 | Sal       | Student2 | student2@example.com | Australia/Melbourne |
      | student3 | Sad       | Student3 | student3@example.com | Africa/Addis_Ababa  |
    And the following "system role assigns" exist:
      | user     | role    | contextlevel |
      | siteman1 | manager | System       |
    And the following "cohorts" exist:
      | name      | cohorttype | idnumber |
      | audience1 |     2      | 1        |
    # Setup non utc server time zone
    And I log in as "admin"
    And I set the following administration settings values:
      | timezone | Europe/Kiev |
    And I navigate to "User profile fields" node in "Site administration > Users > Accounts"
    And I select "Date/Time" from the "Create a new profile field" singleselect
    And I set the following fields to these values:
      | shortname  | joindate  |
      | Name       | Join Date |
      | Start year | 2016      |
      | End year   | 2016      |
    And I press "Save changes"
    And I log out

  Scenario: Set various timezones for user that setup before and after criteria and confirm that they stored correctly
    Given I log in as "siteman1"

    # Add before criteria.
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "audience1"
    And I follow "Rule sets"
    And I select "Join Date" from the "addrulesetmenu" singleselect
    And I set the following fields to these values:
      | fixedordynamic                 | 1                |
      | beforeaftermenu                | before and on    |
      | beforeafterdatetime[day]       | 20               |
      | beforeafterdatetime[month]     | 11               |
      | beforeafterdatetime[year]      | 2016             |
      | beforeafterdatetime[hour]      | 14               |
      | beforeafterdatetime[minute]    | 30               |
      | beforeafterdatetime[timezone]  | Pacific/Auckland |
    And I press "Save"

    # Check before criteria (check, load, re-save without changes, check).
    And I should see "is before 20/11/2016, 12:30"
    And I click on "Edit" "link" in the "ul.cohort-editing_ruleset" "css_element"
    And I press "Save"
    And I should see "is before 20/11/2016, 12:30"

    # Change user default time zone.
    And I follow "Preferences" in the user menu
    And I follow "Edit profile"
    And I set the following fields to these values:
      | Timezone | America/Los_Angeles |
    And I press "Update profile"

    # Check before criteria was not changed.
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "audience1"
    And I follow "Rule sets"
    And I should see "is before 19/11/2016, 17:30"

    # Change default time zone.
    And I log out
    And I log in as "admin"
    And I set the following administration settings values:
      | timezone | Asia/Dubai |
    And I log out
    And I log in as "siteman1"

    # Check before criteria is still set in user default time zone.
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "audience1"
    When I follow "Rule sets"
    Then I should see "is before 19/11/2016, 17:30"

  Scenario: Check custom fields dates of before and after criteria rule
    Given I log in as "siteman1"

    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "audience1"
    And I follow "Rule sets"
    And I select "Join Date" from the "addrulesetmenu" singleselect
    And I set the following fields to these values:
      | fixedordynamic                 | 1                   |
      | beforeaftermenu                | before and on       |
      | beforeafterdatetime[day]       | 20                  |
      | beforeafterdatetime[month]     | November            |
      | beforeafterdatetime[year]      | 2016                |
      | beforeafterdatetime[hour]      | 00                  |
      | beforeafterdatetime[minute]    | 00                  |
      | beforeafterdatetime[timezone]  | Australia/Melbourne |
    And I press "Save"
    And I press "Approve changes"

    # User on the same date as "before and on" should be included (In T10 this should be changed).
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Sam Student1"
    And I follow "Edit profile"
    And I expand all fieldsets
    And I set the following fields to these values:
      | profile_field_joindate[enabled] | 1        |
      | profile_field_joindate[day]     | 20       |
      | profile_field_joindate[month]   | November |
      | profile_field_joindate[year]    | 2016     |
    And I press "Update profile"

    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Sal Student2"
    And I follow "Edit profile"
    And I expand all fieldsets
    And I set the following fields to these values:
      | profile_field_joindate[enabled] | 1        |
      | profile_field_joindate[day]     | 20       |
      | profile_field_joindate[month]   | November |
      | profile_field_joindate[year]    | 2016     |
    And I press "Update profile"

    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Sad Student3"
    And I follow "Edit profile"
    And I expand all fieldsets
    And I set the following fields to these values:
      | profile_field_joindate[enabled] | 1        |
      | profile_field_joindate[day]     | 19       |
      | profile_field_joindate[month]   | November |
      | profile_field_joindate[year]    | 2016     |
    And I press "Update profile"

    And I run the scheduled task "\totara_cohort\task\update_cohort_task"
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "audience1"
    And I follow "Members"
    And I should see "Sam Student1"
    And I should see "Sal Student2"
    And I should see "Sad Student3"

    # Enable time, change to 00:05 and user should not be included anymore.
    And I log out
    And I log in as "admin"
    And I navigate to "User profile fields" node in "Site administration > Users > Accounts"
    And I click on "Edit" "link" in the "Join Date" "table_row"
    And I set the following fields to these values:
      | Include time? | 1 |
    And I press "Save changes"
    And I log out
    And I log in as "siteman1"

    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Sam Student1"
    And I follow "Edit profile"
    And I expand all fieldsets
    And I set the following fields to these values:
      | profile_field_joindate[hour]   | 00 |
      | profile_field_joindate[minute] | 05 |
    And I press "Update profile"

    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Sad Student3"
    And I follow "Edit profile"
    And I expand all fieldsets
    And I set the following fields to these values:
      | profile_field_joindate[hour]   | 23 |
      | profile_field_joindate[minute] | 55 |
    And I press "Update profile"

    And I run the scheduled task "\totara_cohort\task\update_cohort_task"
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "audience1"
    And I follow "Members"
    And I should not see "Sam Student1"
    And I should see "Sal Student2"
    And I should see "Sad Student3"

    # Users "on and after" should be included on date and after.
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "audience1"
    And I follow "Rule sets"
    And I click on "Edit" "link" in the "ul.cohort-editing_ruleset" "css_element"

    And I set the following fields to these values:
      | fixedordynamic                 | 1                   |
      | beforeaftermenu                | on and after        |
      | beforeafterdatetime[day]       | 20                  |
      | beforeafterdatetime[month]     | November            |
      | beforeafterdatetime[year]      | 2016                |
      | beforeafterdatetime[hour]      | 00                  |
      | beforeafterdatetime[minute]    | 00                  |
      | beforeafterdatetime[timezone]  | Australia/Melbourne |
    And I press "Save"
    And I press "Approve changes"
    When I follow "Members"
    Then I should see "Sam Student1"
    And I should see "Sal Student2"
    And I should not see "Sad Student3"