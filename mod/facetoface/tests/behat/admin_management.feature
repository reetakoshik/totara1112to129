@javascript @mod @mod_facetoface @totara
Feature: Add and remove seminar Administrators
  In order to add and remove seminar adminstrators
  I need to be able to add and remove administrators to a seminar activity

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email              |
      | admin1   | Admin     | One      | admin1@example.com |
      | admin2   | Admin     | Two      | admin2@example.com |
      | admin3   | Admin     | Three    | admin3@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | course 1 | c1        |
    And I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "Manager and Administrative approval" "text"
    And I click on "Save changes" "button"
    And I am on "course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1"
    And I expand all fieldsets

  Scenario: Seminar - Add and remove administrators
    Given I set the following fields to these values:
      | Name                                | test seminar |
      | Manager and Administrative approval | 1            |

    # Test I can open the dialog and close it without selecting anyone
    When I click on "Add approver" "button"
    And I click on "Save" "button" in the "Select activity level approvers" "totaradialogue"
    Then I should not see "Admin One (activity level approver)" in the "Sign-up Workflow" "fieldset"
    And I should not see "Admin Two (activity level approver)" in the "Sign-up Workflow" "fieldset"
    And I should not see "Admin Three (activity level approver)" in the "Sign-up Workflow" "fieldset"

    # Select admin one as our starting point now.
    When I click on "Add approver" "button"
    And I click on "Admin One (admin1@example.com)" "link" in the "Select activity level approvers" "totaradialogue"
    And I click on "Save" "button" in the "Select activity level approvers" "totaradialogue"
    And I click on "Save and display" "button"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I expand all fieldsets
    Then I should see "Admin One (activity level approver)" in the "Sign-up Workflow" "fieldset"
    And I should not see "Admin Two (activity level approver)" in the "Sign-up Workflow" "fieldset"
    And I should not see "Admin Three (activity level approver)" in the "Sign-up Workflow" "fieldset"

    # Remove an admin
    When I click on "Remove" "link" in the "Sign-up Workflow" "fieldset"
    Then I should not see "Admin One (activity level approver)" in the "Sign-up Workflow" "fieldset"
    And I should not see "Admin Two (activity level approver)" in the "Sign-up Workflow" "fieldset"
    And I should not see "Admin Three (activity level approver)" in the "Sign-up Workflow" "fieldset"

    # Add an admin
    When I click on "Add approver" "button"
    And I click on "Admin Two (admin2@example.com)" "link" in the "Select activity level approvers" "totaradialogue"
    And I click on "Save" "button" in the "Select activity level approvers" "totaradialogue"
    Then I should not see "Admin One (activity level approver)" in the "Sign-up Workflow" "fieldset"
    And I should see "Admin Two (activity level approver)" in the "Sign-up Workflow" "fieldset"
    And I should not see "Admin Three (activity level approver)" in the "Sign-up Workflow" "fieldset"

    # Confirm that it is displayed correctly after a save
    When I click on "Save and display" "button"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I expand all fieldsets
    Then I should not see "Admin One (activity level approver)" in the "Sign-up Workflow" "fieldset"
    And I should see "Admin Two (activity level approver)" in the "Sign-up Workflow" "fieldset"
    And I should not see "Admin Three (activity level approver)" in the "Sign-up Workflow" "fieldset"

  Scenario: Seminar - enable/disable add approver button depending on whether approval option is on.
    When I set the following fields to these values:
      | Name        | test seminar |
      | No Approval | 1            |
    Then the "Add approver" "button" should be disabled

    When I set the following fields to these values:
      | Manager and Administrative approval | 1            |
    Then the "Add approver" "button" should be enabled

    When I set the following fields to these values:
      | Manager Approval | 1            |
    Then the "Add approver" "button" should be disabled
