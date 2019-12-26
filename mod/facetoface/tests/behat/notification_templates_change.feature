@mod @mod_facetoface @totara
Feature: Test notification templates update all activities
  In order to test notification templates for all activities
  I use Update all activities checkbox to activate or deactivate the changes

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity   | name            | course | idnumber |
      | facetoface | Seminar TL-9052 | C1     | seminar  |

  @javascript
  Scenario: Changing default notification templates using Update all activities will affect all F2F activities notifications
    Given I log in as "admin"
    # Change default template.
    And I navigate to "Notification templates" node in "Site administration > Seminars"
    And I click on "Edit" "link" in the "Seminar booking cancellation" "table_row"
    And I set the following fields to these values:
      | Title  | TL-9052 booking cancellation |
      | Status | 0                            |
      | Update all activities | 1             |
    When I click on "Save changes" "button"
    Then I should see "TL-9052 booking cancellation"
    And I should see "Inactive" in the "TL-9052 booking cancellation" "table_row"
    And I should not see "Seminar booking cancellation"

    And I click on "Edit" "link" in the "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]" "table_row"
    And I set the following fields to these values:
      | Title  | TL-9052 booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate] |
      | Update all activities | 1             |
    When I click on "Save changes" "button"
    Then I should see "TL-9052 booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]"
    And I should not see "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]"

    # Check F2F activity notification now.
    And I am on "Course 1" course homepage
    And I follow "Seminar TL-9052"
    When I navigate to "Notifications" node in "Seminar administration"
    Then I should see "TL-9052 booking cancellation"
    And I should not see "Inactive" in the "TL-9052 booking cancellation" "table_row"
    And I should not see "Seminar booking cancellation"

    And I should see "TL-9052 booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]"
    And I should not see "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]"

  @javascript
  Scenario: Changing default notification templates not using Update all activities will not affect any F2F activities notifications
    Given I log in as "admin"
    # Change default template.
    And I navigate to "Notification templates" node in "Site administration > Seminars"
    And I click on "Edit" "link" in the "Seminar booking cancellation" "table_row"
    And I set the following fields to these values:
      | Title  | TL-9052 booking cancellation |
      | Status | 0                            |
    When I click on "Save changes" "button"
    Then I should see "TL-9052 booking cancellation"
    And I should see "Inactive" in the "TL-9052 booking cancellation" "table_row"
    And I should not see "Seminar booking cancellation"

    And I click on "Edit" "link" in the "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]" "table_row"
    And I set the following fields to these values:
      | Title  | TL-9052 booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate] |
    When I click on "Save changes" "button"
    Then I should see "TL-9052 booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]"
    And I should not see "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]"

    # Check F2F activity notification now.
    And I am on "Course 1" course homepage
    And I follow "Seminar TL-9052"
    When I navigate to "Notifications" node in "Seminar administration"
    Then I should see "Seminar booking cancellation"
    And I should see "Active" in the "Seminar booking cancellation" "table_row"
    And I should not see "TL-9052 booking cancellation"

    And I should see "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]"
    And I should not see "TL-9052 booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]"

  @javascript
  Scenario: Changing default notification templates not using Update all activities will affect all new F2F activities notifications
    Given I log in as "admin"
    # Change default template.
    And I navigate to "Notification templates" node in "Site administration > Seminars"
    And I click on "Edit" "link" in the "Seminar booking cancellation" "table_row"
    And I set the following fields to these values:
      | Title  | TL-9052 booking cancellation |
      | Status | 0                            |
    When I click on "Save changes" "button"
    Then I should see "TL-9052 booking cancellation"
    And I should see "Inactive" in the "TL-9052 booking cancellation" "table_row"
    And I should not see "Seminar booking cancellation"

    And I click on "Edit" "link" in the "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]" "table_row"
    And I set the following fields to these values:
      | Title  | TL-9052 booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate] |
    When I click on "Save changes" "button"
    Then I should see "TL-9052 booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]"
    And I should not see "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]"

    And I click on "Edit" "link" in the "Waitlisting advice for [facetofacename]" "table_row"
    And I set the following fields to these values:
      | Manager copy prefix | *** [firstname] [lastname]'s session waitlisted is copied below **** |
    And I click on "Save changes" "button"

    # Check F2F activity notification now.
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name | Seminar TL-9052A |
    And I follow "Seminar TL-9052A"
    When I navigate to "Notifications" node in "Seminar administration"
    Then I should see "TL-9052 booking cancellation"
    And I should see "Inactive" in the "TL-9052 booking cancellation" "table_row"
    And I should not see "Seminar booking cancellation"

    And I should see "TL-9052 booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]"
    And I should not see "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]"

    When I click on "Edit" "link" in the "Waitlisting advice for [facetofacename]" "table_row"
    Then I should see "*** [firstname] [lastname]'s session waitlisted is copied below ****"
