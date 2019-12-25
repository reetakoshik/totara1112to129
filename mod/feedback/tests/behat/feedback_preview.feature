@mod @mod_feedback
Feature: Test previewing feedback
  In order to know what feedback would look like to a user
  As a teacher
  I need to be able to preview it

  @javascript
  Scenario: Preview feedback with a pagebreak at the top
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | 1        |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name                | course | idnumber  |
      | feedback | Learning experience | C1     | feedback0 |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Learning experience"
    And I click on "Edit questions" "link" in the "[role=main]" "css_element"
    And I add a "Label" question to the feedback with:
      | Contents | label text |
    And I add a page break to the feedback
    And I click on "Edit" "link" in the "#action-menu-1" "css_element"
    And I click on "a.editing_delete" "css_element" in the "#action-menu-1" "css_element"
    Then I should see "Are you sure you want to delete this element?"
    And I press "Yes"
    And I add a "Numeric answer" question to the feedback with:
      | Question   | this is a numeric answer |
      | Label      | numeric                  |
      | Range from | 0                        |
      | Range to   | 100                      |
    And I add a "Short text answer" question to the feedback with:
      | Question                    | this is a short text answer |
      | Label                       | shorttext                   |
      | Maximum characters accepted | 200                         |
    And I click on "Overview" "link" in the "[role=main]" "css_element"
    And I follow "Preview"
    Then I should see "Learning experience"
