@report @report_progress @totara @javascript
Feature: Paging on activity completion report
  Paging must work as expected on the course completion report

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
    And the following "activities" exist:
      | activity   | name              | intro         | course               | idnumber    | completion   |
      | label      | label1            | label1        | C1                   | label1      | 1            |
      | label      | label2            | label2        | C1                   | label2      | 1            |
    # Quite a few users are required so that there are a number of pages
    And the following "users" exist:
      | username  | firstname | lastname | email                 |
      | student1  | CStudent  | A1       | student01@example.com |
      | student2  | CStudent  | A2       | student02@example.com |
      | student3  | CStudent  | A3       | student03@example.com |
      | student4  | Student   | A4       | student04@example.com |
      | student5  | Student   | A5       | student05@example.com |
      | student6  | Student   | A6       | student06@example.com |
      | student7  | Student   | A7       | student07@example.com |
      | student8  | Student   | A8       | student08@example.com |
      | student9  | Student   | A9       | student09@example.com |
      | student10 | Student   | B10      | student10@example.com |
      | student11 | Student   | B11      | student11@example.com |
      | student12 | Student   | B12      | student12@example.com |
      | student13 | Student   | B13      | student13@example.com |
      | student14 | Student   | B14      | student14@example.com |
      | student15 | Student   | B15      | student15@example.com |
      | student16 | Student   | B16      | student16@example.com |
      | student17 | Student   | B17      | student17@example.com |
      | student18 | Student   | B18      | student18@example.com |
      | student19 | Student   | B19      | student19@example.com |
      | student20 | Student   | C20      | student20@example.com |
      | student21 | Student   | C21      | student21@example.com |
      | student22 | Student   | C22      | student22@example.com |
      | student23 | Student   | C23      | student23@example.com |
      | student24 | Student   | C24      | student24@example.com |
      | student25 | Student   | C25      | student25@example.com |
      | student26 | Student   | C26      | student26@example.com |
      | student27 | Student   | C27      | student27@example.com |
      | student28 | Student   | C28      | student28@example.com |
      | student29 | Student   | C29      | student29@example.com |
      | student30 | Student   | C30      | student30@example.com |
    And the following "course enrolments" exist:
      | user      | course | role    |
      | student1  | C1     | student |
      | student2  | C1     | student |
      | student3  | C1     | student |
      | student4  | C1     | student |
      | student5  | C1     | student |
      | student6  | C1     | student |
      | student7  | C1     | student |
      | student8  | C1     | student |
      | student9  | C1     | student |
      | student10 | C1     | student |
      | student11 | C1     | student |
      | student12 | C1     | student |
      | student13 | C1     | student |
      | student14 | C1     | student |
      | student15 | C1     | student |
      | student16 | C1     | student |
      | student17 | C1     | student |
      | student18 | C1     | student |
      | student19 | C1     | student |
      | student20 | C1     | student |
      | student21 | C1     | student |
      | student22 | C1     | student |
      | student23 | C1     | student |
      | student24 | C1     | student |
      | student25 | C1     | student |
      | student26 | C1     | student |
      | student27 | C1     | student |
      | student28 | C1     | student |
      | student29 | C1     | student |
      | student30 | C1     | student |
    And I log in as "admin"
    And I am on homepage
    And I follow "Course 1"
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I click on "criteria_activity_value[1]" "checkbox"
    And I click on "criteria_activity_value[2]" "checkbox"
    And I press "Save changes"

  Scenario Outline: Activity completion report Normal paging works
    Given I navigate to "Activity completion" node in "Course administration > Reports"
    # Check a "random" selection is visible
    Then I should see "CStudent A1"
    And I should see "Student A5"
    And I should see "Student A9"
    And I should see "Student B11"
    And I should see "Student B15"
    And I should see "Student B19"
    And I should see "Student C25"
    # Then there should be some on the next page
    And I should not see "Student C26"
    And I should not see "Student C29"

    # Switch to the next page - should see the inverse
    When I click on "<nextpage>" "link" in the ".paging" "css_element"
    Then I should not see "CStudent A1"
    And I should not see "Student A5"
    And I should not see "Student A9"
    And I should not see "Student B11"
    And I should not see "Student B15"
    And I should not see "Student B19"
    And I should not see "Student C25"
    # Then there should be some on the next page
    And I should see "Student C26"
    And I should see "Student C29"

    # Go back to the first page
    When I click on "<previouspage>" "link" in the ".paging" "css_element"
    Then I should see "CStudent A1"
    And I should see "Student A5"
    And I should see "Student A9"
    And I should see "Student B11"
    And I should see "Student B15"
    And I should see "Student B19"
    And I should see "Student C25"
    # Then there should be some on the next page
    And I should not see "Student C26"
    And I should not see "Student C29"

  Examples:
    | nextpage | previouspage |
    | Next     | Previous     |
    | Next     | 1            |
    | 2        | Previous     |
    | 2        | 1            |

  Scenario: Activity completion report firstname filter and paging
    Given I navigate to "Activity completion" node in "Course administration > Reports"
    And I click on "S" "link" in the ".firstinitial" "css_element"
    # Check a "random" selection is visible
    Then I should not see "CStudent A1"
    And I should see "Student A5"
    And I should see "Student A9"
    And I should see "Student B11"
    And I should see "Student B15"
    And I should see "Student B19"
    And I should see "Student C22"
    And I should see "Student C28"
    # Then there should be some on the next page
    And I should not see "Student C29"

    # Switch to the next page - should see the inverse
    When I click on "Next" "link" in the ".paging" "css_element"
    Then I should not see "CStudent A1"
    And I should not see "Student A5"
    And I should not see "Student A9"
    And I should not see "Student B11"
    And I should not see "Student B15"
    And I should not see "Student B19"
    And I should not see "Student C27"
    # Then there should be some on the next page
    And I should not see "Student C28"
    And I should see "Student C29"

    When I click on "B" "link" in the ".firstinitial" "css_element"
    Then I should not see "Student A1"
    And I should not see "Student A5"
    And I should not see "Student A9"
    And I should not see "Student B11"
    And I should not see "Student B15"
    And I should not see "Student B19"
    And I should not see "Student C25"
    And I should not see "Student C26"
    And I should not see "Student C29"

    When I click on "All" "link" in the ".firstinitial" "css_element"
    Then I should see "CStudent A1"
    And I should see "Student A5"
    And I should see "Student A9"
    And I should see "Student B11"
    And I should see "Student B15"
    And I should see "Student B19"
    And I should see "Student C25"
    # Then there should be some on the next page
    And I should not see "Student C26"
    And I should not see "Student C29"

  Scenario: Activity completion report lastname filter
    Given I navigate to "Activity completion" node in "Course administration > Reports"
    When I click on "B" "link" in the ".lastinitial" "css_element"
    And I should see "Student B11"
    And I should see "Student B15"
    And I should see "Student B19"
    And I should not see "CStudent A1"
    And I should not see "Student A5"
    And I should not see "Student A9"
    And I should not see "Student C20"
    And I should not see "Student C26"
    And I should not see "Student C30"

    When I click on "C" "link" in the ".lastinitial" "css_element"
    And I should not see "Student B11"
    And I should not see "Student B15"
    And I should not see "Student B19"
    And I should not see "CStudent A1"
    And I should not see "Student A5"
    And I should not see "Student A9"
    And I should see "Student C20"
    And I should see "Student C26"
    And I should see "Student C30"

    When I click on "All" "link" in the ".lastinitial" "css_element"
    Then I should see "CStudent A1"
    And I should see "Student A5"
    And I should see "Student A9"
    And I should see "Student B11"
    And I should see "Student B15"
    And I should see "Student B19"
    And I should see "Student C25"
    # Then there should be some on the next page
    And I should not see "Student C26"
    And I should not see "Student C29"