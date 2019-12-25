@availability @availability_time_since_completion @javascript
Feature: availability_time_since_completion
  In order to control learner access to activities
  As a trainer
  I need to set completion conditions which prevent learner access based on time since activity completion

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 1 | C1        | topics | 1                |
    And the following "users" exist:
      | username | firstname | lastname |
      | trainer1 | Trainer   | 1        |
      | learner1 | Learner   | 1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | trainer1 | C1     | editingteacher |
      | learner1 | C1     | student        |

    And I log in as "trainer1"
    And I am on "Course 1" course homepage
    And I turn editing mode on

  Scenario: Test time since activity completion condition, must, is marked complete
    # Add an Assignment with completion.
    Given I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name       | Assignment 1                                         |
      | Grade to pass         | 60                                                   |
      | Completion tracking   | Learners can manually mark the activity as completed |
    # Add a Page with restriction based on completion of the Assignment.
    When I add a "Page" to section "2"
    And I set the following fields to these values:
      | Name         | Page 1                    |
      | Page content | Test content for page 1   |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Time since activity completion" "button" in the "Add restriction..." "dialogue"
    And I click on ".availability-item .availability-eye" "css_element"
    And I set the field "Time amount" to "5"
    And I set the field "Time period" to "Weeks"
    And I set the field "Activity or resource" to "Assignment 1"
    And I set the field "Required completion status" to "is marked complete"
    And I press "Save and return to course"
    Then I should see "Page 1"
    And I should see "Not available unless: 5 weeks have elapsed since the activity Assignment 1 is marked complete (hidden otherwise)"

    # Log in as learner1.
    When I log out
    And I log in as "learner1"
    And I am on "Course 1" course homepage

    # Page 1 should not be available yet.
    Then I should not see "Page 1" in the "region-main" "region"

    # Mark page 1 complete
    And I should see "Assignment 1"
    When I click on "Not completed: Assignment 1. Select to mark as complete." "link"

    # Page 1 should still not be available, the time has not elapsed.
    Then I should not see "Page 1" in the "region-main" "region"

    # Rewind completion time so we can test Page 1 is now available.
    When I rewind completion dates for "learner1" in "Course 1" by "6" "weeks"
    And I follow "Course 1"
    Then I should see "Page 1" in the "region-main" "region"

  Scenario: Test time since activity completion condition, must not, is marked complete
    # Add an Assignment with completion.
    Given I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name       | Assignment 1                                         |
      | Grade to pass         | 60                                                   |
      | Completion tracking   | Learners can manually mark the activity as completed |

    # Add a Page with restriction based on completion of the Assignment.
    When I add a "Page" to section "2"
    And I set the following fields to these values:
      | Name         | Page 1                    |
      | Page content | Test content for page 1   |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Time since activity completion" "button" in the "Add restriction..." "dialogue"
    And I set the field "Restriction type" to "must not"
    And I click on ".availability-eye" "css_element"
    And I set the field "Time amount" to "5"
    And I set the field "Time period" to "Weeks"
    And I set the field "Activity or resource" to "Assignment 1"
    And I set the field "Required completion status" to "is marked complete"
    And I press "Save and return to course"
    Then I should see "Page 1"
    And I should not see "Not available unless: 5 weeks have elapsed since the activity Assignment 1 is marked complete"

    # Log in as learner1.
    When I log out
    And I log in as "learner1"
    And I am on "Course 1" course homepage

    # Page 1 should be available.
    Then I should see "Page 1" in the "region-main" "region"
    And I should not see "Not available unless: 5 weeks have elapsed since the activity Assignment 1 is marked complete"

    # Mark page 1 complete
    And I should see "Assignment 1"
    When I click on "Not completed: Assignment 1. Select to mark as complete." "link"

    # Page 1 should still be available, the time has not elapsed.
    Then I should see "Page 1" in the "region-main" "region"
    And I should not see "Not available unless: 5 weeks have elapsed since the activity Assignment 1 is marked complete"

    # Rewind completion time so we can test page 1 is no longer available.
    When I rewind completion dates for "learner1" in "Course 1" by "6" "weeks"
    And I follow "Course 1"
    Then I should not see "Page 1" in the "region-main" "region"

  Scenario: Test time since activity completion condition, must, is complete with pass grade
    # Add an Assignment with completion.
    Given I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name                     | Assignment 1                                         |
      | assignsubmission_onlinetext_enabled | 1                                                    |
      | assignsubmission_file_enabled       | 0                                                    |
      | Grade to pass                       | 60                                                   |
      | Completion tracking                 | Show activity as complete when conditions are met    |
      | id_completionusegrade               | 1                                                    |

    # Add a Page with restriction based on completion of the Assignment.
    When I add a "Page" to section "2"
    And I set the following fields to these values:
      | Name         | Page 1                    |
      | Page content | Test content for page 1   |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Time since activity completion" "button" in the "Add restriction..." "dialogue"
    And I click on ".availability-item .availability-eye" "css_element"
    And I set the field "Time amount" to "5"
    And I set the field "Time period" to "Weeks"
    And I set the field "Activity or resource" to "Assignment 1"
    And I set the field "Required completion status" to "is complete with pass grade"
    And I press "Save and return to course"
    Then I should see "Page 1"
    And I should see "Not available unless: 5 weeks have elapsed since the activity Assignment 1 is complete and passed (hidden otherwise)"

    # Log in as learner1.
    When I log out
    And I log in as "learner1"
    And I am on "Course 1" course homepage

    # Page 1 should not be available yet.
    Then I should not see "Page 1" in the "region-main" "region"

    # Make a submisson for the Assignment.
    And I follow "Assignment 1"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the learners submission |
    And I press "Save changes"
    Then I should see "Submitted"

    # Log in as Trainer 1 to give a pass grade.
    When I log out
    And I log in as "trainer1"
    And I am on "Course 1" course homepage
    And I follow "Assignment 1"
    And I navigate to "View all submissions" in current page administration
    And I click on "Grade" "link" in the "Learner 1" "table_row"
    And I set the field "Grade out of 100" to "65"
    And I set the field "Notify learners" to "0"
    And I press "Save changes"
    And I press "Ok"
    And I click on "Edit settings" "link"
    And I follow "Assignment 1"
    And I navigate to "View all submissions" in current page administration
    Then "Learner 1" row "Grade" column of "generaltable" table should contain "65.00"

    # Log in as learner1.
    When I log out
    And I log in as "learner1"
    And I am on "Course 1" course homepage

    # Page 1 should not be available yet.
    Then I should not see "Page 1" in the "region-main" "region"

    # Rewind completion time so we can test Page 1 is now available.
    When I rewind completion dates for "learner1" in "Course 1" by "6" "weeks"
    And I follow "Course 1"
    Then I should see "Page 1" in the "region-main" "region"

  Scenario: Test time since activity completion condition, must not, is complete with pass grade
    # Add an Assignment with completion.
    Given I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name                     | Assignment 1                                         |
      | assignsubmission_onlinetext_enabled | 1                                                    |
      | assignsubmission_file_enabled       | 0                                                    |
      | Grade to pass                       | 60                                                   |
      | Completion tracking                 | Show activity as complete when conditions are met    |
      | id_completionusegrade               | 1                                                    |

    # Add a Page with restriction based on completion of the Assignment.
    When I add a "Page" to section "2"
    And I set the following fields to these values:
      | Name         | Page 1                    |
      | Page content | Test content for page 1   |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Time since activity completion" "button" in the "Add restriction..." "dialogue"
    And I set the field "Restriction type" to "must not"
    And I click on ".availability-eye" "css_element"
    And I set the field "Time amount" to "5"
    And I set the field "Time period" to "Weeks"
    And I set the field "Activity or resource" to "Assignment 1"
    And I set the field "Required completion status" to "is complete with pass grade"
    And I press "Save and return to course"
    Then I should see "Page 1"

    # Log in as learner1.
    When I log out
    And I log in as "learner1"
    And I am on "Course 1" course homepage

    # Page 1 should not be available yet.
    Then I should see "Page 1" in the "region-main" "region"

    # Make a submisson for the Assignment.
    And I follow "Assignment 1"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the learners submission |
    And I press "Save changes"
    Then I should see "Submitted"

    # Log in as Trainer 1 to give a pass grade.
    When I log out
    And I log in as "trainer1"
    And I am on "Course 1" course homepage
    And I follow "Assignment 1"
    And I navigate to "View all submissions" in current page administration
    And I click on "Grade" "link" in the "Learner 1" "table_row"
    And I set the field "Grade out of 100" to "65"
    And I set the field "Notify learners" to "0"
    And I press "Save changes"
    And I press "Ok"
    And I click on "Edit settings" "link"
    And I follow "Assignment 1"
    And I navigate to "View all submissions" in current page administration
    Then "Learner 1" row "Grade" column of "generaltable" table should contain "65.00"

    # Log in as learner1.
    When I log out
    And I log in as "learner1"
    And I am on "Course 1" course homepage

    # Page 1 should still be available.
    Then I should see "Page 1" in the "region-main" "region"

    # Rewind completion time so we can test Page 1 is now available.
    When I rewind completion dates for "learner1" in "Course 1" by "6" "weeks"
    And I follow "Course 1"
    Then I should not see "Page 1" in the "region-main" "region"

  Scenario: Test time since activity completion condition, must, is complete with fail grade
    # Add an Assignment with completion.
    Given I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name                     | Assignment 1                                         |
      | assignsubmission_onlinetext_enabled | 1                                                    |
      | assignsubmission_file_enabled       | 0                                                    |
      | Grade to pass                       | 60                                                   |
      | Completion tracking                 | Show activity as complete when conditions are met    |
      | id_completionusegrade               | 1                                                    |

    # Add a Page with restriction based on completion of the Assignment.
    When I add a "Page" to section "2"
    And I set the following fields to these values:
      | Name         | Page 1                    |
      | Page content | Test content for page 1   |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Time since activity completion" "button" in the "Add restriction..." "dialogue"
    And I click on ".availability-item .availability-eye" "css_element"
    And I set the field "Time amount" to "5"
    And I set the field "Time period" to "Weeks"
    And I set the field "Activity or resource" to "Assignment 1"
    And I set the field "Required completion status" to "is complete with fail grade"
    And I press "Save and return to course"
    Then I should see "Page 1"
    And I should see "Not available unless: 5 weeks have elapsed since the activity Assignment 1 is complete and failed (hidden otherwise)"

    # Log in as learner1.
    When I log out
    And I log in as "learner1"
    And I am on "Course 1" course homepage

    # Page 1 should not be available yet.
    Then I should not see "Page 1" in the "region-main" "region"

    # Make a submisson for the Assignment.
    And I follow "Assignment 1"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the learners submission |
    And I press "Save changes"
    Then I should see "Submitted"

    # Log in as Trainer 1 to give a pass grade.
    When I log out
    And I log in as "trainer1"
    And I am on "Course 1" course homepage
    And I follow "Assignment 1"
    And I navigate to "View all submissions" in current page administration
    And I click on "Grade" "link" in the "Learner 1" "table_row"
    And I set the field "Grade out of 100" to "35"
    And I set the field "Notify learners" to "0"
    And I press "Save changes"
    And I press "Ok"
    And I click on "Edit settings" "link"
    And I follow "Assignment 1"
    And I navigate to "View all submissions" in current page administration
    Then "Learner 1" row "Grade" column of "generaltable" table should contain "35.00"

    # Log in as learner1.
    When I log out
    And I log in as "learner1"
    And I am on "Course 1" course homepage

    # Page 1 should not be available yet.
    Then I should not see "Page 1" in the "region-main" "region"

    # Rewind completion time so we can test Page 1 is now available.
    When I rewind completion dates for "learner1" in "Course 1" by "6" "weeks"
    And I follow "Course 1"
    Then I should see "Page 1" in the "region-main" "region"

  Scenario: Test time since activity completion condition, must not, is complete with fail grade
    # Add an Assignment with completion.
    Given I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name                     | Assignment 1                                         |
      | assignsubmission_onlinetext_enabled | 1                                                    |
      | assignsubmission_file_enabled       | 0                                                    |
      | Grade to pass                       | 60                                                   |
      | Completion tracking                 | Show activity as complete when conditions are met    |
      | id_completionusegrade               | 1                                                    |

    # Add a Page with restriction based on completion of the Assignment.
    When I add a "Page" to section "2"
    And I set the following fields to these values:
      | Name         | Page 1                    |
      | Page content | Test content for page 1   |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Time since activity completion" "button" in the "Add restriction..." "dialogue"
    And I set the field "Restriction type" to "must not"
    And I click on ".availability-eye" "css_element"
    And I set the field "Time amount" to "5"
    And I set the field "Time period" to "Weeks"
    And I set the field "Activity or resource" to "Assignment 1"
    And I set the field "Required completion status" to "is complete with fail grade"
    And I press "Save and return to course"
    Then I should see "Page 1"

    # Log in as learner1.
    When I log out
    And I log in as "learner1"
    And I am on "Course 1" course homepage

    # Page 1 should not be available yet.
    Then I should see "Page 1" in the "region-main" "region"

    # Make a submisson for the Assignment.
    And I follow "Assignment 1"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the learners submission |
    And I press "Save changes"
    Then I should see "Submitted"

    # Log in as Trainer 1 to give a pass grade.
    When I log out
    And I log in as "trainer1"
    And I am on "Course 1" course homepage
    And I follow "Assignment 1"
    And I navigate to "View all submissions" in current page administration
    And I click on "Grade" "link" in the "Learner 1" "table_row"
    And I set the field "Grade out of 100" to "35"
    And I set the field "Notify learners" to "0"
    And I press "Save changes"
    And I press "Ok"
    And I click on "Edit settings" "link"
    And I follow "Assignment 1"
    And I navigate to "View all submissions" in current page administration
    Then "Learner 1" row "Grade" column of "generaltable" table should contain "35.00"

    # Log in as learner1.
    When I log out
    And I log in as "learner1"
    And I am on "Course 1" course homepage

    # Page 1 should still be available.
    Then I should see "Page 1" in the "region-main" "region"

    # Rewind completion time so we can test Page 1 is now available.
    When I rewind completion dates for "learner1" in "Course 1" by "6" "weeks"
    And I follow "Course 1"
    Then I should not see "Page 1" in the "region-main" "region"
