@totara @totara_completioneditor @javascript
Feature: Criteria completion records can be edited
  In order to see that criteria completion records can be edited
  I need to use the course completion editor

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And the following "users" exist:
      | username | firstname  | lastname  | email               |
      | user001  | FirstName1 | LastName1 | user001@example.com |
    And the following "courses" exist:
      | fullname   | shortname | format | enablecompletion |
      | Course One | course1   | topics | 1                |
    And the following "course enrolments" exist:
      | user    | course  | role    |
      | user001 | course1 | student |

  Scenario: Activity-based criteria completion records based on timemodified without grades (Feedback) can be edited
    # Set up the activity and criteria and navigate to the completion editor.
    When I am on "Course One" course homepage with editing mode on
    And I add a "Feedback" to section "1" and I fill the form with:
      | Name                | Test feedback 1           |
      | Description         | Test feedback description |
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Feedback - Test feedback 1" to "1"
    And I press "Save changes"
    And I navigate to "Completion editor" node in "Course administration"
    Then I should see "FirstName1 LastName1"

    # Completion editor criteria and activities tab/list.
    When I click on "Edit course completion" "link" in the "FirstName1 LastName1" "table_row"
    And I switch to "Criteria and Activities" tab
    Then I should see "Course completion criteria"
    And I should see "All criteria below are required"
    And I should see "Activity completion"
    And I should see "Test feedback 1"
    And I should see "Not completed"
    And I should see "Activity completion"
    And I should not see "The course has no activities"

    # Default data was created and is loaded correctly.
    When I follow "Edit"
    Then the field "Editing mode" matches value "Use activity completion"
    And "Criteria status" "field" should not exist
    And I should see "Complete if activity is complete"
    And "Criteria time completed" "field" should not exist
    And I should see "Copied from activity time completed"
    And the field "Activity status" matches value "Not completed"
    And "Activity time completed" "field" should not exist
    And the field "Viewed" matches value "0"
    And "RPL" "field" should not exist

    # Create course_modules_completion and course_completion_crit_compl records.
    When I set the field "Viewed" to "1"
    And I click on "Completed" "option" in the "#tfiid_completionstate_totara_completioneditor_form_course_completion" "css_element"
    And I set the "Activity time completed" Totara form field to "2011-02-03 04:56"
    And I set the field "RPL" to "This is an RPL reason"
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    And I should see "Complete via rpl"
    When I switch to "Transactions" tab
    Then I should see "February 2011"
    And I should see "Crit compl manually created"
    And I should see "Grade final: Empty (null)"
    And I should see "Unenroled: Empty (null)"
    And I should see "RPL: This is an RPL reason"
    And I should see "Module completion manually created"
    And I should see "Completion state: Complete (1)"
    And I should see "Viewed: Yes (1)"
    And I should see "Time completed: Not set (null)"
    When I switch to "Criteria and Activities" tab
    And I follow "Edit"
    Then the field "Editing mode" matches value "Use activity completion"
    And "Criteria status" "field" should not exist
    And "Criteria time completed" "field" should not exist
    And the field "Activity status" matches value "Completed"
    And the field "Activity time completed" matches value "2011-02-03T04:56:00"
    And the field "Viewed" matches value "1"
    And the field "RPL" matches value "This is an RPL reason"

    # Update course_modules_completion and course_completion_crit_compl records.
    When I set the field "Viewed" to "0"
    And I click on "Not completed" "option" in the "#tfiid_completionstate_totara_completioneditor_form_course_completion" "css_element"
    And "Activity time completed" "field" should not exist
    And "RPL" "field" should not exist
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    When I switch to "Transactions" tab
    Then I should see "Not complete"
    And I should see "Crit compl manually updated"
    And I should see "RPL: Empty (null)"
    And I should see "Module completion manually updated"
    And I should see "Completion state: Not complete (0)"
    And I should see "Viewed: No (0)"
    When I switch to "Criteria and Activities" tab
    And I follow "Edit"
    Then the field "Editing mode" matches value "Use activity completion"
    And "Criteria status" "field" should not exist
    And "Criteria time completed" "field" should not exist
    And the field "Activity status" matches value "Not completed"
    And "Activity time completed" "field" should not exist
    And the field "Viewed" matches value "0"
    And "RPL" "field" should not exist

    # Save and edit separate completion dates - both set.
    When I set the field "Viewed" to "1"
    And I click on "Use separate completion data" "option" in the "#tfiid_editingmode_totara_completioneditor_form_course_completion" "css_element"
    Then "RPL" "field" should not exist
    When I click on "Completed" "option" in the "#tfiid_criteriastatus_totara_completioneditor_form_course_completion" "css_element"
    And I set the "Criteria time completed" Totara form field to "2011-02-03 04:56"
    And I set the field "RPL" to "This is another RPL reason"
    And I click on "Completed" "option" in the "#tfiid_completionstate_totara_completioneditor_form_course_completion" "css_element"
    # The line below highlights a bug in Totara forms / behat interaction.
    And I set the "Activity time completed" Totara form field to "2027-07-08 16:34"
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    When I follow "Edit"
    Then the field "Editing mode" matches value "Use separate completion data"
    And the field "Criteria status" matches value "Completed"
    And the field "Criteria time completed" matches value "2011-02-03T04:56:00"
    And the field "Activity status" matches value "Completed"
    And the field "Activity time completed" matches value "2027-07-08T16:34:00"
    And the field "Viewed" matches value "1"
    And the field "RPL" matches value "This is another RPL reason"

    # Save and edit separate completion dates - only criteria set.
    When I click on "Not completed" "option" in the "#tfiid_completionstate_totara_completioneditor_form_course_completion" "css_element"
    And I set the field "Viewed" to "0"
    And I set the field "RPL" to "This is yet another RPL reason"
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    When I follow "Edit"
    Then the field "Editing mode" matches value "Use separate completion data"
    And the field "Criteria status" matches value "Completed"
    And the field "Criteria time completed" matches value "2011-02-03T04:56:00"
    And the field "Activity status" matches value "Not completed"
    And "Activity time completed" "field" should not exist
    And the field "Viewed" matches value "0"
    And the field "RPL" matches value "This is yet another RPL reason"

    # Save and edit separate completion dates - only activity set.
    When I click on "Completed" "option" in the "#tfiid_completionstate_totara_completioneditor_form_course_completion" "css_element"
    And I click on "Not completed" "option" in the "#tfiid_criteriastatus_totara_completioneditor_form_course_completion" "css_element"
    Then "RPL" "field" should not exist
    When I set the field "Viewed" to "1"
    And I set the "Activity time completed" Totara form field to "2011-11-11 11:11"
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    When I follow "Edit"
    Then the field "Editing mode" matches value "Use separate completion data"
    And the field "Criteria status" matches value "Not completed"
    And "Criteria time completed" "field" should not exist
    And the field "Activity status" matches value "Completed"
    And the field "Activity time completed" matches value "2011-11-11T11:11:00"
    And the field "Viewed" matches value "1"
    And "RPL" "field" should not exist

    # Save and edit separate completion dates - both set to the same date - results in editor switching to 'Use activity completion'.
    When I click on "Completed" "option" in the "#tfiid_criteriastatus_totara_completioneditor_form_course_completion" "css_element"
    And I set the field "Viewed" to "1"
    And I set the "Criteria time completed" Totara form field to "2011-11-11 11:11"
    And I set the field "RPL" to "This is the last RPL reason"
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    When I follow "Edit"
    Then the field "Editing mode" matches value "Use activity completion"
    And "Criteria status" "field" should not exist
    And "Criteria time completed" "field" should not exist
    And the field "Activity status" matches value "Completed"
    And the field "Activity time completed" matches value "2011-11-11T11:11:00"
    And the field "Viewed" matches value "1"
    And the field "RPL" matches value "This is the last RPL reason"

  Scenario: Activity-based criteria completion records based on timemodified and grades (Quiz) can be edited
    # Set up the activity and criteria and navigate to the completion editor.
    When I am on "Course One" course homepage with editing mode on
    And I add a "Quiz" to section "1" and I fill the form with:
      | Name                | Test quiz 1                                       |
      | Description         | Test quiz description                             |
      | Completion tracking | Show activity as complete when conditions are met |
      | completionusegrade  | 1                                                 |
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Quiz - Test quiz 1" to "1"
    And I press "Save changes"
    And I navigate to "Completion editor" node in "Course administration"
    Then I should see "FirstName1 LastName1"

    # Completion editor criteria and activities tab/list.
    When I click on "Edit course completion" "link" in the "FirstName1 LastName1" "table_row"
    And I switch to "Criteria and Activities" tab
    Then I should see "Course completion criteria"
    And I should see "All criteria below are required"
    And I should see "Activity completion"
    And I should see "Test quiz 1"
    And I should see "Not completed"
    And I should see "Activity completion"
    And I should not see "The course has no activities"

    # Default data was created and is loaded correctly.
    When I follow "Edit"
    Then the field "Editing mode" matches value "Use activity completion"
    And "Criteria status" "field" should not exist
    And I should see "Complete if activity is complete"
    And "Criteria time completed" "field" should not exist
    And I should see "Copied from activity time completed"
    And the field "Activity status" matches value "Not completed"
    And "Activity time completed" "field" should not exist
    And the field "Viewed" matches value "0"
    And "RPL" "field" should not exist

    # Create course_modules_completion and course_completion_crit_compl records.
    When I set the field "Viewed" to "1"
    And I click on "Completed" "option" in the "#tfiid_completionstate_totara_completioneditor_form_course_completion" "css_element"
    And I set the "Activity time completed" Totara form field to "2011-02-03 04:56"
    And I set the field "RPL" to "This is an RPL reason"
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    And I should see "Complete via rpl"
    When I switch to "Transactions" tab
    Then I should see "February 2011"
    And I should see "Crit compl manually created"
    And I should see "Grade final: Empty (null)"
    And I should see "Unenroled: Empty (null)"
    And I should see "RPL: This is an RPL reason"
    And I should see "Module completion manually created"
    And I should see "Completion state: Complete (1)"
    And I should see "Viewed: Yes (1)"
    And I should see "Time completed: Not set (null)"
    When I switch to "Criteria and Activities" tab
    And I follow "Edit"
    Then the field "Editing mode" matches value "Use activity completion"
    And "Criteria status" "field" should not exist
    And "Criteria time completed" "field" should not exist
    And the field "Activity status" matches value "Completed"
    And the field "Activity time completed" matches value "2011-02-03T04:56:00"
    And the field "Viewed" matches value "1"
    And the field "RPL" matches value "This is an RPL reason"

    # Update course_modules_completion and course_completion_crit_compl records.
    When I set the field "Viewed" to "0"
    And I click on "Not completed" "option" in the "#tfiid_completionstate_totara_completioneditor_form_course_completion" "css_element"
    And "Activity time completed" "field" should not exist
    And "RPL" "field" should not exist
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    And I should see "Not completed"
    When I switch to "Transactions" tab
    Then I should see "Crit compl manually updated"
    And I should see "RPL: Empty (null)"
    And I should see "Module completion manually updated"
    And I should see "Completion state: Not complete (0)"
    And I should see "Viewed: No (0)"
    When I switch to "Criteria and Activities" tab
    And I follow "Edit"
    Then the field "Editing mode" matches value "Use activity completion"
    And "Criteria status" "field" should not exist
    And "Criteria time completed" "field" should not exist
    And the field "Activity status" matches value "Not completed"
    And "Activity time completed" "field" should not exist
    And the field "Viewed" matches value "0"
    And "RPL" "field" should not exist

    # Save and edit separate completion dates - both set.
    When I set the field "Viewed" to "1"
    And I click on "Use separate completion data" "option" in the "#tfiid_editingmode_totara_completioneditor_form_course_completion" "css_element"
    Then "RPL" "field" should not exist
    When I click on "Completed" "option" in the "#tfiid_criteriastatus_totara_completioneditor_form_course_completion" "css_element"
    And I set the "Criteria time completed" Totara form field to "2011-02-03 04:56"
    And I set the field "RPL" to "This is another RPL reason"
    And I click on "Completed" "option" in the "#tfiid_completionstate_totara_completioneditor_form_course_completion" "css_element"
    And I set the "Activity time completed" Totara form field to "2027-07-08 16:34"
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    When I follow "Edit"
    Then the field "Editing mode" matches value "Use separate completion data"
    And the field "Criteria status" matches value "Completed"
    And the field "Criteria time completed" matches value "2011-02-03T04:56:00"
    And the field "Activity status" matches value "Completed"
    And the field "Activity time completed" matches value "2027-07-08T16:34:00"
    And the field "Viewed" matches value "1"
    And the field "RPL" matches value "This is another RPL reason"

    # Save and edit separate completion dates - only criteria set.
    When I click on "Not completed" "option" in the "#tfiid_completionstate_totara_completioneditor_form_course_completion" "css_element"
    And I set the field "Viewed" to "0"
    And I set the field "RPL" to "This is yet another RPL reason"
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    When I follow "Edit"
    Then the field "Editing mode" matches value "Use separate completion data"
    And the field "Criteria status" matches value "Completed"
    And the field "Criteria time completed" matches value "2011-02-03T04:56:00"
    And the field "Activity status" matches value "Not completed"
    And "Activity time completed" "field" should not exist
    And the field "Viewed" matches value "0"
    And the field "RPL" matches value "This is yet another RPL reason"

    # Save and edit separate completion dates - only activity set.
    When I click on "Completed (achieved pass grade)" "option" in the "#tfiid_completionstate_totara_completioneditor_form_course_completion" "css_element"
    When I click on "Not completed" "option" in the "#tfiid_criteriastatus_totara_completioneditor_form_course_completion" "css_element"
    Then "RPL" "field" should not exist
    When I set the field "Viewed" to "1"
    And I set the "Activity time completed" Totara form field to "2011-11-11 11:11"
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    When I follow "Edit"
    Then the field "Editing mode" matches value "Use separate completion data"
    And the field "Criteria status" matches value "Not completed"
    And "Criteria time completed" "field" should not exist
    And the field "Activity status" matches value "Completed (achieved pass grade)"
    And the field "Activity time completed" matches value "2011-11-11T11:11:00"
    And the field "Viewed" matches value "1"
    And "RPL" "field" should not exist

    # Save and edit separate completion dates - both set to the same date - results in editor switching to 'Use activity completion'.
    When I click on "Completed" "option" in the "#tfiid_criteriastatus_totara_completioneditor_form_course_completion" "css_element"
    And I set the field "Viewed" to "1"
    And I set the "Criteria time completed" Totara form field to "2011-11-11 11:11"
    And I set the field "RPL" to "This is the last RPL reason"
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    When I follow "Edit"
    Then the field "Editing mode" matches value "Use activity completion"
    And "Criteria status" "field" should not exist
    And "Criteria time completed" "field" should not exist
    And the field "Activity status" matches value "Completed (achieved pass grade)"
    And the field "Activity time completed" matches value "2011-11-11T11:11:00"
    And the field "Viewed" matches value "1"
    And the field "RPL" matches value "This is the last RPL reason"

  Scenario: Non-activity criteria completion records can be edited
    # Set up the criteria and navigate to the completion editor.
    When I am on "Course One" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "id_criteria_self_value" to "1"
    And I press "Save changes"
    And I navigate to "Completion editor" node in "Course administration"
    Then I should see "FirstName1 LastName1"

    # Completion editor criteria and activities tab/list.
    When I click on "Edit course completion" "link" in the "FirstName1 LastName1" "table_row"
    And I switch to "Criteria and Activities" tab
    Then I should see "Course completion criteria"
    And I should see "All criteria below are required"
    And I should see "Self completion"
    And I should see "Not completed"
    And I should see "Activity completion"
    And I should see "The course has no activities"

    # Default data was created and is loaded correctly.
    When I follow "Edit"
    Then the field "Criteria status" matches value "Not completed"
    And "Criteria time completed" "field" should not exist
    And "RPL" "field" should not exist

    # Create course_completion_crit_compl record.
    When I click on "Completed" "option" in the "#tfiid_criteriastatus_totara_completioneditor_form_course_completion" "css_element"
    Then "RPL" "field" should not exist
    When I set the "Criteria time completed" Totara form field to "2011-02-03 04:56"
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    When I switch to "Transactions" tab
    And I should see "February 2011"
    And I should see "Crit compl manually created"
    And I should see "Grade final: Empty (null)"
    And I should see "Unenroled: Empty (null)"
    And I should see "RPL: Empty (null)"
    And I should not see "Module completion manually"
    When I switch to "Criteria and Activities" tab
    And I follow "Edit"
    Then the field "Criteria status" matches value "Completed"
    And the field "Criteria time completed" matches value "2011-02-03T04:56:00"
    And "RPL" "field" should not exist

    # Update course_modules_completion record.
    When I click on "Not completed" "option" in the "#tfiid_criteriastatus_totara_completioneditor_form_course_completion" "css_element"
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    And I should see "Not completed"
    When I switch to "Transactions" tab
    And I should see "Time completed: Not set (null)"
    And I should see "Crit compl manually updated"
    When I switch to "Criteria and Activities" tab
    And I follow "Edit"
    Then the field "Criteria status" matches value "Not completed"
    And "Criteria time completed" "field" should not exist
    And "RPL" "field" should not exist
