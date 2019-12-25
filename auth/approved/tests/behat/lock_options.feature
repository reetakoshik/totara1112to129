@totara @auth @auth_approved @javascript
Feature: Profile option locking of approved user accounts

  Scenario: Lock profile options of approved user accounts
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname  | email                 | auth     | city         | institution | department |
      | user1     | First     | User      | user1@example.com     | approved | Christchurch | Spolecnost  | Oddeleni   |
      | user2     | Second    | User      | user2@example.com     | approved |              |             |            |
      | user3     | Third     | User      | user3@example.com     | approved |              |             |            |
    And I log in as "admin"
    And I navigate to "Manage authentication" node in "Site administration > Plugins > Authentication"
    And I click on "Enable" "link" in the "Self-registration with approval" "table_row"
    And I navigate to "Settings" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    And I set the following fields to these values:
      | Lock value (City/town)   | Unlocked if empty |
      | Lock value (Institution) | Unlocked          |
      | Lock value (Department)  | Locked            |
    And I press "Save changes"
    And I log out

    When I log in as "user1"
    And I follow "Profile" in the user menu
    And I follow "Edit profile"
    And I expand all fieldsets
    And the "City/town" "field" should be readonly
    And the "Department" "field" should be readonly
    And I set the field "Institution" to "University"
    And I press "Update profile"
    And I follow "Edit profile"
    Then the field "City/town" matches value "Christchurch"
    And the field "Department" matches value "Oddeleni"
    And the field "Institution" matches value "University"
    And the "City/town" "field" should be readonly
    And the "Department" "field" should be readonly
    And I press "Cancel"
    And I log out

    When I log in as "user2"
    And I follow "Profile" in the user menu
    And I follow "Edit profile"
    And I expand all fieldsets
    And the "Department" "field" should be readonly
    And I set the field "City" to "Wellington"
    And I set the field "Institution" to "Skola"
    And I press "Update profile"
    And I follow "Edit profile"
    Then the field "City/town" matches value "Wellington"
    And the field "Department" matches value ""
    And the field "Institution" matches value "Skola"
    And the "City/town" "field" should be readonly
    And the "Department" "field" should be readonly
    And I press "Cancel"
    And I log out

    When I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Third User"
    And I follow "Edit profile"
    And I expand all fieldsets
    And I set the field "City" to "Auckland"
    And I set the field "Institution" to "Skolka"
    And I set the field "Department" to "Jidelna"
    And I press "Update profile"
    And I follow "Edit profile"
    Then the field "City/town" matches value "Auckland"
    And the field "Institution" matches value "Skolka"
    And the field "Department" matches value "Jidelna"
    And I press "Cancel"
    And I log out
