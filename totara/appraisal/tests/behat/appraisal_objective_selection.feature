@totara @totara_appraisal @javascript
Feature: Test appraisal objective selection

  Scenario: Test Choose objective to review when the plan has being completed comes up with proper error
    When I am on a totara site
    And I log in as "admin"
    And the following "users" exist:
      | username | firstname | lastname | email |
      | learner  | learner   | one      | l1@example.com |
      | manager   | manager   | one      | m1@exmaple.com |
    And the following job assignments exist:
      | user | fullname | idnumber | manager |
      | learner | Learner1 day job | l1ja | manager |

    # Make audience.
    And I am on site homepage
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Add new audience"
    And I set the following fields to these values:
      | Name         | Test_cohort_name        |
      | Context      | System                  |
      | Audience ID  | 222                     |
      | Description  | Test cohort description |
    And I press "Save changes"
    And I add "learner one (l1@example.com)" user to "222" cohort members

    # Login as admin and create appraisal.
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I press "Create appraisal"
    And I set the following fields to these values:
      | Name | Appraisal review questions test |
      | Description | This is the behat description |
    And I press "Create appraisal"
    # Add a stage and set the completion date to the future.
    And I press "Add stage"

    And I set the following fields to these values:
      | Name | teststage |
    And I set the field "id_timedue_enabled" to "1"
    And I set the field "id_timedue_day" to "13"
    And I set the field "id_timedue_month" to "1"
    And I set the field "id_timedue_year" to "2050"
    # Submit the form like this because there is a button with the same name in the form.
    And I click on ".felement input[type=\"submit\"]" "css_element"

    # Add a new page.
    And I follow "Add new page"
    And I set the following fields to these values:
      | Name | testpage |
    And I click on "Add new page" "button"
    # Add a Objectives from learning plan question.
    And I set the following fields to these values:
      | datatype | Objectives from Learning plan |
    And I click on "#id_submitbutton" "css_element"
    And I set the following fields to these values:
      | Question | testquestion |
      | roles[1][2] | 1         |
    And I click on "Save changes" "button"

    # Add the audience that the user is in.
    And I click on "Assignments" "link"
    And I set the following fields to these values:
      | groupselector | Audience |
    And I click on "Test_cohort_name" "text"
    And I click on "Save" "button"
    And I log out

    # Log in as the manager approve the plan and mark the plan as complete.
    And I log in as "manager"
    And I follow "Team"
    And I follow "Plans"
    And I click on "Create new learning plan" "button"
    And I click on "Create plan" "button"
    And I follow "Objectives"
    And I click on "Add new objective" "button"
    And I set the following fields to these values:
      | Objective Title | objTitle |
    And I click on "Add objective" "button"
    And I click on "Approve" "button"
    And I follow "Overview"
    And I click on "Complete plan" "button"
    And I click on "Complete plan" "button"
    And I log out

    # Activate the appraisal.
    And I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I follow "Appraisal review questions test"
    And I follow "Activate now"
    And I click on "Activate" "button"
    And I log out

    # Log in as the learner and go to latest appraisal.
    And I log in as "learner"
    And I follow "Performance"
    And I follow "Latest Appraisal"
    # Click Choose objectives to review.
    And I click on "Start" "button"
    And I click on "Choose objectives to review" "button"

    # If it didnt work then behat will error from the exception before here.
    And "objTitle" "text" should exist in the "Choose objectives to review" "totaradialogue"
    When I click on "button[title=close]" "css_element" in the "Choose objectives to review" "totaradialogue"
