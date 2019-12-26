@totara @totara_program @javascript
Feature: Suspend and re-enrol users enrolled in courses via programs
  In order to ensure that users enrolled in courses via programs have only access to the intended courses
  We must verify that suspension works properly for all program enrolment methods
  We must also verify that the user is being unsuspended if re-assigned to the program

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher   | First    | teacher1@example.com |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |
      | manager1 | Manager   | One      | manager1@example.com |

    Given the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname        | idnumber |
      | Organisation FW | OFW001   |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | org_framework | fullname      | idnumber |
      | OFW001        | Organisation1 | org1     |
      | OFW001        | Organisation2 | org2     |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname    | idnumber |
      | Position FW | PFW001   |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | pos_framework | fullname  | idnumber |
      | PFW001        | Manager   | manager  |
      | PFW001        | Learner   | learner  |
    And the following job assignments exist:
      | user       | idnumber      | fullname | shortname | organisation | position | manager  | managerjaidnumber |
      | teacher1   | teacherjaid1  | fullt1   |           |              | manager  |          |                   |
      | manager1   | managerjaid1  | fullm1   |           |              | manager  | teacher1 | teacherjaid1      |
      | learner1   | jaid1         | full1    | shortl1   | org1         | learner  | manager1 | managerjaid1      |
      | learner2   | jaid2         | full2    | shortl2   | org2         | learner  | manager1 | managerjaid1      |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    And the following "programs" exist in "totara_program" plugin:
      | fullname                | shortname |
      | Test Program 1          | program1  |
    And I add a courseset with courses "C1" to "program1":
      | Set name              | Set1        |
      | Learner must complete | All courses |
      | Minimum time required | 1           |

    # Ensure Audience syn External unenrol action is set to "Disable course enrolment and remove roles"
    And I log in as "admin"
    # Set the enrolment plugin unenrolment actions
    And I navigate to "Audience sync" node in "Site administration > Plugins > Enrolments"
    And I select "Disable course enrolment and remove roles" from the "External unenrol action" singleselect
    And I press "Save changes"
    And I navigate to "Program" node in "Site administration > Plugins > Enrolments"
    And I select "Disable course enrolment" from the "External unenrol action" singleselect
    And I press "Save changes"
    And I log out


  Scenario: Suspend users enrolled via a program with organisation assignment by removing the learner from the organisation
    When I log in as "admin"
    And I am on "Test Program 1" program homepage
    And I press "Edit program details"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Organisations"
    And I click on "Organisation1" "link" in the "Add organisations to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add organisations to program" "totaradialogue"
    Then I should see "Organisation1"
    And I log out

    # Enrol learner1 in the course via the program
    When I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

    # Change learner1's organisation
    When I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner One"
    And I follow "full1"
    And I click on "Delete" "link" in the "#organisationtitle" "css_element"
    And I click on "Update job assignment" "button"
    Then "full1" "link" should exist
    And I log out

    # User can still access the course until the cron is run
    When I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

    # Now run the cron task and test user access to the course
    When I run the scheduled task "\totara_program\task\user_assignments_task"
    And I log in as "learner1"
    Then I should not see "Test Program 1"
    When I am on "Test Program 1" program homepage
    Then "//input[@type='submit' and @value='Not available' and @disabled]" "xpath_element" should exist in the "Course 1" "table_row"
    When I am on "Course 1" course homepage
    Then I should see "You can not enrol yourself in this course"
    And I log out

    # Change learner1's organisation again to re-assign him to the program
    When I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner One"
    And I follow "full1"
    And I click on "Choose organisation" "button"
    And I click on "Organisation1" "link" in the "Choose organisation" "totaradialogue"
    And I click on "OK" "button" in the "Choose organisation" "totaradialogue"
    And I click on "Update job assignment" "button"
    Then "full1" "link" should exist
    And I log out

    # Now run the cron task and test user access to the course
    When I run the scheduled task "\totara_program\task\user_assignments_task"

    # learner1 should be able to access the course again
    And I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

  Scenario: Suspend users enrolled via a program with organisation assignment by changing the assigned organisation
    When I log in as "admin"
    And I am on "Test Program 1" program homepage
    And I press "Edit program details"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Organisations"
    And I click on "Organisation1" "link" in the "Add organisations to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add organisations to program" "totaradialogue"
    Then I should see "Organisation1"
    And I log out

    # Enrol learner1 in the course via the program
    When I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

    # Change assigned organisation
    When I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Miscellaneous"
    And I click on "Enrolled users" "link" in the "Test Program 1" "table_row"
    And I click on "Remove program assignment" "link" in the "Organisation1" "table_row"
    And I click on "Remove" "button"
    And I set the field "Add a new" to "Organisations"
    And I click on "Organisation2" "link" in the "Add organisations to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add organisations to program" "totaradialogue"
    Then I should see "Organisation2"
    And I log out

    # Now run cron - cron is not always needed if there are a small number of affected users.
    # Including it in the test to make sure the cron doesn't break anything
    When I run the scheduled task "\totara_program\task\user_assignments_task"

    # learner1 not able to access program or course
    And I log in as "learner1"
    Then I should not see "Test Program 1"
    When I am on "Test Program 1" program homepage
    Then "//input[@type='submit' and @value='Not available' and @disabled]" "xpath_element" should exist in the "Course 1" "table_row"
    When I am on "Course 1" course homepage
    Then I should see "You can not enrol yourself in this course"
    And I log out

    # learner2 should now be able to enrol via the program
    When I log in as "learner2"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

    # Change the program's assigned organisation again to re-assign learner1 to the program
    When I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Miscellaneous"
    And I click on "Enrolled users" "link" in the "Test Program 1" "table_row"
    And I set the field "Add a new" to "Organisations"
    And I click on "Organisation1" "link" in the "Add organisations to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add organisations to program" "totaradialogue"
    Then I should see "Organisation1"
    And I should see "Organisation2"
    And I log out

    # Now run the cron task and test user access to the course
    When I run the scheduled task "\totara_program\task\user_assignments_task"

    # learner1 should be able to access the course again
    And I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

  Scenario: Suspend users enrolled via a program with position assignment by changing the learner's position
    When I log in as "admin"
    And I am on "Test Program 1" program homepage
    And I press "Edit program details"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Positions"
    And I click on "Learner" "link" in the "Add positions to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add positions to program" "totaradialogue"
    Then I should see "Learner"
    And I log out

    # Enrol learner1 in the course via the program
    When I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

    # Change learner1's position
    When I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner One"
    And I follow "full1"
    And I click on "Delete" "link" in the "#positiontitle" "css_element"
    And I click on "Update job assignment" "button"
    Then "full1" "link" should exist
    And I log out

    # User can still access the course until the cron is run
    When I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

    # Now run the cron task and test user access to the course
    When I run the scheduled task "\totara_program\task\user_assignments_task"
    And I log in as "learner1"
    Then I should not see "Test Program 1"
    When I am on "Test Program 1" program homepage
    Then "//input[@type='submit' and @value='Not available' and @disabled]" "xpath_element" should exist in the "Course 1" "table_row"
    When I am on "Course 1" course homepage
    Then I should see "You can not enrol yourself in this course"
    And I log out

    # Change learner1's position again to re-enrol him in the program
    When I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner One"
    And I follow "full1"
    And I click on "Choose position" "button"
    And I click on "Learner" "link" in the "Choose position" "totaradialogue"
    And I click on "OK" "button" in the "Choose position" "totaradialogue"
    And I click on "Update job assignment" "button"
    Then "full1" "link" should exist
    And I log out

    # Now run the cron task and test user access to the course
    When I run the scheduled task "\totara_program\task\user_assignments_task"

    # learner1 can again access the course
    And I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

  Scenario: Suspend users enrolled via a program with position assignment by changing the assigned position
    When I log in as "admin"
    And I am on "Test Program 1" program homepage
    And I press "Edit program details"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Positions"
    And I click on "Learner" "link" in the "Add positions to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add positions to program" "totaradialogue"
    Then I should see "Learner"
    And I log out

    # Enrol learner1 in the course via the program
    When I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

    # Change assigned position
    When I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Miscellaneous"
    And I click on "Enrolled users" "link" in the "Test Program 1" "table_row"
    And I click on "Remove program assignment" "link" in the "Learner" "table_row"
    And I click on "Remove" "button"
    And I set the field "Add a new" to "Positions"
    And I click on "Manager" "link" in the "Add positions to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add positions to program" "totaradialogue"
    Then I should see "Manager"
    And I log out

    # Now run cron - cron is not always needed of there are a small number of affected users.
    # Including it in the test to make sure the cron doesn't break anything
    When I run the scheduled task "\totara_program\task\user_assignments_task"

    # learner1 not able to access program or course
    And I log in as "learner1"
    Then I should not see "Test Program 1"
    When I am on "Test Program 1" program homepage
    Then "//input[@type='submit' and @value='Not available' and @disabled]" "xpath_element" should exist in the "Course 1" "table_row"
    When I am on "Course 1" course homepage
    Then I should see "You can not enrol yourself in this course"
    And I log out

    # manager1 should now be able to enrol via the program
    When I log in as "manager1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

    # Assign Learner position to the program again to re-enrol learner1
    When I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Miscellaneous"
    And I click on "Enrolled users" "link" in the "Test Program 1" "table_row"
    And I set the field "Add a new" to "Positions"
    And I click on "Learner" "link" in the "Add positions to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add positions to program" "totaradialogue"
    Then I should see "Learner"
    And I should see "Manager"
    And I log out

    # Now run the cron task and test user access to the course
    When I run the scheduled task "\totara_program\task\user_assignments_task"

    # learner1 can now access the course again
    And I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

  Scenario: Suspend users enrolled via a program with management hierarchy assignment by changing the learner's profile
    When I log in as "admin"
    And I am on "Test Program 1" program homepage
    And I press "Edit program details"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Management hierarchy"
    And I click on "Manager One (manager1@example.com) - fullm1" "link" in the "Add managers to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add managers to program" "totaradialogue"
    Then I should see "Manager One - fullm1"
    And I log out

    # Enrol learner1 in the course via the program
    When I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

    # Change learner1's profile
    When I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner One"
    And I follow "full1"
    And I click on "Delete" "link" in the "#managertitle" "css_element"
    And I click on "Update job assignment" "button"
    Then "full1" "link" should exist
    And I log out

    # User can still access the course until the cron is run
    When I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

    # Now run the cron task and test user access to the course
    When I run the scheduled task "\totara_program\task\user_assignments_task"
    And I log in as "learner1"
    Then I should not see "Test Program 1"
    When I am on "Test Program 1" program homepage
    Then "//input[@type='submit' and @value='Not available' and @disabled]" "xpath_element" should exist in the "Course 1" "table_row"
    When I am on "Course 1" course homepage
    Then I should see "You can not enrol yourself in this course"
    And I log out

    # Change learner1's profile again to re-enrol him
    When I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner One"
    And I follow "full1"
    And I click on "Choose manager" "button"
    And I click on "Manager One (manager1@example.com)" "link" in the "Choose manager" "totaradialogue"
    And I click on "fullm1" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I click on "Update job assignment" "button"
    Then "full1" "link" should exist
    And I log out

    # Now run the cron task and test user access to the course
    When I run the scheduled task "\totara_program\task\user_assignments_task"

    # learner1 can access the course again
    And I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

  Scenario: Suspend users enrolled via a program with management hierarchy assignment by changing the assigned manager
    When I log in as "admin"
    And I am on "Test Program 1" program homepage
    And I press "Edit program details"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Management hierarchy"
    And I click on "Manager One (manager1@example.com) - fullm1" "link" in the "Add managers to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add managers to program" "totaradialogue"
    Then I should see "Manager One - fullm1"
    And I log out

    # Enrol learner1 in the course via the program
    When I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

    # Change assigned manager
    When I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Miscellaneous"
    And I click on "Enrolled users" "link" in the "Test Program 1" "table_row"
    And I click on "Remove program assignment" "link" in the "Manager One - fullm1" "table_row"
    And I click on "Remove" "button"
    And I set the field "Add a new" to "Management hierarchy"
    And I click on "Teacher First (teacher1@example.com) - fullt1" "link" in the "Add managers to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add managers to program" "totaradialogue"
    Then I should see "Teacher First - fullt1"
    And I log out

    # Now run the cron task and test user access to the course
    When I run the scheduled task "\totara_program\task\user_assignments_task"

    # learner1 not able to access program or course
    And I log in as "learner1"
    Then I should not see "Test Program 1"
    When I am on "Test Program 1" program homepage
    Then "//input[@type='submit' and @value='Not available' and @disabled]" "xpath_element" should exist in the "Course 1" "table_row"
    When I am on "Course 1" course homepage
    Then I should see "You can not enrol yourself in this course"
    And I log out

    # manager1 should now be able to enrol via the program
    When I log in as "manager1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

    # Change assigned manager again to allow learner1 access
    When I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Miscellaneous"
    And I click on "Enrolled users" "link" in the "Test Program 1" "table_row"
    And I set the field "Add a new" to "Management hierarchy"
    And I click on "Manager One (manager1@example.com) - fullm1" "link" in the "Add managers to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add managers to program" "totaradialogue"
    Then I should see "Manager One - fullm1"
    And I should see "Teacher First - fullt1"
    And I log out

    # Now run the cron task and test user access to the course
    When I run the scheduled task "\totara_program\task\user_assignments_task"

    # learner1 can access the course again
    And I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

  Scenario: Suspend users that were enrolled as individuals through a program
    When I log in as "admin"
    And I am on "Test Program 1" program homepage
    And I press "Edit program details"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "Learner One (learner1@example.com)" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Learner Two (learner2@example.com)" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    Then I should see "Learner One"
    And I should see "Learner Two"
    And I log out

    # Enrol learner1 in the course via the program
    When I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

    # Remove learner1
    When I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Miscellaneous"
    And I click on "Enrolled users" "link" in the "Test Program 1" "table_row"
    And I click on "Remove program assignment" "link" in the "Learner One" "table_row"
    And I click on "Remove" "button"
    Then I should not see "Learner One" in the ".totara_program__assignments__results__table" "css_element"
    And I should see "'Learner One' has been removed from the program"
    And I log out

    # Now run the cron task and test user access to the course
    When I run the scheduled task "\totara_program\task\user_assignments_task"

    # learner1 not able to access program or course
    And I log in as "learner1"
    Then I should not see "Test Program 1"
    When I am on "Test Program 1" program homepage
    Then "//input[@type='submit' and @value='Not available' and @disabled]" "xpath_element" should exist in the "Course 1" "table_row"
    When I am on "Course 1" course homepage
    Then I should see "You can not enrol yourself in this course"
    And I log out

    # learner2 should still be able to enrol via the program
    When I log in as "learner2"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

    # Re-assign learner1 again
    When I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Miscellaneous"
    And I click on "Enrolled users" "link" in the "Test Program 1" "table_row"
    And I set the field "Add a new" to "Individuals"
    And I click on "Learner One (learner1@example.com)" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    Then I should see "Learner One"
    And I should see "Learner Two"
    And I log out

    # Now run the cron task and test user access to the course
    When I run the scheduled task "\totara_program\task\user_assignments_task"

    # learner1 can access the course again
    And I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

  Scenario: Suspend users by making the program unavailable
    When I log in as "admin"
    And I am on "Test Program 1" program homepage
    And I press "Edit program details"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "Learner One (learner1@example.com)" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Learner Two (learner2@example.com)" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    Then I should see "Learner One"
    And I should see "Learner Two"
    And I log out

    # Enrol learner1 in the course via the program
    When I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

    # Make the program unavailable by setting an expiry date that is in the past
    When I log in as "admin"
    And I am on "Test Program 1" program homepage
    And I press "Edit program details"
    And I switch to "Details" tab
    And I set the following fields to these values:
      | availableuntil[enabled]  | 1 |
      | availableuntil[day]      | 1 |
      | availableuntil[month]    | 1 |
      | availableuntil[year]     | 2017 |
    And I press "Save changes"
    # Can't test string on Overview due to &nbsp; in the value
    And I switch to "Details" tab
    Then the following fields match these values:
      | availableuntil[enabled]  | 1 |
      | availableuntil[day]      | 1 |
      | availableuntil[month]    | 1 |
      | availableuntil[year]     | 2017 |
    And I log out

    # learner1 can not see the program or access the course
    When I log in as "learner1"
    Then I should not see "Test Program 1"
    When I click on "Find Learning" in the totara menu
    Then I should not see "Test Program 1"
    When I am on "Course 1" course homepage
    Then I should see "You can not enrol yourself in this course"
    And I log out

    # Make the program available again
    When I log in as "admin"
    And I am on "Test Program 1" program homepage
    And I press "Edit program details"
    And I switch to "Details" tab
    And I set the following fields to these values:
      | availableuntil[enabled]  | 0 |
    And I press "Save changes"
    # Can't test string on Overview due to &nbsp; in the value
    And I switch to "Details" tab
    Then the following fields match these values:
      | availableuntil[enabled]  | 0 |
    And I log out

    # Learner1 can access see the program and access the course again
    When I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out
