@mod @mod_facetoface @totara
Feature: Use facetoface session roles content restriction in facetoface session report
  In order to use session roles content restriction
  As an admin
  I need to be able to setup session roles content restriction

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry     | Teacher1 | teacher1@example.com |
      | teacher2 | Alex      | Teacher2 | teacher2@example.com |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
      | student3 | Sam3      | Student3 | student3@example.com |
      | student4 | Sam4      | Student4 | student4@example.com |
      | student5 | Sam5      | Student5 | student5@example.com |
      | student6 | Sam6      | Student6 | student6@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
      | Course 2 | C2        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      # Course 1
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
      | student5 | C1     | student        |
      | student6 | C1     | student        |
      # Course 2
      | teacher2 | C2     | teacher        |
      | teacher1 | C2     | teacher        |
      | student1 | C2     | student        |
      | student2 | C2     | student        |
      | student3 | C2     | student        |
      | student4 | C2     | student        |
      | student5 | C2     | student        |
      | student6 | C2     | student        |
    And the following "activities" exist:
      | activity   | name           | course | idnumber |
      | facetoface | Seminar 11187A | C1     | S11187A  |
      | facetoface | Seminar 11187B | C2     | S11187B  |

    And I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "id_s__enableglobalrestrictions" to "1"
    And I press "Save changes"

    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I set the field "id_s__facetoface_session_roles_3" to "1"
    And I set the field "id_s__facetoface_session_roles_4" to "1"
    And I press "Save changes"

  @javascript
  Scenario: Setup session roles through report builder content restriction and the teachers can view only their attendees according to session role
    Given I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Seminar Sign-ups"
    And I set the field "Source" to "Seminar Sign-ups"
    And I press "Create report"
    And I wait until "Edit Report 'Seminar Sign-ups'" "text" exists
    And I click on "Columns" "link" in the ".tabtree" "css_element"
    And I add the "Seminar Name" column to the report
    And I press "Save changes"
    And I click on "Content" "link" in the ".tabtree" "css_element"
    And I set the field "id_globalrestriction" to "1"
    And I set the field "id_contentenabled_1" to "1"
    And I set the field "id_session_roles_enable" to "1"
    And I set the field "id_role_3" to "1"
    And I press "Save changes"
    And I click on "Access" "link" in the ".tabtree" "css_element"
    And I set the field "All users can view this report" to "1"
    And I press "Save changes"

    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Seminar Events"
    And I set the field "Source" to "Seminar Events"
    And I press "Create report"
    And I wait until "Edit Report 'Seminar Events'" "text" exists
    And I click on "Columns" "link" in the ".tabtree" "css_element"
    And I add the "Number of Attendees" column to the report
    And I press "Save changes"
    And I click on "Content" "link" in the ".tabtree" "css_element"
    And I set the field "id_globalrestriction" to "1"
    And I set the field "id_contentenabled_1" to "1"
    And I set the field "id_session_roles_enable" to "1"
    And I set the field "id_role_3" to "1"
    And I press "Save changes"
    And I click on "Access" "link" in the ".tabtree" "css_element"
    And I set the field "All users can view this report" to "1"
    And I press "Save changes"

    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Seminar Sessions"
    And I set the field "Source" to "Seminar Sessions"
    And I press "Create report"
    And I wait until "Edit Report 'Seminar Sessions'" "text" exists
    And I click on "Columns" "link" in the ".tabtree" "css_element"
    And I add the "Number of Attendees" column to the report
    And I press "Save changes"
    And I click on "Content" "link" in the ".tabtree" "css_element"
    And I set the field "id_globalrestriction" to "1"
    And I set the field "id_contentenabled_1" to "1"
    And I set the field "id_session_roles_enable" to "1"
    And I set the field "id_role_3" to "1"
    And I press "Save changes"
    And I click on "Access" "link" in the ".tabtree" "css_element"
    And I set the field "All users can view this report" to "1"
    And I press "Save changes"

    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I set the field "Terry Teacher1" to "1"
    And I press "Save changes"
    And I click on "Attendees" "link" in the "1 January 2020" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Add"
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I press "Add"
    And I click on "Sam3 Student3, student3@example.com" "option"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    And I follow "Go back"

    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 2    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 2    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I set the field "Alex Teacher2" to "1"
    And I press "Save changes"
    And I click on "Attendees" "link" in the "1 February 2020" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam4 Student4, student4@example.com" "option"
    And I press "Add"
    And I click on "Sam5 Student5, student5@example.com" "option"
    And I press "Add"
    And I click on "Sam6 Student6, student6@example.com" "option"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    And I log out

    When I log in as "teacher1"
    And I follow "Reports"
    And I follow "Seminar Sessions"
    Then I should see "3" in the "Seminar 11187A" "table_row"
    When I follow "Reports"
    And I follow "Seminar Sign-ups"
    Then I should see "Sam3 Student3"
    And I should see "Sam1 Student1"
    And I should see "Sam2 Student2"
    And I should not see "Sam4 Student4"
    And I should not see "Sam5 Student5"
    And I should not see "Sam6 Student6"
    When I follow "Reports"
    And I follow "Seminar Events"
    Then I should see "3" in the "Seminar 11187A" "table_row"
    And I log out

    When I log in as "teacher2"
    And I follow "Reports"
    And I follow "Seminar Sign-ups"
    Then I should not see "Sam3 Student3"
    And I should not see "Sam1 Student1"
    And I should not see "Sam2 Student2"
    And I should see "Sam4 Student4"
    And I should see "Sam5 Student5"
    And I should see "Sam6 Student6"
    When I follow "Reports"
    And I follow "Seminar Events"
    Then I should see "3" in the "Seminar 11187A" "table_row"
    When I follow "Reports"
    And I follow "Seminar Sessions"
    Then I should see "3" in the "Seminar 11187A" "table_row"
    And I log out

  @javascript
  Scenario: Setup multiple session roles through report builder content restriction and the teachers can view only their attendees according to mulitple session roles
    Given I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Seminar Sign-ups"
    And I set the field "Source" to "Seminar Sign-ups"
    And I press "Create report"
    And I wait until "Edit Report 'Seminar Sign-ups'" "text" exists
    And I click on "Columns" "link" in the ".tabtree" "css_element"
    And I add the "Seminar Name" column to the report
    And I press "Save changes"
    And I click on "Content" "link" in the ".tabtree" "css_element"
    And I set the field "id_globalrestriction" to "1"
    And I set the field "id_contentenabled_1" to "1"
    And I set the field "id_session_roles_enable" to "1"
    And I set the field "id_role_3" to "1"
    And I set the field "id_role_4" to "1"
    And I press "Save changes"
    And I click on "Access" "link" in the ".tabtree" "css_element"
    And I set the field "All users can view this report" to "1"
    And I press "Save changes"

    # Course 1 setup
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I set the field "Terry Teacher1" to "1"
    And I press "Save changes"
    And I click on "Attendees" "link" in the "1 January 2020" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Add"
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I press "Add"
    And I click on "Sam3 Student3, student3@example.com" "option"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    And I follow "Go back"

    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 2    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 2    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I set the field "Alex Teacher2" to "1"
    And I press "Save changes"
    And I click on "Attendees" "link" in the "1 February 2020" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam4 Student4, student4@example.com" "option"
    And I press "Add"
    And I click on "Sam5 Student5, student5@example.com" "option"
    And I press "Add"
    And I click on "Sam6 Student6, student6@example.com" "option"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"

    # Course 2 setup
    And I am on "Course 2" course homepage
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 2    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 2    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I set the field "Terry Teacher1" to "1"
    And I press "Save changes"
    And I click on "Attendees" "link" in the "2 January 2020" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Add"
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I press "Add"
    And I click on "Sam3 Student3, student3@example.com" "option"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"

    And I follow "Go back"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 2    |
      | timestart[month]   | 2    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 2    |
      | timefinish[month]  | 2    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I set the field "Alex Teacher2" to "1"
    And I press "Save changes"
    And I click on "Attendees" "link" in the "2 February 2020" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam4 Student4, student4@example.com" "option"
    And I press "Add"
    And I click on "Sam5 Student5, student5@example.com" "option"
    And I press "Add"
    And I click on "Sam6 Student6, student6@example.com" "option"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    And I log out

    When I log in as "teacher1"
    And I follow "Reports"
    And I follow "Seminar Sign-ups"
    Then I should see "Sam3 Student3"
    And I should see "Sam1 Student1"
    And I should see "Sam2 Student2"
    And I should not see "Sam4 Student4"
    And I should not see "Sam5 Student5"
    And I should not see "Sam6 Student6"
    And I log out

    When I log in as "teacher2"
    And I follow "Reports"
    And I follow "Seminar Sign-ups"
    Then I should not see "Sam3 Student3"
    And I should not see "Sam1 Student1"
    And I should not see "Sam2 Student2"
    And I should see "Sam4 Student4"
    And I should see "Sam5 Student5"
    And I should see "Sam6 Student6"
