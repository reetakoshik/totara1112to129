@totara @totara_program @javascript
Feature: Enrolment plugin cron tasks
  In order to ensure that program plugin user enrolment cron tasks play nicely together
  We must verify that running the cron tasks in any order has no effect on the results
  # Any one of the following on its own should be enough to update the user enrolment
  # but in the past there were issues where these tasks undone some of the work the
  # other tasks did, so running we need to test running them in different order
  # \totara_program\task\assignments_deferred_task handles updated job assignments
  # \totara_program\task\clean_enrolment_plugins_task cleans up user_enrolments
  # \totara_program\task\user_assignments_task cleans up program assignments, completion data and user_enrolments

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

    # Using program assignment through Organisation for all cases
    And I log in as "admin"
    And I am on "Test Program 1" program homepage
    And I press "Edit program details"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Organisations"
    And I click on "Organisation1" "link" in the "Add organisations to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add organisations to program" "totaradialogue"
    Then I should see "Organisation1"

    # Ensure Audience syn External unenrol action is set to "Disable course enrolment and remove roles"
    # Set the enrolment plugin unenrolment actions
    And I navigate to "Audience sync" node in "Site administration > Plugins > Enrolments"
    And I select "Disable course enrolment and remove roles" from the "External unenrol action" singleselect
    And I press "Save changes"
    And I navigate to "Program" node in "Site administration > Plugins > Enrolments"
    And I select "Disable course enrolment" from the "External unenrol action" singleselect
    And I press "Save changes"
    And I log out

  Scenario: Run clean_enrolment_plugins_task first
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
    Then "Learner" "link" should exist
    And I log out

    # User can still access the course until the cron is run
    When I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

    # Now run the cron tasks and check the user's access to the course
    When I run the scheduled task "\totara_program\task\clean_enrolment_plugins_task"
    And I run the scheduled task "\totara_program\task\assignments_deferred_task"
    And I run the scheduled task "\totara_program\task\user_assignments_task"
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
    Then "Learner" "link" should exist
    And I log out

    # Run cron again and check the user's access to the course
    When I run the scheduled task "\totara_program\task\clean_enrolment_plugins_task"
    And I run the scheduled task "\totara_program\task\assignments_deferred_task"
    And I run the scheduled task "\totara_program\task\user_assignments_task"

    # learner1 should be able to access the course again
    And I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

  Scenario: Run clean_enrolment_plugins_task second
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
    Then "Learner" "link" should exist
    And I log out

    # User can still access the course until the cron is run
    When I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

    # Now run the cron tasks and check the user's access to the course
    When I run the scheduled task "\totara_program\task\assignments_deferred_task"
    And I run the scheduled task "\totara_program\task\clean_enrolment_plugins_task"
    And I run the scheduled task "\totara_program\task\user_assignments_task"
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
    Then "Learner" "link" should exist
    And I log out

    # Run cron again and check the user's access to the course
    When I run the scheduled task "\totara_program\task\assignments_deferred_task"
    And I run the scheduled task "\totara_program\task\clean_enrolment_plugins_task"
    And I run the scheduled task "\totara_program\task\user_assignments_task"

    # learner1 should be able to access the course again
    And I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

  Scenario: Run clean_enrolment_plugins_task last
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
    Then "Learner" "link" should exist
    And I log out

    # User can still access the course until the cron is run
    When I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out

    # Now run the cron tasks and check the user's access to the course
    When I run the scheduled task "\totara_program\task\assignments_deferred_task"
    And I run the scheduled task "\totara_program\task\user_assignments_task"
    And I run the scheduled task "\totara_program\task\clean_enrolment_plugins_task"
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
    Then "Learner" "link" should exist
    And I log out

    # Run cron again and check the user's access to the course
    When I run the scheduled task "\totara_program\task\assignments_deferred_task"
    And I run the scheduled task "\totara_program\task\user_assignments_task"
    And I run the scheduled task "\totara_program\task\clean_enrolment_plugins_task"

    # learner1 should be able to access the course again
    And I log in as "learner1"
    Then I should see "Test Program 1"
    When I follow "Test Program 1"
    Then I should see "Topic 1"
    And I log out
