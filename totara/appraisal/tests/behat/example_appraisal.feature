@totara @totara_appraisal
Feature: Complete the example appraisal
  In order to use the example appraisal
  An admin must be able to assign it
  A learner and manager must be able to complete it

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Learner   | One      | learner1@example.com |
      | manager1 | Manager   | One      | manager1@example.com |
    And the following "position" frameworks exist:
      | fullname           | idnumber |
      | Position Framework | posfw    |
    And the following "position" hierarchy exists:
      | fullname     | idnumber | framework |
      | Position One | pos1     | posfw     |
    And the following job assignments exist:
      | user     | fullname         | idnumber | manager  | position |
      | learner1 | Learner1 Day Job | l1ja     | manager1 | pos1     |
    And the following "goal" frameworks exist:
      | fullname       | idnumber |
      | Goal Framework | goalfw   |
    And the following "goal" hierarchy exists:
      | fullname | idnumber | framework |
      | Company Goal One   | goal1    | goalfw    |
      | Company Goal Two   | goal2    | goalfw    |
      | Company Goal Three | goal2    | goalfw    |
    And the following "competency" frameworks exist:
      | fullname             | idnumber |
      | Competency Framework | compfw   |
    And the following "competency" hierarchy exists:
      | fullname         | idnumber | framework |
      | Competency One   | comp1    | compfw    |
      | Competency Two   | comp2    | compfw    |
      | Competency Three | comp2    | compfw    |
    And the following "plans" exist in "totara_plan" plugin:
      | user     | name                      |
      | learner1 | Learner One Learning Plan |
    And I log in as "learner1"
    And I click on "Goals" in the totara menu
    And I press "Add company goal"
    And I click on "Company Goal One" "link" in the "Assign goals" "totaradialogue"
    And I click on "Company Goal Two" "link" in the "Assign goals" "totaradialogue"
    And I click on "Company Goal Three" "link" in the "Assign goals" "totaradialogue"
    And I click on "Save" "button" in the "Assign goals" "totaradialogue"
    And I wait "1" seconds
    And I press "Add personal goal"
    And I set the following fields to these values:
      | Name | Personal Goal One |
    And I press "Save changes"
    And I press "Add personal goal"
    And I set the following fields to these values:
      | Name | Personal Goal Two |
    And I press "Save changes"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "Learner One Learning Plan" "link"
    And I switch to "Competencies" tab
    And I press "Add competencies"
    And I click on "Competency One" "link" in the "Add competencies" "totaradialogue"
    And I click on "Competency Two" "link" in the "Add competencies" "totaradialogue"
    And I click on "Competency Three" "link" in the "Add competencies" "totaradialogue"
    And I click on "Continue" "button" in the "Add competencies" "totaradialogue"
    And I wait "1" seconds
    And I press "Send approval request"
    And I log out
    And I log in as "manager1"
    And I click on "Team" in the totara menu
    And I click on "Plans" "link" in the "Learner One" "table_row"
    And I click on "Approve" "link" in the "Learner One Learning Plan" "table_row"
    And I log out

  @javascript
  Scenario: Complete example appraisal as learner and manager
    Given I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I click on "Activate" "link" in the "Example appraisal" "table_row"
    And I should see "There are no assigned learners."
    And I press "Back to appraisal"
    And I switch to "Assignments" tab
    And I select "Position" from the "groupselector" singleselect
    And I click on "Position One" "link" in the "Assign Learner Group To Appraisal" "totaradialogue"
    And I click on "Save" "button" in the "Assign Learner Group To Appraisal" "totaradialogue"
    And I wait "1" seconds
    And I should see "Learner One" in the "#assignedusers" "css_element"
    And I click on "Activate now" "link"
    And I press "Activate"
    Then I should see "Appraisal Example appraisal activated"
    When I log out
    And I log in as "learner1"
    And I click on "Latest Appraisal" in the totara menu
    Then I should see "Example appraisal"
    And I should see "Learner1 Day Job"
    When I press "Start"
    Then I should see "Review your goals"
    When I press "Choose goals to review"
    And I click on "Company Goal One" "link" in the "Choose goals to review" "totaradialogue"
    And I click on "Company Goal Two" "link" in the "Choose goals to review" "totaradialogue"
    And I click on "Company Goal Three" "link" in the "Choose goals to review" "totaradialogue"
    And I select "Personal Goals" from the "goaltypeselector" singleselect
    And I click on "Personal Goal One" "link" in the "Choose goals to review" "totaradialogue"
    And I click on "Personal Goal Two" "link" in the "Choose goals to review" "totaradialogue"
    And I click on "Save" "button" in the "Choose goals to review" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Company Goal One"
    And I should see "Remove Company Goal One"
    And I should see "Company Goal Two"
    And I should see "Remove Company Goal Two"
    And I should see "Company Goal Three"
    And I should see "Remove Company Goal Three"
    And I should see "Personal Goal One"
    And I should see "Remove Company Goal One"
    And I should see "Personal Goal Two"
    And I should see "Remove Company Goal One"

    # Check that you can remove a goal that's been added.
    When I follow "Remove Company Goal Three"
    Then I should see "Are you sure you want to remove this item?"
    When I press "Yes"
    And I wait "1" seconds
    Then I should not see "Company Goal Three"

    # XPaths below are for finding 1st, 2nd, etc, text areas in the document.
    When I set the field with xpath "/descendant::textarea[1]" to "Learner One goal review one"
    And I set the field with xpath "/descendant::textarea[2]" to "Learner One goal review two"
    And I set the field with xpath "/descendant::textarea[3]" to "Learner One goal review three"
    And I set the field with xpath "/descendant::textarea[4]" to "Learner One goal review four"
    And I press "Next"
    Then I should see "Enter personal development details"
    When I set the following fields to these values:
      | Your answer | Learner One personal development details for set up stage |
    And I press "Next"
    Then I should see "Review competencies"
    When I press "Choose competencies to review"
    And I click on "Competency One" "link" in the "Choose competencies to review" "totaradialogue"
    And I click on "Competency Two" "link" in the "Choose competencies to review" "totaradialogue"
    And I click on "Competency Three" "link" in the "Choose competencies to review" "totaradialogue"
    And I click on "Save" "button" in the "Choose competencies to review" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Competency One (Learner One Learning Plan)"
    And I should see "Remove Competency One (Learner One Learning Plan)"
    And I should see "Competency Two (Learner One Learning Plan)"
    And I should see "Remove Competency Two (Learner One Learning Plan)"
    And I should see "Competency Three (Learner One Learning Plan)"
    And I should see "Remove Competency Three (Learner One Learning Plan)"

    # Check that you can remove a competency that's been added.
    When I follow "Remove Competency Three (Learner One Learning Plan)"
    Then I should see "Are you sure you want to remove this item?"
    When I press "Yes"
    And I wait "1" seconds
    Then I should not see "Competency Three"

    When I set the field with xpath "/descendant::textarea[1]" to "Learner One competency review one"
    And I set the field with xpath "/descendant::textarea[2]" to "Learner One competency review two"
    And I press "Next"
    Then I should see "Has this been agreed between learner and manager?"
    When I set the following fields to these values:
      | Yes | 1 |
    And I click on "Complete stage" "button" in the "#fitem_id_submitbutton" "css_element"
    Then I should see "You have completed this stage"
    When I log out
    And I log in as "manager1"
    And I click on "All Appraisals" in the totara menu
    Then I should see "Active" in the "Learner One" "table_row"
    When I click on "Example appraisal" "link"
    Then I should see "You are viewing Learner One's appraisal."
    When I press "Start"
    Then I should see "Company Goal One"
    And I should see "Company Goal Two"
    And I should not see "Company Goal Company"
    And I should see "Personal Goal One"
    And I should see "Personal Goal Two"
    And I should see "Learner One goal review one"
    And I should see "Learner One goal review two"
    And I should see "Learner One goal review three"
    And I should see "Learner One goal review four"
    When I set the field with xpath "/descendant::textarea[1]" to "Manager One goal review one"
    And I set the field with xpath "/descendant::textarea[2]" to "Manager One goal review two"
    And I set the field with xpath "/descendant::textarea[3]" to "Manager One goal review three"
    And I set the field with xpath "/descendant::textarea[4]" to "Manager One goal review four"
    And I press "Next"
    Then I should see "Learner One personal development details for set up stage"
    When I set the following fields to these values:
      | Your answer | Manager One personal development details for set up stage |
    And I press "Next"
    Then I should see "Competency One (Learner One Learning Plan)"
    And I should see "Competency Two (Learner One Learning Plan)"
    And I should not see "Competency Three (Learner One Learning Plan)"
    And I should see "Learner One competency review one"
    And I should see "Learner One competency review two"
    When I press "Next"
    Then I should see "There were problems with the data you submitted"
    When I set the field with xpath "/descendant::textarea[1]" to "Manager One competency review one"
    And I set the field with xpath "/descendant::textarea[2]" to "Manager One competency review two"
    And I press "Next"
    Then I should see "Has this been agreed between learner and manager?"
    When I set the following fields to these values:
      | Yes | 1 |
    And I click on "Complete stage" "button" in the "#fitem_id_submitbutton" "css_element"
    Then I should see "You have completed this stage"
    When I press "Start"
    And I press "Choose goals to review"
    And I click on "Company Goal One" "link" in the "Choose goals to review" "totaradialogue"
    And I select "Personal Goals" from the "goaltypeselector" singleselect
    And I click on "Personal Goal Two" "link" in the "Choose goals to review" "totaradialogue"
    And I click on "Save" "button" in the "Choose goals to review" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Company Goal One"
    And I should not see "Company Goal Two"
    And I should not see "Personal Goal One"
    And I should see "Personal Goal Two"
    When I set the field with xpath "/descendant::textarea[1]" to "Manager One goal review one (mid-year)"
    And I set the field with xpath "/descendant::textarea[2]" to "Manager One goal review two (mid-year)"
    # Set ratings slider to 10.
    And I click on ".yui3-slider-rail-cap-right" "css_element"
    And I press "Next"
    When I press "Choose competencies to review"
    And I click on "Competency One" "link" in the "Choose competencies to review" "totaradialogue"
    And I click on "Save" "button" in the "Choose competencies to review" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Competency One (Learner One Learning Plan)"
    And I should not see "Competency Two (Learner One Learning Plan)"
    When I set the field with xpath "/descendant::textarea[1]" to "Manager One competency review one (mid-year)"
    And I click on ".yui3-slider-rail-cap-right" "css_element"
    And I press "Next"
    Then I should see "Overall comments"
    When I set the following fields to these values:
      | Your answer | Manager One overall comments (mid-year) |
    And I click on "Complete stage" "button" in the "#fitem_id_submitbutton" "css_element"
    Then I should see "You have completed this stage"
    When I log out
    And I log in as "learner1"
    And I click on "Latest Appraisal" in the totara menu
    And I press "Start"
    Then I should see "Mid-Year Review"
    And I should see "Company Goal One"
    And I should see "Manager One goal review one (mid-year)"
    And I should not see "Company Goal Two"
    And I should not see "Personal Goal One"
    And I should see "Personal Goal Two"
    And I should see "Manager One goal review two (mid-year)"
    When I press "Choose goals to review"
    And I click on "Company Goal Two" "link" in the "Choose goals to review" "totaradialogue"
    And I click on "Save" "button" in the "Choose goals to review" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Company Goal One"
    And I should see "Company Goal Two"
    And I should not see "Personal Goal One"
    And I should see "Personal Goal Two"
    When I set the field with xpath "/descendant::textarea[1]" to "Learner One goal review one (mid-year)"
    And I set the field with xpath "/descendant::textarea[2]" to "Learner One goal review two (mid-year)"
    And I set the field with xpath "/descendant::textarea[3]" to "Learner One goal review three (mid-year)"
    And I click on ".yui3-slider-rail-cap-right" "css_element"
    And I press "Next"
    Then I should see "Competency One (Learner One Learning Plan)"
    And I should see "Manager One competency review one (mid-year)"
    And I should not see "Competency Two (Learner One Learning Plan)"
    When I press "Choose competencies to review"
    And I click on "Competency Two" "link" in the "Choose competencies to review" "totaradialogue"
    And I click on "Save" "button" in the "Choose competencies to review" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Competency One (Learner One Learning Plan)"
    And I should see "Competency Two (Learner One Learning Plan)"
    When I set the field with xpath "/descendant::textarea[1]" to "Learner One competency review one (mid-year)"
    And I set the field with xpath "/descendant::textarea[2]" to "Learner One competency review two (mid-year)"
    And I click on ".yui3-slider-rail-cap-right" "css_element"
    And I press "Next"
    Then I should see "Manager One overall comments (mid-year)"
    When I set the following fields to these values:
      | Your answer | Learner One overall comments (mid-year) |
    And I click on "Complete stage" "button" in the "#fitem_id_submitbutton" "css_element"
    Then I should see "You have completed this stage"
    When I press "Start"
    Then I should see "End of Year Review"
    When I press "Next"
    Then I should see "There were problems with the data you submitted"
    And I should see "At least one item must be reviewed"
    When I press "Choose goals to review"
    And I click on "Company Goal One" "link" in the "Choose goals to review" "totaradialogue"
    And I click on "Save" "button" in the "Choose goals to review" "totaradialogue"
    And I wait "1" seconds
    When I set the field with xpath "/descendant::textarea[1]" to "Learner One goal review one (end-of-year)"
    And I click on ".yui3-slider-rail-cap-right" "css_element"
    And I press "Next"
    When I press "Choose competencies to review"
    And I click on "Competency One" "link" in the "Choose competencies to review" "totaradialogue"
    And I click on "Save" "button" in the "Choose competencies to review" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Competency One (Learner One Learning Plan)"
    When I click on "Remove" "link"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I wait "1" seconds
    Then I should not see "Competency One (Learner One Learning Plan)"
    When I press "Choose competencies to review"
    And I click on "Competency One" "link" in the "Choose competencies to review" "totaradialogue"
    And I click on "Save" "button" in the "Choose competencies to review" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Competency One (Learner One Learning Plan)"
    When I set the field with xpath "/descendant::textarea[1]" to "Learner One competency review one (end-of-year)"
    And I click on ".yui3-slider-rail-cap-right" "css_element"
    And I press "Next"
    And I set the following fields to these values:
      | Your answer | Learner One overall comments (end-of-year) |
    And I click on "Complete stage" "button" in the "#fitem_id_submitbutton" "css_element"
    And I log out
    And I log in as "manager1"
    And I click on "All Appraisals" in the totara menu
    And I click on "Example appraisal" "link"
    And I press "Start"
    Then I should see "Company Goal One"
    And I should not see "Company Goal Two"
    And I should see "Learner One goal review one (end-of-year)"
    When I set the field with xpath "/descendant::textarea[1]" to "Manager One goal review one (end-of-year)"
    And I click on ".yui3-slider-rail-cap-right" "css_element"
    And I press "Next"
    Then I should see "Competency One (Learner One Learning Plan)"
    And I should see "Learner One competency review one (end-of-year)"
    And I should not see "Competency Two (Learner One Learning Plan)"
    When I set the field with xpath "/descendant::textarea[1]" to "Manager One competency review one (end-of-year)"
    And I click on ".yui3-slider-rail-cap-right" "css_element"
    And I press "Next"
    Then I should see "Learner One overall comments (end-of-year)"
    When I set the following fields to these values:
      | Your answer | Manager One overall comments (end-of-year) |
    And I click on "Complete stage" "button" in the "#fitem_id_submitbutton" "css_element"
    Then I should see "You have completed this stage"
    When I click on "All Appraisals" in the totara menu
    Then I should see "Completed" in the "Learner One" "table_row"
    When I click on "Example appraisal" "link"
    Then I should see "This appraisal was completed on"
    When I press "View"
    # There were 3 "View" buttons, but none will go straight to a Summary, so we'll work backwards
    # in case it clicks a different button in different runs of the test.
    And I click on "Summary (End-Year)" "link"
    Then I should see "Learner One overall comments (end-of-year)"
    And I should see "Manager One overall comments (end-of-year)"
    When I click on "Goals (End-Year)" "link"
    Then I should see "Company Goal One"
    And I should see "Learner One goal review one (end-of-year)"
    And I should see "Manager One goal review one (end-of-year)"
    And I should not see "Learner One goal review two (end-of-year)"
    And I should not see "Manager One goal review two (end-of-year)"
    When I click on "Competencies (Mid-Year)" "link"
    Then I should see "Competency One (Learner One Learning Plan)"
    And I should see "Competency Two (Learner One Learning Plan)"
    And I should see "Learner One competency review one (mid-year)"
    And I should see "Learner One competency review two (mid-year)"
    And I should see "Manager One competency review one (mid-year)"
    And I should not see "Manager One competency review two (mid-year)"
    When I click on "Personal Development" "link"
    Then I should see "Learner One personal development details for set up stage"
    And I should see "Manager One personal development details for set up stage"
