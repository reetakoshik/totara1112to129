@totara @totara_program @javascript
Feature: Course enrolment through programs
  Verify that user enrolment / unenrolment in courses associated with a program
  is handled correctly for all Unenrol program plugin settings

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
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname    | idnumber |
      | Position FW | PFW001   |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | pos_framework | fullname  | idnumber |
      | PFW001        | Manager   | manager  |
      | PFW001        | Learner   | learner  |
    And the following job assignments exist:
      | user       | idnumber      | fullname | shortname | organisation | position | manager  | managerjaidnumber |
      | teacher1   | teacherjaid1  | fullt1   |           |              |          |          |                   |
      | manager1   | managerjaid1  | fullm1   |           |              | manager  | teacher1 | teacherjaid1      |
      | learner1   | jaid1         | full1    | shortl1   | org1         | learner  | manager1 | managerjaid1      |
      | learner2   | jaid2         | full2    | shortl2   | org1         | learner  | manager1 | managerjaid1      |

    # Create two programs with one course each
    And the following "programs" exist in "totara_program" plugin:
      | fullname                | shortname |
      | Test Program 1          | program1  |
      | Test Program 2          | program2  |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | course1   | 1                |
      | Course 2 | course2   | 1                |
    And I add a courseset with courses "course1" to "program1":
      | Set name              | set1        |
      | Learner must complete | All courses |
      | Minimum time required | 1           |
    And I add a courseset with courses "course2" to "program2":
      | Set name              | set1        |
      | Learner must complete | All courses |
      | Minimum time required | 1           |

    # Assign the position Learner program1
    And I log in as "admin"
    And I click on "Programs" in the totara menu
    And I follow "Test Program 1"
    And I press "Edit program details"
    And I switch to "Assignments" tab
    And I select "Positions" from the "Add a new" singleselect
    And I click on "Add" "button" in the "#category_select" "css_element"
    And I press "Add position to program"
    And I click on "Learner" "link" in the "Add position to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add position to program" "totaradialogue"
    And I press "Save changes"
    And I press "Save all changes"
    Then I should see "Learner"

    # Assign the organisation to the program2
    When I click on "Programs" in the totara menu
    And I follow "Test Program 2"
    And I press "Edit program details"
    And I switch to "Assignments" tab
    And I select "Organisations" from the "Add a new" singleselect
    And I click on "Add" "button" in the "#category_select" "css_element"
    And I press "Add organisations to program"
    And I click on "Organisation1" "link" in the "Add organisations to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add organisations to program" "totaradialogue"
    And I press "Save changes"
    And I press "Save all changes"
    Then I should see "Organisation1"
    And I log out

    # Enrol learner1 in both courses
    When I log in as "learner1"
    Then I should see "Test Program 1"
    And I should see "Test Program 2"
    When I follow "Test Program 1"
    Then I should see "You have been enrolled in course Course 1 via required learning program Test Program 1."
    When I follow "Dashboard"
    And I follow "Test Program 2"
    Then I should see "You have been enrolled in course Course 2 via required learning program Test Program 2."
    And I log out

  @block_current_learning
  Scenario: Assigned users can launch courses they are not enrolled in with audience based visibility on
    Given I log in as "admin"
    And I set the following administration settings values:
      | Enable audience-based visibility | 1 |
    And I follow "Find Learning"
    And I follow "Course 1"
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "Enrolled users only"
    And I press "Save and display"
    And I follow "Find Learning"
    And I follow "Course 2"
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "Enrolled users only"
    And I press "Save and display"
    And I log out
    When I log in as "learner2"
    Then I should see "Test Program 1"
    And I should see "Test Program 2"

    # Use the link to the course first up and confirm that they can access the course.
    When I follow "Dashboard"
    And I follow "Test Program 1"
    Then I should see "You have been enrolled in course Course 1 via required learning program Test Program 1."
    # Use the 'Launch course' button to enrol in the course.
    When I follow "Dashboard"
    And I follow "Test Program 2"
    Then I should see "You have been enrolled in course Course 2 via required learning program Test Program 2."

  Scenario: Enrolled user removed from program with Unenrol program plugin setting
    Given I log in as "admin"
    # Set the program plugin unenrolment action
    When I navigate to "Program" node in "Site administration > Plugins > Enrolments"
    And I select "Unenrol user from course" from the "External unenrol action" singleselect
    And I press "Save changes"

    # Remove learner1's position
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Learner One"
    And I follow "full1"
    And I click on "Delete" "link" in the "#positiontitle" "css_element"
    And I click on "Update job assignment" "button"
    And I log out

    # Run the cron tasks
    When I run the scheduled task "\totara_program\task\user_assignments_task"

    # learner1 should no longer be enrolled in the program and should not be able to access the course
    And I log in as "learner1"
    Then I should not see "Test Program 1" in the "Current Learning" "block"
    And I should see "Test Program 2" in the "Current Learning" "block"

    When I click on "Courses" in the totara menu
    Then I should see "Course 1"
    And I should see "Course 2"

    When I follow "Course 1"
    Then I should see "You can not enrol yourself in this course"
    When I click on "Courses" in the totara menu
    And I follow "Course 2"
    Then I should see "Topic 1"
    And I log out

    # learner2 can still enrol
    When I log in as "learner2"
    Then I should see "Test Program 1"
    And I should see "Test Program 2"
    When I follow "Test Program 1"
    Then I should see "Course 1"
    And I should see "You have been enrolled in course Course 1 via required learning program Test Program 1."
    When I follow "Dashboard"
    And I follow "Test Program 2"
    Then I should see "Course 2"
    And I should see "You have been enrolled in course Course 2 via required learning program Test Program 2."
    And I log out

  Scenario: Enrolled user removed from program with Disable course enrolment program plugin setting
    Given I log in as "admin"
    # Set the program plugin unenrolment action
    When I navigate to "Program" node in "Site administration > Plugins > Enrolments"
    And I select "Disable course enrolment" from the "External unenrol action" singleselect
    And I press "Save changes"

    # Remove learner1's position
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Learner One"
    And I follow "full1"
    And I click on "Delete" "link" in the "#positiontitle" "css_element"
    And I click on "Update job assignment" "button"
    And I log out

    # Run the cron tasks
    When I run the scheduled task "\totara_program\task\user_assignments_task"

    # learner1 should no longer be enrolled in the program and should not be able to access the course
    And I log in as "learner1"
    Then I should not see "Test Program 1" in the "Current Learning" "block"
    And I should see "Test Program 2" in the "Current Learning" "block"

    When I click on "Courses" in the totara menu
    Then I should see "Course 1"
    And I should see "Course 2"

    When I follow "Course 1"
    Then I should see "You can not enrol yourself in this course"

    When I click on "Courses" in the totara menu
    And I follow "Course 2"
    Then I should see "Topic 1"
    And I log out

    # learner2 can still enrol
    When I log in as "learner2"
    Then I should see "Test Program 1"
    And I should see "Test Program 2"
    When I follow "Test Program 1"
    Then I should see "Course 1"
    And I should see "You have been enrolled in course Course 1 via required learning program Test Program 1."
    When I follow "Dashboard"
    And I follow "Test Program 2"
    Then I should see "Course 2"
    And I should see "You have been enrolled in course Course 2 via required learning program Test Program 2."
    And I log out

  Scenario: Enrolled user removed from program with Disable course enrolment and remove roles program plugin setting
    Given I log in as "admin"
    # Set the program plugin unenrolment action
    When I navigate to "Program" node in "Site administration > Plugins > Enrolments"
    And I select "Disable course enrolment and remove roles" from the "External unenrol action" singleselect
    And I press "Save changes"

    # Remove learner1's position
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Learner One"
    And I follow "full1"
    And I click on "Delete" "link" in the "#positiontitle" "css_element"
    And I click on "Update job assignment" "button"
    And I log out

    # Run the cron tasks
    When I run the scheduled task "\totara_program\task\user_assignments_task"

    # User1 should no longer be enrolled in the program and should not be able to access the course
    And I log in as "learner1"
    Then I should not see "Test Program 1" in the "Current Learning" "block"
    And I should see "Test Program 2" in the "Current Learning" "block"

    When I click on "Courses" in the totara menu
    Then I should see "Course 1"
    And I should see "Course 2"

    When I follow "Course 1"
    Then I should see "You can not enrol yourself in this course"

    When I click on "Courses" in the totara menu
    And I follow "Course 2"
    Then I should see "Topic 1"
    And I log out

    # learner2 can still enrol
    When I log in as "learner2"
    Then I should see "Test Program 1"
    And I should see "Test Program 2"
    When I follow "Test Program 1"
    Then I should see "Course 1"
    Then I should see "You have been enrolled in course Course 1 via required learning program Test Program 1."
    When I follow "Dashboard"
    And I follow "Test Program 2"
    Then I should see "Course 2"
    Then I should see "You have been enrolled in course Course 2 via required learning program Test Program 2."
    And I log out

  Scenario: User added to program
    # teacher1 not in any program. Should not be allowed to enrol
    Given I log in as "teacher1"
    Then I should not see "Test Program 1"
    And I should not see "Test Program 2"

    When I click on "Courses" in the totara menu
    Then I should see "Course 1"
    And I should see "Course 2"

    When I follow "Course 1"
    Then I should see "You can not enrol yourself in this course"
    When I click on "Courses" in the totara menu
    And I follow "Course 2"
    Then I should see "You can not enrol yourself in this course"
    And I log out

    # Now add teacher to both programs
    Given I log in as "admin"

    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Teacher First"
    And I follow "fullt1"
    And I press "Choose position"
    And I click on "Learner" "link" in the "Choose position" "totaradialogue"
    And I click on "OK" "button" in the "Choose position" "totaradialogue"
    And I press "Choose organisation"
    And I click on "Organisation1" "link" in the "Choose organisation" "totaradialogue"
    And I click on "OK" "button" in the "Choose organisation" "totaradialogue"
    And I click on "Update job assignment" "button"
    And I log out

    # Run the cron tasks
    When I run the scheduled task "\totara_program\task\user_assignments_task"

    # teacher1 should now be able to enrol in the courses
    And I log in as "teacher1"
    Then I should see "Test Program 1" in the "Current Learning" "block"
    And I should see "Test Program 2" in the "Current Learning" "block"

    When I follow "Test Program 1"
    Then I should see "Course 1"
    And I should see "You have been enrolled in course Course 1 via required learning program Test Program 1."
    When I follow "Dashboard"
    And I follow "Test Program 2"
    Then I should see "Course 2"
    And I should see "You have been enrolled in course Course 2 via required learning program Test Program 2."
    And I log out
