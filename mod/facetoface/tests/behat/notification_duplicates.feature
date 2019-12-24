@javascript @mod @mod_facetoface @totara
Feature: Check seminar notification duplicates recovery functionality
  In order to fix problem with duplicated seminar notifications
  As an admin
  I need to be informed about and be able to remove duplicates from seminar events

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    When I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I click on "Test seminar name" "link"
    And I follow "Add a new event"
    And I press "Save changes"

  Scenario: Check that duplicates are detected and can be removed
    Given I reload the page
    And I should see "All events in Test seminar name"
    And I should not see "Duplicates of auto notifications found"
    When I make duplicates of seminar notification "Seminar booking cancellation"
    And I reload the page
    Then I should see "Duplicates of auto notifications found"
    And I navigate to "Notifications" node in "Seminar administration"
    And I should see "Duplicates of auto notifications found"

    # Remove duplicate
    When I click on "Delete" "link" in the "Seminar booking cancellation" "table_row"
    And I press "Continue"
    Then I should not see "Duplicates of auto notifications found"
    And I should see "Seminar booking cancellation"
    And I should not see "Delete" in the "Seminar booking cancellation" "table_row"
    And I click on "Test seminar name" "link"
    And I should see "All events in Test seminar name"
    And I should not see "Duplicates of auto notifications found"


