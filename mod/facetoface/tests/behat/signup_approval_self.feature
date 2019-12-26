@mod @mod_facetoface @totara @javascript
Feature: Seminar Signup Self Approval
  In order to signup to classroom connect
  As a learner
  I need to aggree to the terms and conditions


  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username    | firstname | lastname | email              |
      | sysapprover | Terry     | Ter      | terry@example.com  |
      | actapprover | Larry     | Lar      | larry@example.com  |
      | teacher     | Freddy    | Fred     | freddy@example.com |
      | trainer     | Benny     | Ben      | benny@example.com  |
      | manager     | Cassy     | Cas      | cassy@example.com  |
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
      | trainer | CCC    | teacher        |
      | manager | CCC    | teacher        |
      | jimmy   | CCC    | student        |
      | timmy   | CCC    | student        |
      | sammy   | CCC    | student        |
      | sally   | CCC    | student        |
    And the following job assignments exist:
      | user  | manager |
      | jimmy | manager |
      | timmy | manager |
      | sammy | manager |
    And I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_approvaloptions[approval_none]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_manager]" "checkbox"
    And I press "Save changes"
    And I navigate to "Activity defaults" node in "Site administration > Seminars"
    And I set the following fields to these values:
      | Terms and conditions | Blah Blah Blah, agree? |
    And I press "Save changes"
    And I am on "Classroom Connect Course" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Classroom Connect       |
      | Description       | Classroom Connect Tests |
      | approvaloptions   | approval_admin          |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 10   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 0    |
    And I press "OK"
    And I set the following fields to these values:
      | capacity              | 10   |
    And I press "Save changes"

  Scenario: Student signs up and self approves
    When I am on "Classroom Connect Course" course homepage
    And I follow "View all events"
    And I follow "Edit settings"
    And I expand all fieldsets
    Then I should see "Blah Blah Blah, agree?"

    When I set the following fields to these values:
        | approval_termsandconds | Do the work, don't be a nuisance. agreed? |
    And I press "Save and display"
    And I log out
    And I log in as "jimmy"
    And I am on "Classroom Connect Course" course homepage
    And I should see "Sign-up"
    And I follow "Sign-up"
    Then I should see "Self authorisation"

    When I press "Sign-up"
    Then I should see "Required"

    When I follow "Terms and conditions"
    Then I should see "Do the work, don't be a nuisance. agreed?"

    When I press "Close"
    And I click on "authorisation" "checkbox"
    When I press "Sign-up"
    Then I should see "Your request was accepted"
