@totara_cohort @totara
Feature: Verify membership options work correctly.

  Background:
    Given I am on a totara site
    # Create some users and set the last login to 1st April 2015.
    And the following "users" exist:
      # VERY IMPORTANT NOTE:
      # The original test assumed the code used lastlogin field but the code
      # actually uses the *currentlogin* field - which is the totally wrong.
      # The flaw was exposed via TL-8803 but since this bug will only be fixed
      # in TL-10440 and only > Totara 9.0, the test data has been fixed to have
      # a currentlogin field.
      | username  | firstname   | lastname   | lastlogin     | currentlogin  |
      | learner10 | firstname10 | lastname10 | 1427842800    | 1427842800    |
      | learner11 | firstname11 | lastname11 | 1427842800    | 1427842800    |
      | learner20 | firstname20 | lastname20 | 1427842800    | 1427842800    |
      | learner21 | firstname21 | lastname21 | 1427842800    | 1427842800    |
      | learner30 | firstname30 | lastname30 | 1427842800    | 1427842800    |
      | learner31 | firstname31 | lastname31 | 1427842800    | 1427842800    |
    And the following "cohorts" exist:
      | name       | idnumber | cohorttype |
      | Audience 1 | A1       | 2          |

  @javascript @_alert
  Scenario: Verify members can be added to the audience while both membership options are active.

    # Navigate to Audiences.
    Given I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab

    # Change the audience rule set to 'Or'
    When I click on "OR (members are in any rule set)" "radio"
    Then I should see "Audience rules changed"
    And "Approve changes" "button" should be visible
    And "Cancel changes" "button" should be visible

    # Add a rule set.
    When I set the field "addrulesetmenu" to "Username"
    Then I should see "Add a rule based on User's username"
    When I set the field "listofvalues" to "learner1"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Ruleset #1"

    # Add another rule set.
    When I set the field "addrulesetmenu" to "Username"
    Then I should see "Add a rule based on User's username"
    When I set the field "listofvalues" to "learner2"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Ruleset #2"

    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "firstname10 lastname10" in the "#cohort_members" "css_element"
    And I should see "firstname11 lastname11" in the "#cohort_members" "css_element"
    And I should see "firstname20 lastname20" in the "#cohort_members" "css_element"
    And I should see "firstname21 lastname21" in the "#cohort_members" "css_element"

    # Delete one of the rule sets to check delete works okay.
    When I switch to "Rule sets" tab
    And I click on "Delete" "link" confirming the dialogue
    Then I should see "Audience rules changed"

    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should not see "firstname10 lastname10" in the "#cohort_members" "css_element"
    And I should not see "firstname11 lastname11" in the "#cohort_members" "css_element"
    And I should see "firstname20 lastname20" in the "#cohort_members" "css_element"
    And I should see "firstname21 lastname21" in the "#cohort_members" "css_element"

  @javascript
  Scenario: Verify members are not added to the audience when 'add' membership options is inactive.

    # Navigate to Audiences.
    Given I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab

    # Change the audience rule set to 'Or'
    When I click on "OR (members are in any rule set)" "radio"
    Then I should see "Audience rules changed"
    And "Approve changes" "button" should be visible
    And "Cancel changes" "button" should be visible

    # Add a rule set.
    When I set the field "addrulesetmenu" to "Username"
    Then I should see "Add a rule based on User's username"
    When I set the field "listofvalues" to "learner1"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Ruleset #1"

    # Check the first rule set's data is present.
    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "firstname10 lastname10" in the "#cohort_members" "css_element"
    And I should see "firstname11 lastname11" in the "#cohort_members" "css_element"

    # Make the 'add' membership option inactive.
    When I switch to "Rule sets" tab
    And I click on "Make a user a member when they meet rule sets criteria" "checkbox"
    Then I should see "Audience rules changed"

    # Add another rule set but with the 'add' membership option active
    When I set the field "addrulesetmenu" to "Username"
    Then I should see "Add a rule based on User's username"
    When I set the field "listofvalues" to "learner2"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Ruleset #2"

    # Verify that the Ruleset #2 mebers haven't been added.
    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "firstname10 lastname10" in the "#cohort_members" "css_element"
    And I should see "firstname11 lastname11" in the "#cohort_members" "css_element"
    And I should not see "firstname20 lastname20" in the "#cohort_members" "css_element"
    And I should not see "firstname21 lastname21" in the "#cohort_members" "css_element"

    # Make the 'add' membership option active again so can verify members can still be added.
    When I switch to "Rule sets" tab
    And I click on "Make a user a member when they meet rule sets criteria" "checkbox"
    Then I should see "Audience rules changed"

    # Verify that the Ruleset #2 mebers haven't been added.
    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "firstname10 lastname10" in the "#cohort_members" "css_element"
    And I should see "firstname11 lastname11" in the "#cohort_members" "css_element"
    And I should see "firstname20 lastname20" in the "#cohort_members" "css_element"
    And I should see "firstname21 lastname21" in the "#cohort_members" "css_element"

  @javascript @_alert
  Scenario: Verify members are not removed from the audience when 'remove' membership options is inactive.

    # Navigate to Audiences.
    Given I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab

    # Change the audience rule set to 'Or'
    When I click on "OR (members are in any rule set)" "radio"
    Then I should see "Audience rules changed"
    And "Approve changes" "button" should be visible
    And "Cancel changes" "button" should be visible

    # Add a rule set.
    When I set the field "addrulesetmenu" to "Username"
    Then I should see "Add a rule based on User's username"
    When I set the field "listofvalues" to "learner1"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Ruleset #1"

    # Add another rule set.
    When I set the field "addrulesetmenu" to "Username"
    Then I should see "Add a rule based on User's username"
    When I set the field "listofvalues" to "learner2"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Ruleset #2"

    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "firstname10 lastname10" in the "#cohort_members" "css_element"
    And I should see "firstname11 lastname11" in the "#cohort_members" "css_element"
    And I should see "firstname20 lastname20" in the "#cohort_members" "css_element"
    And I should see "firstname21 lastname21" in the "#cohort_members" "css_element"

    # Make the 'remove' membership option inactive.
    When I switch to "Rule sets" tab
    And I click on "Remove a user's membership when they no longer meet the rule sets criteria" "checkbox"
    Then I should see "Audience rules changed"

    # Delete one of the rule sets. The members shall still all be present.
    When I click on "Delete" "link" confirming the dialogue
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "firstname10 lastname10" in the "#cohort_members" "css_element"
    And I should see "firstname11 lastname11" in the "#cohort_members" "css_element"
    And I should see "firstname20 lastname20" in the "#cohort_members" "css_element"
    And I should see "firstname21 lastname21" in the "#cohort_members" "css_element"

    # Make the 'remove' membership option active again.
    When I switch to "Rule sets" tab
    And I click on "Remove a user's membership when they no longer meet the rule sets criteria" "checkbox"
    Then I should see "Audience rules changed"

    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should not see "firstname10 lastname10" in the "#cohort_members" "css_element"
    And I should not see "firstname11 lastname11" in the "#cohort_members" "css_element"
    And I should see "firstname20 lastname20" in the "#cohort_members" "css_element"
    And I should see "firstname21 lastname21" in the "#cohort_members" "css_element"

  @javascript
  Scenario: Verify a user is added the audience when they meet the criteria and the 'add' memeber option is active.

    # Navigate to Audiences.
    Given I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab

    # Change the audience rule set to 'Or'
    When I click on "OR (members are in any rule set)" "radio"
    Then I should see "Audience rules changed"
    And "Approve changes" "button" should be visible
    And "Cancel changes" "button" should be visible

    # Add a rule set.
    When I set the field "addrulesetmenu" to "Username"
    Then I should see "Add a rule based on User's username"
    When I set the field "listofvalues" to "learner1"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Ruleset #1"

    # Add another rule set.
    When I set the field "addrulesetmenu" to "Username"
    Then I should see "Add a rule based on User's username"
    When I set the field "listofvalues" to "learner2"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Ruleset #2"

    # Add some extra users and run the cron to make sure they're given audience membership.
    When I press "Approve changes"
    And the following "users" exist:
      | username  | firstname   | lastname   |
      | learner12 | firstname12 | lastname12 |
      | learner22 | firstname22 | lastname22 |
      | learner32 | firstname32 | lastname32 |
    And I run the "\totara_cohort\task\update_cohort_task" task

    # Navigate to Audiences and check the new users has been added.
    When I am on homepage
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Members" tab
    Then I should see "firstname12 lastname12" in the "#cohort_members" "css_element"
    And I should see "firstname22 lastname22" in the "#cohort_members" "css_element"
    # The following user shouldnot be added because it doesn't meet the rulset critera.
    And I should not see "firstname32 lastname32" in the "#cohort_members" "css_element"

  @javascript
  Scenario: Verify a user is not added the audience when they meet the criteria because 'add' member option is inactive.

    # Navigate to Audiences.
    Given I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab

    # Change the audience rule set to 'Or'
    When I click on "OR (members are in any rule set)" "radio"
    Then I should see "Audience rules changed"
    And "Approve changes" "button" should be visible
    And "Cancel changes" "button" should be visible

    # Add a rule set.
    When I set the field "addrulesetmenu" to "Username"
    Then I should see "Add a rule based on User's username"
    When I set the field "listofvalues" to "learner1"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Ruleset #1"

    # Add another rule set.
    When I set the field "addrulesetmenu" to "Username"
    Then I should see "Add a rule based on User's username"
    When I set the field "listofvalues" to "learner2"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Ruleset #2"

    # Make 'add' member option inactive.
    When I press "Approve changes"
    And I click on "Make a user a member when they meet rule sets criteria" "checkbox"
    Then I should see "Audience rules changed"

    # Add some extra users and run the cron to make sure they're given audience membership.
    When I press "Approve changes"
    And the following "users" exist:
      | username  | firstname   | lastname   |
      | learner12 | firstname12 | lastname12 |
      | learner22 | firstname22 | lastname22 |
      | learner32 | firstname32 | lastname32 |
    And I run the "\totara_cohort\task\update_cohort_task" task

    # Navigate to Audiences and check the new users have been added.
    When I am on homepage
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Members" tab
    Then I should not see "firstname12 lastname12" in the "#cohort_members" "css_element"
    And I should not see "firstname22 lastname22" in the "#cohort_members" "css_element"
    And I should not see "firstname32 lastname32" in the "#cohort_members" "css_element"


  @javascript
  Scenario: Verify user is removed from the audience when criteria is met and 'remove' option is active.

    # Navigate to Audiences.
    Given I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab

    # Change the audience rule set to 'Or'
    When I click on "OR (members are in any rule set)" "radio"
    Then I should see "Audience rules changed"
    And "Approve changes" "button" should be visible
    And "Cancel changes" "button" should be visible

    # Add a rule set to include users containing 'learner1'.
    When I set the field "addrulesetmenu" to "Username"
    Then I should see "Add a rule based on User's username"
    When I set the field "listofvalues" to "learner1"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Ruleset #1"

    # Add a rule set to include users containing 'learner2'.
    When I set the field "addrulesetmenu" to "Username"
    Then I should see "Add a rule based on User's username"
    When I set the field "listofvalues" to "learner2"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Ruleset #2"

    # Extend one rule set to include anyone who has not logged in within the last week.
    When I set the field "addrulemenu1" to "Last login date"
    Then I should see "Add a rule based on User's last login date"
    When I click on "id_fixedordynamic_2" "radio"
    And I set the field "durationdate" to "7"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I wait "1" seconds
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "firstname10 lastname10" in the "#cohort_members" "css_element"
    And I should see "firstname11 lastname11" in the "#cohort_members" "css_element"
    And I should see "firstname20 lastname20" in the "#cohort_members" "css_element"
    And I should see "firstname21 lastname21" in the "#cohort_members" "css_element"

    When I log out
    And I log in as "learner10"
    And I log out
    And I log in as "learner11"
    And I log out
    And I run the "\totara_cohort\task\update_cohort_task" task

    # Navigate to Audiences and check the new users have been removed.
    When I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Members" tab
    Then I should not see "firstname10 lastname10" in the "#cohort_members" "css_element"
    And I should not see "firstname11 lastname11" in the "#cohort_members" "css_element"
    And I should see "firstname20 lastname20" in the "#cohort_members" "css_element"
    And I should see "firstname21 lastname21" in the "#cohort_members" "css_element"

  @javascript
  Scenario: Verify user is not removed from the audience when criteria is met because 'remove' option is inactive.

    # Navigate to Audiences.
    Given I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Rule sets" tab

    # Change the audience rule set to 'Or'
    When I click on "OR (members are in any rule set)" "radio"
    Then I should see "Audience rules changed"
    And "Approve changes" "button" should be visible
    And "Cancel changes" "button" should be visible

    # Make the 'remove' membership option inactive.

    # Add a rule set to include users containing 'learner1'
    When I click on "Remove a user's membership when they no longer meet the rule sets criteria" "checkbox"
    And I set the field "addrulesetmenu" to "Username"
    Then I should see "Add a rule based on User's username"
    When I set the field "listofvalues" to "learner1"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Ruleset #1"

    # Add a rule set to include users containing 'learner2'.
    When I set the field "addrulesetmenu" to "Username"
    Then I should see "Add a rule based on User's username"
    When I set the field "listofvalues" to "learner2"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Ruleset #2"

    # Extend one rule set to include anyone who has not logged in within the last week.
    When I set the field "addrulemenu1" to "Last login date"
    Then I should see "Add a rule based on User's last login date"
    When I click on "id_fixedordynamic_2" "radio"
    And I set the field "durationdate" to "7"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I wait "1" seconds
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "firstname10 lastname10" in the "#cohort_members" "css_element"
    And I should see "firstname11 lastname11" in the "#cohort_members" "css_element"
    And I should see "firstname20 lastname20" in the "#cohort_members" "css_element"
    And I should see "firstname21 lastname21" in the "#cohort_members" "css_element"

    When I log out
    And I log in as "learner10"
    And I log out
    And I log in as "learner11"
    And I log out
    And I run the "\totara_cohort\task\update_cohort_task" task

    # Navigate to Audiences and check the new users have NOT been removed.
    When I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Members" tab
    Then I should see "firstname10 lastname10" in the "#cohort_members" "css_element"
    And I should see "firstname11 lastname11" in the "#cohort_members" "css_element"
    And I should see "firstname20 lastname20" in the "#cohort_members" "css_element"
    And I should see "firstname21 lastname21" in the "#cohort_members" "css_element"
