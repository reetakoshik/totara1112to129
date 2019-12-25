@totara @totara_reportbuilder
Feature: Use the multi-item course filter
  To filter the courses in a report
  by several courses at a time
  I need to use the multi-item course filter

  Background:
    Given I am on a totara site
    # Audience visibility: 3 is 'No users' and 2 is 'All users'.
    And the following "courses" exist:
      | fullname    | shortname | audiencevisible |
      | CourseOne   | Course1   | 3               |
      | CourseTwo   | Course2   | 2               |
      | CourseThree | Course3   | 2               |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | user      | one      | user1@example.com |
    And I log in as "admin"
    And the following config values are set as admin:
      | audiencevisibility | 1 |
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"

  @javascript
  Scenario: Use filter with Courses report source
    Given I set the field "Report Name" to "Courses"
    And I set the field "Source" to "Courses"
    And I press "Create report"
    And I switch to "Filters" tab
    And I select "Course (multi-item)" from the "newstandardfilter" singleselect
    And I press "Save changes"
    And I switch to "Access" tab
    And I set the field "Authenticated user" to "1"
    And I press "Save changes"
    When I follow "View This Report"
    Then I should see "CourseOne" in the ".reportbuilder-table" "css_element"
    And I should see "CourseTwo" in the ".reportbuilder-table" "css_element"
    And I should see "CourseThree" in the ".reportbuilder-table" "css_element"
    And the "Choose Courses" "button" should be disabled
    When I select "is equal to" from the "Course (multi-item)" singleselect
    Then the "Choose Courses" "button" should be enabled
    When I press "Choose Courses"
    And I click on "Miscellaneous" "link" in the "Choose Courses" "totaradialogue"
    And I wait "1" seconds
    And I click on "CourseOne" "link" in the "Choose Courses" "totaradialogue"
    And I click on "CourseTwo" "link" in the "Choose Courses" "totaradialogue"
    And I click on "Save" "button" in the "Choose Courses" "totaradialogue"
    And I wait "1" seconds
    Then I should see "CourseOne" in the "Course (multi-item)" "fieldset"
    And I should see "CourseTwo" in the "Course (multi-item)" "fieldset"
    When I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "CourseOne" in the ".reportbuilder-table" "css_element"
    And I should see "CourseTwo" in the ".reportbuilder-table" "css_element"
    And I should not see "CourseThree" in the ".reportbuilder-table" "css_element"
    When I select "isn't equal to" from the "Course (multi-item)" singleselect
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "CourseOne" in the ".reportbuilder-table" "css_element"
    And I should not see "CourseTwo" in the ".reportbuilder-table" "css_element"
    And I should see "CourseThree" in the ".reportbuilder-table" "css_element"
    When I press "Save this search"
    # The order of the courses in the below text can change so just check the first part of it.
    Then I should see "Course (multi-item) isn't equal to"
    When I set the field "Search Name" to "Not1or2"
    And I set the field "Let other users view" to "1"
    And I press "Save changes"
    Then "View a saved search..." "select" should be visible
    When I log out
    And I log in as "user1"
    And I click on "Reports" in the totara menu
    And I click on "Courses" "link" in the ".reportmanager" "css_element"
    And I select "Not1or2" from the "View a saved search..." singleselect
    Then I should not see "CourseOne" in the "Course (multi-item)" "fieldset"
    And I should see "CourseTwo" in the "Course (multi-item)" "fieldset"
    And I should not see "CourseOne" in the ".reportbuilder-table" "css_element"
    And I should not see "CourseTwo" in the ".reportbuilder-table" "css_element"
    And I should see "CourseThree" in the ".reportbuilder-table" "css_element"

  @javascript
  Scenario: Test filter with spaces
    Given I set the field "Report Name" to "Courses"
    And I set the field "Source" to "Courses"
    And I press "Create report"
    When I follow "View This Report"
    Then I should see "CourseOne" in the ".reportbuilder-table" "css_element"
    And I should see "CourseTwo" in the ".reportbuilder-table" "css_element"
    And I should see "CourseThree" in the ".reportbuilder-table" "css_element"
  # Use normal search
    When I set the field "course-fullname" to "CourseOne"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "CourseOne" in the ".reportbuilder-table" "css_element"
    And I should not see "CourseTwo" in the ".reportbuilder-table" "css_element"
    And I should not see "CourseThree" in the ".reportbuilder-table" "css_element"
  # Use search with spaces
    When I set the field "course-fullname" to "    "
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "CourseOne" in the ".reportbuilder-table" "css_element"
    And I should see "CourseTwo" in the ".reportbuilder-table" "css_element"
    And I should see "CourseThree" in the ".reportbuilder-table" "css_element"

  @javascript
  Scenario: Add filter with Seminar Sessions report source
    Given I set the field "Report Name" to "Seminar Sessions"
    And I set the field "Source" to "Seminar Sessions"
    And I press "Create report"
    And I switch to "Filters" tab
    And I select "Course (multi-item)" from the "newstandardfilter" singleselect
    And I press "Save changes"
    When I follow "View This Report"
    Then I should see "Course (multi-item)"
