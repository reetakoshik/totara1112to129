@core_enrol @javascript @totara
Feature: Suspend enrolled course users
  In order to ensure that suspended users can't access courses after suspension
  We must verify that suspension works properly for all course enrolment methods

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher   | First    | teacher1@example.com |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |
      | manager1 | Manager   | One      | manager1@example.com |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    # Ensure Audience syn External unenrol action is set to "Disable course enrolment and remove roles"
    And I log in as "admin"
    # Set the enrolment plugin unenrolment actions
    And I navigate to "Audience sync" node in "Site administration > Plugins > Enrolments"
    And I select "Disable course enrolment and remove roles" from the "External unenrol action" singleselect
    And I press "Save changes"
    And I log out


  Scenario: Suspend users that were manually enrolled
    Given the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | learner1 | C1     | student        |
      | learner2 | C1     | student        |

    # Check learner1 is enrolled and can access the course
    When I log in as "learner1"
    Then I should see "Course 1"
    When I follow "Course 1"
    Then I should see "Topic 1"
    And I log out


    When I log in as "admin"
    And I navigate to "Courses and categories" node in "Site administration > Courses"
    Then I should see "Course 1"

    When I follow "Course 1"
    And I follow "Enrolled users"
    Then I should see "Learner One"

    When I click on "Unenrol" "link" in the "Learner One" "table_row"
    And I press "Continue"
    Then I should not see "Learner One"
    And I log out

    # No cron job is required in this case
    When I log in as "learner1"
    And I am on "Course 1" course homepage
    Then I should see "You can not enrol yourself in this course"

  @totara_cohort
  Scenario: Suspend users enrolled via a set audience
    Given the following "cohorts" exist:
      | name             | idnumber | cohorttype |
      | Set audience     | S1       | 1          |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner1 | S1     |
      | learner2 | S1     |

    # Assign audience to course
    And I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Set audience"
    And I follow "Enrolled learning"
    And I press "Add courses"
    And I follow "Miscellaneous"
    And I follow "Course 1"
    And I press "Save"
    And I run the scheduled task "\enrol_cohort\task\sync_members"
    And I log out

    When I log in as "learner1"
    Then I should see "Course 1"
    When I follow "Course 1"
    Then I should see "Topic 1"
    And I log out

    When I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Set audience"
    And I follow "Edit members"
    And I click on "Learner One (learner1@example.com)" "option" in the "#removeselect" "css_element"
    And I click on "remove" "button"
    And I log out

    # No cron job is required in this case
    When I log in as "learner1"
    And I am on "Course 1" course homepage
    Then I should see "You can not enrol yourself in this course"

  @totara_cohort
  Scenario: Suspend users enrolled via a dynamic audience by adding exclusion rule
    # Create the dynamic audience
    Given the following "cohorts" exist:
      | name             | idnumber | cohorttype |
      | Dynamic audience | D2       | 2          |
    And I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Dynamic audience"
    And I switch to "Rule sets" tab
    And I set the field "id_addrulesetmenu" to "First name"
    And I wait "1" seconds
    And I set the field "id_equal" to "starts with"
    And I set the field "listofvalues" to "learner"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I wait "1" seconds
    And I press "Approve changes"
    Then I should see "User's first name starts with \"learner\""

    # Assign audience to course
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Dynamic audience"
    And I follow "Enrolled learning"
    And I press "Add courses"
    And I follow "Miscellaneous"
    And I follow "Course 1"
    And I press "Save"
    And I run the scheduled task "\enrol_cohort\task\sync_members"
    And I log out

    When I log in as "learner1"
    Then I should see "Course 1"
    When I follow "Course 1"
    Then I should see "Topic 1"
    And I log out

    # Remove learner1 from the audience
    When I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Dynamic audience"
    And I switch to "Rule sets" tab
    And I set the field "id_addrulemenu2" to "Username"
    And I wait "1" seconds
    And I set the field "id_equal" to "is not equal to"
    And I set the field "listofvalues" to "learner1"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I wait "1" seconds
    And I press "Approve changes"
    Then I should see "User's username is not equal to \"learner1\""
    And I log out

    # No cron job is required in this case as updating of the cohort rules triggers the neccessary updates
    When I log in as "learner1"
    And I am on "Course 1" course homepage
    Then I should see "You can not enrol yourself in this course"

  @totara_cohort
  Scenario: Suspend users enrolled via a dynamic audience by changing user's profile
    # Create the dynamic audience
    Given the following "cohorts" exist:
      | name             | idnumber | cohorttype |
      | Dynamic audience | D2       | 2          |
    And I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Dynamic audience"
    And I switch to "Rule sets" tab
    And I set the field "id_addrulesetmenu" to "First name"
    And I wait "1" seconds
    And I set the field "id_equal" to "starts with"
    And I set the field "listofvalues" to "learner"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I wait "1" seconds
    And I press "Approve changes"
    Then I should see "User's first name starts with \"learner\""

    # Assign audience to course
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Dynamic audience"
    And I follow "Enrolled learning"
    And I press "Add courses"
    And I follow "Miscellaneous"
    And I follow "Course 1"
    And I press "Save"
    And I run the scheduled task "\enrol_cohort\task\sync_members"
    And I log out

    When I log in as "learner1"
    Then I should see "Course 1"
    When I follow "Course 1"
    Then I should see "Topic 1"
    And I log out

    # Remove learner1 from the audience
    When I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner One"
    And I follow "Edit profile"
    And I set the following fields to these values:
      | First name | Student |
    And I press "Update profile"
    Then I should see "Student One"
    And I log out

    # User can still access the course until the cron is run
    When I log in as "learner1"
    And I am on "Course 1" course homepage
    Then I should see "Topic 1"
    And I log out

    # Now run the cron task
    When I run the scheduled task "\enrol_cohort\task\sync_members"
    And I log in as "learner1"
    And I am on "Course 1" course homepage
    Then I should see "You can not enrol yourself in this course"

