@totara @totara_core
Feature: Test the ability to set your own position assignments on email-based self-enrolment

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname    | lastname | email               |
      | manager  | Frederick    | Newman   | manager@example.com |
      | user1    | John         | Smith    | user1@example.com   |
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname               | idnumber  |
      | Organisation Framework | oframe    |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | fullname         | idnumber  | org_framework |
      | Organisation One | org1      | oframe        |
      | Organisation Two | org2      | oframe        |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname           | idnumber  |
      | Position Framework | pframe    |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | fullname     | idnumber  | pos_framework |
      | Position One | pos1      | pframe        |
      | Position Two | pos2      | pframe        |
    And the following job assignments exist:
      | user    | fullname        |
      | manager | General Manager |
    And I log in as "admin"
    And I navigate to "Manage authentication" node in "Site administration > Plugins > Authentication"
    And I click on "Enable" "link" in the "Email-based self-registration" "table_row"
    And I navigate to "Email-based self-registration" node in "Site administration > Plugins > Authentication"
    And I set the following fields to these values:
      | Position     | Yes |
      | Organisation | Yes |
      | Manager      | Yes |
    And I press "Save changes"
    And the following config values are set as admin:
      | registerauth    | email |
    And I log out

  @javascript
  Scenario: Testing position assignment fields on email-based self-registration
    When I press "Create new account"
    Then I should see "Position"
    And I should see "Organisation"
    And I should see "Manager"

    When I set the following fields to these values:
      | Username      | gregnick             |
      | Password      | Greg_Nick01          |
      | Email address | gregnick@example.com |
      | Email (again) | gregnick@example.com |
      | First name    | Gregory              |
      | Surname       | Nickleson            |
    And I click on "Choose position" "button"
    And I click on "Position One" "link" in the "position" "totaradialogue"
    And I click on "OK" "button" in the "position" "totaradialogue"
    And I click on "Choose organisation" "button"
    And I click on "Organisation One" "link" in the "organisation" "totaradialogue"
    And I click on "OK" "button" in the "organisation" "totaradialogue"
    And I click on "Choose manager" "button"
    And I click on "John Smith - requires job assignment entry" "link" in the "manager" "totaradialogue"
    Then I should not see "Selected:"
    When I click on "Search" "link" in the "Choose manager" "totaradialogue"
    And I search for "John" in the "Choose manager" totara dialogue
    And I click on "John Smith - requires job assignment entry" "link" in the "#search-tab" "css_element"
    Then I should not see "Selected:"
    When I click on "Browse" "link" in the "Choose manager" "totaradialogue"
    When I click on "Frederick Newman - General Manager" "link" in the "manager" "totaradialogue"
    Then I should see "Selected:"
    And I click on "OK" "button" in the "manager" "totaradialogue"
    And I press "Create my new account"
    And I press "Continue"
    And I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I set the field "User Status" to "any value"
    And I press "id_submitgroupstandard_addfilter"
    And I click on "Confirm" "link" in the "Gregory Nickleson" "table_row"
    And I click on "Gregory Nickleson" "link"
    And I follow "Unnamed job assignment (ID: 1)"
    Then I should see "Position One"
    And I should see "Organisation One"
    And I should see "Frederick Newman (manager@example.com) - General Manager"
