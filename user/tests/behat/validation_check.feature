@user @auth @auth_email @javascript
Feature: Confirm validation works when a date profile field is added

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Manage authentication" node in "Site administration > Plugins > Authentication"
    And I click on "Enable" "link" in the "Email-based self-registration" "table_row"
    And I set the field "Self registration" to "Email-based self-registration"
    And I click on "Save changes" "button"
    And I navigate to "User profile fields" node in "Site administration > Users"
    And I set the field "Create a new profile field:" to "Date (no timezone)"
    And I set the following fields to these values:
        | Name                        | Date test |
        | Short name (must be unique) | dt        |
        | Is this field required?     | Yes       |
        | Display on signup page?     | Yes       |
    And I click on "Save changes" "button"
    And I log out

  Scenario: Confirm validation remains after a required date field is needed
    When I click on "Create new account" "button"
    And I start watching to see if a new page loads
    And I click on "Create my new account" "button"
    Then I should see "Missing username"
    And I should see "Missing email address"
    And a new page should not have loaded since I started watching
