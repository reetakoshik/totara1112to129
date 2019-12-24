@totara @totara_question @totara_feedback360 @javascript
Feature: Admin interface of multiple answer multichoice question
  In order to create a multichoice question for feedback360 and appraisals
  I need to ensure correct behaviour of the admin interface

Background:
  Given I am on a totara site
  And I log in as "admin"
  And I navigate to "Manage Feedback" node in "Site administration > Appraisals"
  And I click on "Create Feedback" "button"
  And I set the field "Name" to "test feedback"
  And I click on "Create Feedback" "button"
  And I switch to "Content" tab
  And I set the field "datatype" to "Multiple choice (several answers)"
  And I click on "Add" "button"
  And I set the field "Question" to "Test question 1"
  And I set the field "choice[0][option]" to "one"
  And I set the field "choice[1][option]" to "two"
  And I set the field "choice[2][option]" to "three"
  When I click on "Make selected by default" "link" in the "#fgroup_id_choice_1" "css_element"
  Then I should see "unselect" in the "#fgroup_id_choice_1" "css_element"

Scenario: totara_question 111: Make default link
  Given I should see "unselect" in the "#fgroup_id_choice_1" "css_element"
  When I click on "Make selected by default" "link" in the "#fgroup_id_choice_2" "css_element"
  Then I should see "unselect" in the "#fgroup_id_choice_2" "css_element"
  And I should not see "Make selected by default" in the "#fgroup_id_choice_2" "css_element"
  And I should see "unselect" in the "#fgroup_id_choice_1" "css_element"

Scenario: totara_question 112: Unselect link
  When I click on "unselect" "link" in the "#fgroup_id_choice_1" "css_element"
  Then I should not see "unselect" in the "#id_availablechoices" "css_element"
  And I should see "Make selected by default" in the "#fgroup_id_choice_1" "css_element"

Scenario: totara_question 113: Add more link
  Given I click on "Add another option" "link"
  And I set the field "choice[3][option]" to "three"

  When I click on "Make selected by default" "link" in the "#fgroup_id_choice_3" "css_element"
  Then I should see "unselect" in the "#fgroup_id_choice_3" "css_element"
  And I should not see "Make selected by default" in the "#fgroup_id_choice_3" "css_element"
  And I should not see "Make selected by default" in the "#fgroup_id_choice_1" "css_element"
  And I should see "unselect" in the "#fgroup_id_choice_1" "css_element"
  And I should see "Make selected by default" in the "#fgroup_id_choice_0" "css_element"
  And I should not see "unselect" in the "#fgroup_id_choice_0" "css_element"

Scenario: totara_question 114: Javascript works after reload
  Given I click on "Save changes" "button"
  # Accessibility for this link is a bit naf
  And I click on "Settings" "link" in the ".feedback360-quest-actions" "css_element"
  Then I should see "Selected by default" in the "#fgroup_id_choice_1" "css_element"
  And I should see "unselect" in the "#fgroup_id_choice_1" "css_element"
  And I should not see "Selected by default" in the "#fgroup_id_choice_2" "css_element"
  When I click on "Make selected by default" "link" in the "#fgroup_id_choice_2" "css_element"
  Then I should see "unselect" in the "#fgroup_id_choice_2" "css_element"
  And I should see "unselect" in the "#fgroup_id_choice_1" "css_element"

  When I click on "unselect" "link" in the "#fgroup_id_choice_2" "css_element"
  Then I should see "unselect" in the "#fgroup_id_choice_1" "css_element"
