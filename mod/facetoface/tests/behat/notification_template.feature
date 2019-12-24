@mod @mod_facetoface @totara
Feature: Edit seminar notification templates
  In order to update seminar notifications across the site
  As admin
  I should be able to edit notification templates

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And I log in as "admin"

  Scenario: Check that the correct seminar notification templates have been installed
    # Check the templates and ensure that there is at least a key line present from the body of the notification.
    # Also check which room placeholders are present as indicator that it is the 9.0+ version of the notification installed.
    When I navigate to "Notification templates" node in "Site administration > Seminars"
    And I click on "Edit" "link" in the "All reservations cancelled" "table_row"
    Then I should see "This is to advise you that all unallocated reservations for the following course have been automatically cancelled"
    And I should see "Room: [session:room:name]"
    And I should not see "Room: [session:room]"

    When I navigate to "Notification templates" node in "Site administration > Seminars"
    And I click on "Edit" "link" in the "Seminar booking admin request" "table_row"
    Then I should see "Your request to book into the following course has been sent to the sessions approvers"
    And I should see "Room: [session:room:name]"
    And I should not see "Room: [session:room]"

    When I navigate to "Notification templates" node in "Site administration > Seminars"
    And I click on "Edit" "link" in the "Seminar booking cancellation" "table_row"
    Then I should see "This is to advise that your booking on the following course has been cancelled"
    And I should see "Room: [session:room:name]"
    And I should not see "Room: [session:room]"

    When I navigate to "Notification templates" node in "Site administration > Seminars"
    And I click on "Edit" "link" in the "Seminar booking confirmation" "table_row"
    Then I should see "This is to confirm that you are now booked on the following course"
    And I should see "Room: [session:room:name]"
    And I should not see "Room: [session:room]"

  @javascript
  Scenario: Old placeholders are identified in seminar notification templates
    When I navigate to "Notification templates" node in "Site administration > Seminars"
    Then I should not see "Some templates contain deprecated placeholders. Please review the templates marked with a warning icon and update where necessary."
    When I click on "Edit" "link" in the "Seminar booking decline" "table_row"
    And I set the following fields to these values:
      | Body | This uses the an old placeholder, [session:venue], hopefully there'll be a warning about this |
    And I press "Save changes"
    Then I should see "Some templates contain deprecated placeholders. Please review the templates marked with a warning icon and update where necessary."

  @javascript
  Scenario: Updating templates can be set to update the notifications in a seminar activity
    When I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Test seminar name        |
      | Description       | Test seminar description |
    And I follow "Test seminar name"
    And I navigate to "Notifications" node in "Seminar administration"
    # Below is a quick check in case any new types of notifications are not being added properly.
    Then I should not see "Click here to restore missing default notifications"
    When I click on "Edit" "link" in the "Seminar registration closed" "table_row"
    Then the following fields match these values:
      | Template | Seminar registration closed: [facetofacename], [starttime]-[finishtime], [sessiondate] |
      | Title    | Seminar registration closed: [facetofacename], [starttime]-[finishtime], [sessiondate] |
    And I should see "The registration period for the following session has been closed:"
    When I navigate to "Notification templates" node in "Site administration > Seminars"
    And I click on "Edit" "link" in the "Seminar registration closed" "table_row"
    And I set the following fields to these values:
      | Title                 | A customised title |
      | Body                  | A customised body  |
      | Update all activities | 1                  |
    And I press "Save changes"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "Test seminar name"
    And I navigate to "Notifications" node in "Seminar administration"
    And I click on "Edit" "link" in the "A customised title" "table_row"
    Then the following fields match these values:
      | Template | A customised title |
      | Title    | A customised title |
      | Body     | A customised body  |
    And I should not see "The registration period for the following session has been closed:"

  @javascript
  Scenario: Updating templates can be set to not update the notifications in a seminar activity
    When I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Test seminar name        |
      | Description       | Test seminar description |
    And I follow "Test seminar name"
    And I navigate to "Notifications" node in "Seminar administration"
    And I click on "Edit" "link" in the "Seminar registration closed" "table_row"
    Then the following fields match these values:
      | Template | Seminar registration closed: [facetofacename], [starttime]-[finishtime], [sessiondate] |
      | Title    | Seminar registration closed: [facetofacename], [starttime]-[finishtime], [sessiondate] |
    And I should see "The registration period for the following session has been closed:"
    When I navigate to "Notification templates" node in "Site administration > Seminars"
    And I click on "Edit" "link" in the "Seminar registration closed" "table_row"
    And I set the following fields to these values:
      | Title                 | A customised title |
      | Body                  | A customised body  |
      | Update all activities | 0                  |
    And I press "Save changes"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "Test seminar name"
    And I navigate to "Notifications" node in "Seminar administration"
    And I click on "Edit" "link" in the "Seminar registration closed" "table_row"
    Then the following fields match these values:
      | Template | A customised title                                                                          |
      | Title    | Seminar registration closed: [facetofacename], [starttime]-[finishtime], [sessiondate] |
    And I should see "The registration period for the following session has been closed:"
    And I should not see "A customised body"
