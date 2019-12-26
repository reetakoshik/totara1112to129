@mod @mod_facetoface @totara @totara_reportbuilder @javascript
Feature: Verify that link to approval requests work

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | usertest | user      | test     |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1  | c1       | 0        |

  Scenario: Confirm that the link directs to the approval page
    Given I am on a totara site
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name | Seminar1 |
    And I turn editing mode off
    And I follow "Seminar1"
    And I follow "Add a new event"
    And I click on "Save changes" "button"
    And I follow "Attendees"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "user test"
    And I click on "Add" "button"
    And I click on "Continue" "button"
    And I click on "Confirm" "button"
    And I navigate to "Reports > Manage user reports" in site administration
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Report 1            |
      | Source      | facetoface_sessions |
    And I click on "Create report" "button"
    And I follow "View"
    Then I should see "user test"
    And I should see "Link to approval requests"
    And I follow "Manage approval"
    Then I should see "Seminar1"
    And I should see "No pending approvals"
