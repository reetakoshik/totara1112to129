@totara @totara_reportbuilder @javascript @core_grades @mod_facetoface
Feature: Test expand course details in Reportbuilder
  As a admin
  I need to be able to expand course details in reports regardless whether the
  report has enabled filters or not

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username     | firstname | lastname  | email                 |
      | student1     | Sam1      | Student1  | student1@example.com  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
      | Course 2 | C2        | 0        |
      | Course 3 | C3        | 0        |
      | Course 4 | C4        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C4     | student        |
    And the following courses are completed:
      | user     | course | timecompleted  |
      | student1 | C4     | yesterday      |

    And I log in as "admin"
    And I set the following administration settings values:
      | catalogtype | enhanced |
    And I navigate to "Manage enrol plugins" node in "Site administration > Plugins > Enrolments"
    And I click on "Enable" "link" in the "Seminar direct enrolment" "table_row"
    And I am on homepage

    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                                    | Test seminar 1             |
      | Description                             | Test seminar 1 description |
    And I navigate to "Enrolment methods" node in "Course administration > Users"
    And I click on "Disable" "link" in the "Manual enrolments" "table_row"
    And I click on "Disable" "link" in the "Program" "table_row"
    And I set the field "Add method" to "Seminar direct enrolment"
    And I press "Add method"

    And I am on "Course 2" course homepage
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                                    | Test seminar 2             |
      | Description                             | Test seminar 2 description |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[month]   | 0                |
      | timestart[day]     | +1               |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[month]  | 0                |
      | timefinish[day]    | +1               |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | +1               |
      | timefinish[minute] | 0                |
    And I press "OK"
    And I press "Save changes"
    And I navigate to "Enrolment methods" node in "Course administration > Users"
    And I click on "Disable" "link" in the "Manual enrolments" "table_row"
    And I click on "Disable" "link" in the "Program" "table_row"
    And I set the field "Add method" to "Seminar direct enrolment"
    And I press "Add method"

    And I am on "Course 3" course homepage
    And I add a "Page" to section "1" and I fill the form with:
      | Name                | Page 1 |
      | Description         | Test   |
      | Page content        | Test   |

    And I am on "Course 4" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the following fields to these values:
      | Show gradebook to learners | No |
    And I press "Save and display"
    And I navigate to "Grades" node in "Course administration"
    And I follow "Grader report"
    And I turn editing mode on
    And I give the grade "90" to the user "Sam1 Student1" for the grade item "Course total"
    And I press "Save changes"
    And I log out

  Scenario: Expand course detail in coursecatalog with filters
    Given I log in as "student1"
    And I click on "Courses" in the totara menu
    And I click on "//div[contains(@class, 'rb-display-expand') and contains (., 'Course 1')]" "xpath_element"
    Then I should see "Seminar direct enrolment"
    And I should see "Cannot enrol (no seminar events in this course)"
    And I should not see "Sign-up"
    And I should not see "Manual enrolments, Program"

    When I click on "//div[contains(@class, 'rb-display-expand') and contains (., 'Course 2')]" "xpath_element"
    Then I should see "Seminar direct enrolment"
    And I should not see "Cannot enrol (no seminar events in this course)"
    And I should see "Sign-up"
    And I should not see "Manual enrolments, Program"

    When I click on "//div[contains(@class, 'rb-display-expand') and contains (., 'Course 3')]" "xpath_element"
    And I should see "Manual enrolments, Program"

    When I click on "//div[contains(@class, 'rb-display-expand') and contains (., 'Course 4')]" "xpath_element"
    Then I should see "You have completed this course"
    And I should not see "Grade"
    And I log out

  @_alert
  Scenario: Expand course detail in coursecatalog with all filters disabled
    Given I log in as "admin"
    And I click on "Courses" in the totara menu
    And I press "Edit this report"
    And I switch to "Filters" tab
    # Deleting all filters
    And I click on "Delete" "link" confirming the dialogue
    And I click on "Delete" "link" confirming the dialogue
    And I press "Save changes"
    And I log out

    When I log in as "student1"
    And I click on "Courses" in the totara menu
    And I click on "//div[contains(@class, 'rb-display-expand') and contains (., 'Course 1')]" "xpath_element"
    Then I should see "Seminar direct enrolment"
    And I should see "Cannot enrol (no seminar events in this course)"
    And I should not see "Sign-up"
    And I should not see "Manual enrolments, Program"

    When I click on "//div[contains(@class, 'rb-display-expand') and contains (., 'Course 2')]" "xpath_element"
    Then I should see "Seminar direct enrolment"
    And I should not see "Cannot enrol (no seminar events in this course)"
    And I should see "Sign-up"
    And I should not see "Manual enrolments, Program"

    When I click on "//div[contains(@class, 'rb-display-expand') and contains (., 'Course 3')]" "xpath_element"
    And I should see "Manual enrolments, Program"
    And I log out
