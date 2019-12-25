@totara @totara_cohort @totara_certification @javascript
Feature: Test the certification completion date rule in dynamic audiences
  I need to be able to select an audience as a rule in a dynamic audience

  Background:
    Given I am on a totara site
    And the following "cohorts" exist:
      | name       | idnumber | cohorttype |
      | Audience 1 | AUD001   | 2          |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Learner1  | One      | learner1@example.com |
      | learner2 | Learner2  | Two      | learner2@example.com |
      | learner3 | Learner3  | Three    | learner3@example.com |
      | learner4 | Learner4  | Four     | learner4@example.com |
      | learner5 | Learner5  | Five     | learner5@example.com |
      | learner6 | Learner6  | Six      | learner5@example.com |
    And the following "courses" exist:
      | fullname   | shortname | format | enablecompletion | completionstartonenrol |
      | Course One | course1   | topics | 1                | 1                      |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname        | shortname | activeperiod | windowperiod | recertifydatetype |
      | Certification 1 | cert1     | 1 year       | 1 month      | 1                 |
      | Certification 2 | cert2     | 1 year       | 1 month      | 1                 |
    And the following "program assignments" exist in "totara_program" plugin:
      | program | user     |
      | cert1   | learner1 |
      | cert1   | learner2 |
      | cert1   | learner3 |
      | cert2   | learner4 |
      | cert2   | learner5 |
      | cert2   | learner6 |
    And I log in as "admin"
    And I set the following administration settings values:
      | menulifetime   | 0       |
      | enableprograms | Disable |
    And I set self completion for "Course One" in the "Miscellaneous" category
    And I am on "Certification 1" certification homepage
    And I press "Edit certification details"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#programcontent_ce" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course One" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I press "Save changes"
    And I click on "Save all changes" "button"

  Scenario: Ensure certification completion date rule form validation is correct
    Given I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Certification completion date"
    And I click on "Miscellaneous" "link"
    And I click on "Certification 1" "link"
    Then I should not see "Please specify a valid date"
    And I should not see "Please specify a valid number of days"

    When I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Please specify a valid date"
    And I should not see "Please specify a valid number of days"

    When I set the following fields to these values:
      | fixedordynamic1 | 1          |
      | completiondate  | 25/12/2018 |
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should not see "Please specify a valid date"
    And I should not see "Please specify a valid number of days"
    Then I should see "User's certification completion date is before 25/12/2018 \"Certification 1\"" in the "Ruleset #1" "fieldset"

    When I click on "Edit" "link" in the "ul.cohort-editing_ruleset" "css_element"
    And I click on "fixedordynamic2" "radio"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should not see "Please specify a valid date"
    And I should see "Please specify a valid number of days"

    When I set the following fields to these values:
      | durationdate | 5 |
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should not see "Please specify a valid date"
    And I should not see "Please specify a valid number of days"
    And I should see "User's certification completion date is within the past 5 day(s) \"Certification 1\"" in the "Ruleset #1" "fieldset"

  Scenario: Ensure certification completion date rules are not created when certifications are not selected
    Given I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Certification completion date"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should not see "User's certification completion date is"

  Scenario: Use certification completion date rule, user completed before date
    # User not certified, check date before, date in past.
    Given I navigate to "Audiences" node in "Site administration > Audiences"
    When I follow "Audience 1"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Certification completion date"
    And I click on "Miscellaneous" "link"
    And I click on "Certification 1" "link"
    And I set the following fields to these values:
      | fixedordynamic1 | 1          |
      | beforeaftermenu | before     |
      | completiondate  | 20/10/2015 |
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I should see "User's certification completion date is before 20/10/2015 \"Certification 1\"" in the "Ruleset #1" "fieldset"
    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should not see "Learner1 One"
    And I should not see "Learner2 Two"
    And I should not see "Learner3 Three"
    And I should not see "Learner4 Four"
    And I should not see "Learner5 Five"
    And I should not see "Learner6 Six"

    # User not certified, check date before, date in future.
    Given I switch to "Rule sets" tab
    And I click on "Edit" "link" in the "ul.cohort-editing_ruleset" "css_element"
    And I set the following fields to these values:
      | fixedordynamic1 | 1          |
      | beforeaftermenu | before     |
      | completiondate  | 20/10/2050 |
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I should see "User's certification completion date is before 20/10/2050 \"Certification 1\"" in the "Ruleset #1" "fieldset"
    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should not see "Learner1 One"
    And I should not see "Learner2 Two"
    And I should not see "Learner3 Three"
    And I should not see "Learner4 Four"
    And I should not see "Learner5 Five"
    And I should not see "Learner6 Six"

    # Now certify learner 1 on cert 1
    And I log out
    And I log in as "learner1"
    And I am on "Course One" course homepage
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    Then I should see "You have already completed this course"
    And I log out
    And I run the "\totara_cohort\task\update_cohort_task" task
    And I log in as "admin"

    # User certified, check date before, date in future.
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab
    And I should see "User's certification completion date is before 20/10/2050 \"Certification 1\"" in the "Ruleset #1" "fieldset"
    And I switch to "Members" tab
    Then I should see "Learner1 One"
    And I should not see "Learner2 Two"
    And I should not see "Learner3 Three"
    And I should not see "Learner4 Four"
    And I should not see "Learner5 Five"
    And I should not see "Learner6 Six"

    # User certified, check date before, date in past.
    Given I switch to "Rule sets" tab
    And I click on "Edit" "link" in the "ul.cohort-editing_ruleset" "css_element"
    And I set the following fields to these values:
      | fixedordynamic1 | 1          |
      | beforeaftermenu | before     |
      | completiondate  | 20/10/2015 |
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I should see "User's certification completion date is before 20/10/2015 \"Certification 1\"" in the "Ruleset #1" "fieldset"
    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should not see "Learner1 One"
    And I should not see "Learner2 Two"
    And I should not see "Learner3 Three"
    And I should not see "Learner4 Four"
    And I should not see "Learner5 Five"
    And I should not see "Learner6 Six"

  Scenario: Use certification completion date rule, user completed after date
    # User not certified, check date before, date in past.
    Given I navigate to "Audiences" node in "Site administration > Audiences"
    When I follow "Audience 1"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Certification completion date"
    And I click on "Miscellaneous" "link"
    And I click on "Certification 1" "link"
    And I set the following fields to these values:
      | fixedordynamic1 | 1          |
      | beforeaftermenu | after      |
      | completiondate  | 20/10/2015 |
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I should see "User's certification completion date is on and after 20/10/2015 \"Certification 1\"" in the "Ruleset #1" "fieldset"
    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should not see "Learner1 One"
    And I should not see "Learner2 Two"
    And I should not see "Learner3 Three"
    And I should not see "Learner4 Four"
    And I should not see "Learner5 Five"
    And I should not see "Learner6 Six"

    # User not certified, check date before, date in future.
    Given I switch to "Rule sets" tab
    And I click on "Edit" "link" in the "ul.cohort-editing_ruleset" "css_element"
    And I set the following fields to these values:
      | fixedordynamic1 | 1          |
      | beforeaftermenu | after      |
      | completiondate  | 20/10/2050 |
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I should see "User's certification completion date is on and after 20/10/2050 \"Certification 1\"" in the "Ruleset #1" "fieldset"
    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should not see "Learner1 One"
    And I should not see "Learner2 Two"
    And I should not see "Learner3 Three"
    And I should not see "Learner4 Four"
    And I should not see "Learner5 Five"
    And I should not see "Learner6 Six"

    # Now certify learner 1 on cert 1
    And I log out
    And I log in as "learner1"
    And I am on "Course One" course homepage
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    Then I should see "You have already completed this course"
    And I log out
    And I run the "\totara_cohort\task\update_cohort_task" task
    And I log in as "admin"

    # User certified, check date before, date in future.
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab
    And I should see "User's certification completion date is on and after 20/10/2050 \"Certification 1\"" in the "Ruleset #1" "fieldset"
    And I switch to "Members" tab
    Then I should not see "Learner1 One"
    And I should not see "Learner2 Two"
    And I should not see "Learner3 Three"
    And I should not see "Learner4 Four"
    And I should not see "Learner5 Five"
    And I should not see "Learner6 Six"

    # User certified, check date before, date in past.
    Given I switch to "Rule sets" tab
    And I click on "Edit" "link" in the "ul.cohort-editing_ruleset" "css_element"
    And I set the following fields to these values:
      | fixedordynamic1 | 1          |
      | beforeaftermenu | after      |
      | completiondate  | 20/10/2015 |
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I should see "User's certification completion date is on and after 20/10/2015 \"Certification 1\"" in the "Ruleset #1" "fieldset"
    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "Learner1 One"
    And I should not see "Learner2 Two"
    And I should not see "Learner3 Three"
    And I should not see "Learner4 Four"
    And I should not see "Learner5 Five"
    And I should not see "Learner6 Six"

  Scenario: Use certification completion date rule, user completed within the previous days
    # User not certified.
    Given I navigate to "Audiences" node in "Site administration > Audiences"
    When I follow "Audience 1"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Certification completion date"
    And I click on "Miscellaneous" "link"
    And I click on "Certification 1" "link"
    And I click on "fixedordynamic2" "radio"
    And I set the following fields to these values:
      | durationmenu | within the previous |
      | durationdate | 5                   |
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I should see "User's certification completion date is within the past 5 day(s) \"Certification 1\"" in the "Ruleset #1" "fieldset"
    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should not see "Learner1 One"
    And I should not see "Learner2 Two"
    And I should not see "Learner3 Three"
    And I should not see "Learner4 Four"
    And I should not see "Learner5 Five"
    And I should not see "Learner6 Six"

    # Now certify learner 1 on cert 1
    And I log out
    And I log in as "learner1"
    And I am on "Course One" course homepage
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    Then I should see "You have already completed this course"
    And I log out
    And I run the "\totara_cohort\task\update_cohort_task" task
    And I log in as "admin"

    # User certified.
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab
    And I should see "User's certification completion date is within the past 5 day(s) \"Certification 1\"" in the "Ruleset #1" "fieldset"
    And I switch to "Members" tab
    Then I should see "Learner1 One"
    And I should not see "Learner2 Two"
    And I should not see "Learner3 Three"
    And I should not see "Learner4 Four"
    And I should not see "Learner5 Five"
    And I should not see "Learner6 Six"

  Scenario: Use certification completion date rule, user completed before previous days
    # User not certified.
    Given I navigate to "Audiences" node in "Site administration > Audiences"
    When I follow "Audience 1"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Certification completion date"
    And I click on "Miscellaneous" "link"
    And I click on "Certification 1" "link"
    And I click on "fixedordynamic2" "radio"
    And I set the following fields to these values:
      | durationmenu | before previous |
      | durationdate | 5               |
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I should see "User's certification completion date is more than 5 day(s) ago \"Certification 1\"" in the "Ruleset #1" "fieldset"
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should not see "Learner1 One"
    And I should not see "Learner2 Two"
    And I should not see "Learner3 Three"
    And I should not see "Learner4 Four"
    And I should not see "Learner5 Five"
    And I should not see "Learner6 Six"

    # Now certify learner 1 on cert 1
    And I log out
    And I log in as "learner1"
    And I am on "Course One" course homepage
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    Then I should see "You have already completed this course"
    And I log out
    And I run the "\totara_cohort\task\update_cohort_task" task
    And I log in as "admin"

    # User certified.
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab
    And I should see "User's certification completion date is more than 5 day(s) ago \"Certification 1\"" in the "Ruleset #1" "fieldset"
    And I switch to "Members" tab
    Then I should not see "Learner1 One"
    And I should not see "Learner2 Two"
    And I should not see "Learner3 Three"
    And I should not see "Learner4 Four"
    And I should not see "Learner5 Five"
    And I should not see "Learner6 Six"

    # Wind back completion by a month.
    When I wind back certification dates by 1 months

    # Learner 1 should now be in the audience.
    And I switch to "Rule sets" tab
    And I click on "Edit" "link" in the "ul.cohort-editing_ruleset" "css_element"
    And I set the following fields to these values:
      | durationmenu | before previous |
      | durationdate | 5               |
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I should see "User's certification completion date is more than 5 day(s) ago \"Certification 1\"" in the "Ruleset #1" "fieldset"
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "Learner1 One"
    And I should not see "Learner2 Two"
    And I should not see "Learner3 Three"
    And I should not see "Learner4 Four"
    And I should not see "Learner5 Five"
    And I should not see "Learner6 Six"

    # Adjust the rule to 35 days, learner 1 should now be added.
    When I switch to "Rule sets" tab
    And I click on "Edit" "link" in the "ul.cohort-editing_ruleset" "css_element"
    And I set the following fields to these values:
      | durationmenu | before previous |
      | durationdate | 35              |
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I should see "User's certification completion date is more than 35 day(s) ago \"Certification 1\"" in the "Ruleset #1" "fieldset"
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should not see "Learner1 One"
    And I should not see "Learner2 Two"
    And I should not see "Learner3 Three"
    And I should not see "Learner4 Four"
    And I should not see "Learner5 Five"
    And I should not see "Learner6 Six"
