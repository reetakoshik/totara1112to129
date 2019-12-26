@mod @mod_facetoface @totara
Feature: Seminar Signup Role Approval after creating an event
  In order to signup to classroom connect
  As an admin
  I need to make sure that approval role is setup

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username    | firstname | lastname | email              |
      | teacher     | Freddy    | Fred     | freddy@example.com |
      | jimmy       | Jimmy     | Jim      | jimmy@example.com  |
      | timmy       | Timmy     | Tim      | timmy@example.com  |
      | sammy       | Sammy     | Sam      | sammy@example.com  |
      | sally       | Sally     | Sal      | sally@example.com  |
    And the following "courses" exist:
      | fullname                 | shortname | category |
      | Classroom Connect Course | CCC       | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | CCC    | editingteacher |
      | jimmy   | CCC    | student        |
      | timmy   | CCC    | student        |
      | sammy   | CCC    | student        |
      | sally   | CCC    | student        |
    And the following "activities" exist:
      | activity   | name              | course | idnumber |
      | facetoface | Classroom Connect | CCC    | S10784   |

  @javascript
  Scenario: Learner is trying to sing-up when there is approval role and no trainer appointed.
    Given I log in as "admin"
    And I am on "Classroom Connect Course" course homepage
    And I follow "View all events"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | capacity              | 10   |
    And I press "Save changes"

    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_session_roles[3]" "checkbox"
    And I press "Save changes"
    And I click on "s__facetoface_approvaloptions[approval_role_3]" "checkbox"
    And I press "Save changes"
    And I am on "Classroom Connect Course" course homepage
    And I follow "View all events"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I expand all fieldsets
    And I click on "#id_approvaloptions_approval_role_3" "css_element"
    And I press "Save and display"
    And I log out

    When I log in as "sally"
    And I am on "Classroom Connect Course" course homepage
    And I follow "More info"
    Then I should see "This seminar requires role approval, there are no users assigned to this role. Please contact the site administrator"
    And I log out

    When I log in as "admin"
    And I am on "Classroom Connect Course" course homepage
    And I follow "View all events"
    And I click on "Edit event" "link"
    And I click on "Freddy Fred" "checkbox" in the "#id_trainerroles" "css_element"
    And I press "Save changes"
    Then I should see "Booking open"
    And I log out

    When I log in as "sally"
    And I am on "Classroom Connect Course" course homepage
    And I should see "Request approval"
    And I follow "Request approval"
    Then I should see "Editing Trainer"

    When I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."
