@totara_cohort @totara
Feature: Apply audience membership rules using custom date field
  Create rules for dynamic audience that use
  Custom date no timezone field

  Background:
    Given I am on a totara site
    And the following "cohorts" exist:
      | name      | cohorttype | idnumber |
      | audience1 |     2      | 1        |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | user1     | one      | user1@example.com |
      | user2    | user2     | two      | user2@example.com |
      | user3    | user3     | three    | user3@example.com |
    And I log in as "admin"
    And I navigate to "User profile fields" node in "Site administration > Users"
    And I select "Date (no timezone)" from the "Create a new profile field" singleselect
    And I set the following fields to these values:
      | shortname | joindate  |
      | Name      | Join Date |
    And I press "Save changes"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Edit" "link" in the "user1" "table_row"
    And I set the following fields to these values:
      | profile_field_joindate[enabled] | 1    |
      | profile_field_joindate[day]     | 21   |
      | profile_field_joindate[month]   | July |
      | profile_field_joindate[year]    | 2015 |
    And I press "Update profile"
    And I click on "Edit" "link" in the "user2" "table_row"
    And I set the following fields to these values:
      | profile_field_joindate[enabled] | 1    |
      | profile_field_joindate[day]     | 22   |
      | profile_field_joindate[month]   | July |
      | profile_field_joindate[year]    | 2015 |
    And I press "Update profile"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "audience1"
    And I follow "Rule sets"

  @javascript
  Scenario: Add before fixed date rule
    And I select "Join Date" from the "addrulesetmenu" singleselect
    And I set the following fields to these values:
      | beforeaftermenu  | before     |
      | beforeafterdate  | 22/07/2015 |
    And I press "Save"
    # Sometimes the Calendar will cover the Approve changes button causing a failure in chrome
    # The small pause confirms that the button is no longer covered
    And I wait "1" seconds
    And I press "Approve changes"
    And I follow "Members"
    Then I should see "user1"
    And I should not see "user2"
    And I should not see "user3"

  @javascript
  Scenario: Add after fixed date rule
    And I select "Join Date" from the "addrulesetmenu" singleselect
    And I set the following fields to these values:
      | beforeaftermenu  | after      |
      | beforeafterdate  | 21/07/2015 |
    And I press "Save"
    And I press "Approve changes"
    And I follow "Members"
    Then I should not see "user1"
    And I should see "user2"
    And I should not see "user3"
