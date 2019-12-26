@totara @block @block_last_course_accessed @totara_courseprogressbar @javascript
Feature: Verify the LCA block content displays the correct information.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Bob1      | Learner1 | learner1@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
      | Course 2 | C2        | 1                |
      | Course 3 | C3        | 1                |
    And the following "course enrolments" exist:
      | user      | course    | role     |
      | learner1  | C1        | student  |
      | learner1  | C2        | student  |
      | learner1  | C3        | student  |

  Scenario: Verify a Site Administrator sees the correct information but does not see progress bar when not enrolled on the course.
    Given I log in as "admin"

    When I click on "Dashboard" in the totara menu
    And I should not see "Last Course Accessed"

    # Visit the course
    When I am on "Course 1" course homepage
    Then I should see "C1" in the ".breadcrumb-nav" "css_element"

    # Return to My Learning to check the content of the block.
    When I click on "Dashboard" in the totara menu
    Then I should see "Course 1" in the "Last Course Accessed" "block"
    # The admin should not see a progress bar as they're not enrolled on the course.
    And I should see "Not tracked" in the "Last Course Accessed" "block"

    When I am on "Course 2" course homepage
    Then I should see "C2" in the ".breadcrumb-nav" "css_element"

    # Return to My Learning to check the content of the block.
    When I click on "Dashboard" in the totara menu
    Then I should see "Course 2" in the "Last Course Accessed" "block"
    # The admin should not see a progress bar as they're not enrolled on the course.
    And I should see "Not tracked" in the "Last Course Accessed" "block"

  Scenario: Verify a learner sees the correct information and sees the progress bar when enrolled on a course.

    Given I log in as "admin"

    # Create an activity on course 2 so we use RPL completion.
    And I am on "Course 2" course homepage with editing mode on
    And I add a "Page" to section "1" and I fill the form with:
      | Name                | Test Page                               |
      | Description         | -                                       |
      | Page content        | Believe it or not, this is a test page! |
      | Completion tracking | 2                                       |
      | Require view        | 1                                       |

    # Set course completion on course 2.
    Then I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I click on "criteria_activity_value[1]" "checkbox"
    And I press "Save changes"
    And I log out

    Given I log in as "learner1"

    When I click on "Dashboard" in the totara menu
    And I should not see "Last Course Accessed"

    # Visit the course.
    When I am on "Course 1" course homepage
    Then I should see "C1" in the ".breadcrumb-nav" "css_element"

    # Return to My Learning to check the content of the block.
    When I click on "Dashboard" in the totara menu
    Then I should see "Course 1" in the "Last Course Accessed" "block"
    And ".progressbar_container" "css_element" should exist in the "Last Course Accessed" "block"
    And I should see "No criteria" in the "Last Course Accessed" "block"

    When I am on "Course 2" course homepage
    Then I should see "C2" in the ".breadcrumb-nav" "css_element"

    # Return to My Learning to check the content of the block.
    When I click on "Dashboard" in the totara menu
    Then I should see "Course 2" in the "Last Course Accessed" "block"
    And ".progressbar_container" "css_element" should exist in the "Last Course Accessed" "block"
    And I should see "0%" in the "Last Course Accessed" "block"

    When I am on "Course 2" course homepage
    Then I should see "C2" in the ".breadcrumb-nav" "css_element"
    And I follow "Test Page"
    When I click on "Dashboard" in the totara menu
    Then I should see "Course 2" in the "Last Course Accessed" "block"
    And ".progressbar_container" "css_element" should exist in the "Last Course Accessed" "block"
    And I should see "100%" in the "Last Course Accessed" "block"

  Scenario: Verify a learner sees the correct information when an activity is accessed directly by-passing the main course page.

    Given I log in as "admin"
    # Create activities on course 2.
    And I am on "Course 2" course homepage with editing mode on
    And I add a "Page" to section "1" and I fill the form with:
      | Name                | Test Page                               |
      | Description         | -                                       |
      | Page content        | Believe it or not, this is a test page! |
      | Completion tracking | 2                                       |
      | Require view        | 1                                       |

    And I add a "Page" to section "1" and I fill the form with:
      | Name                | Second Page                             |
      | Description         | -                                       |
      | Page content        | And yet another test page!              |
      | Completion tracking | 2                                       |
      | Require view        | 1                                       |

    And I add a "Page" to section "1" and I fill the form with:
      | Name                | Last Page                               |
      | Description         | -                                       |
      | Page content        | Last test page!                         |
      | Completion tracking | 2                                       |
      | Require view        | 1                                       |

    # Set course completion on course 2.
    Then I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I click on "criteria_activity_value[1]" "checkbox"
    And I click on "criteria_activity_value[2]" "checkbox"
    And I click on "criteria_activity_value[3]" "checkbox"
    And I press "Save changes"

    # Visit the course.
    When I am on "Course 1" course homepage
    Then I should see "C1" in the ".breadcrumb-nav" "css_element"

    # Add a forum post.
    When I add a "Forum" to section "1" and I fill the form with:
      | Forum name  | My Forum |
      | Forum type  | Standard forum for general use |
      | Description | Description of My Forum |
    And I follow "My Forum"
    And I press "Add a new discussion topic"
    And I set the field "Subject" to "My Discussion"
    And I set the field "Message" to "<p><a href='../../mod/page/view.php?id=1'>Link to Course 2 activity</a>.</p>"
    And I press "Post to forum"
    Then I should see "Your post was successfully added."
    And I log out

    Given I log in as "learner1"

    When I click on "Dashboard" in the totara menu
    And I should not see "Last Course Accessed"

    # Visit the course.
    When I follow "Course 1"
    Then I should see "C1" in the ".breadcrumb-nav" "css_element"

    # We're still on Course one, but click the forum link to a page in Course 2.
    When I follow "My Forum"
    And I follow "My Discussion"
    And I follow "Link to Course 2 activity"

    When I click on "Dashboard" in the totara menu
    Then I should see "Course 2" in the "Last Course Accessed" "block"
    And ".progressbar_container" "css_element" should exist in the "Last Course Accessed" "block"
    And I should see "33%" in the "Last Course Accessed" "block"

  Scenario: Verify a learner sees the correct information in My Learning when having not visited a course during a login session.

    Given I log in as "learner1"
    When I click on "Dashboard" in the totara menu
    And I should not see "Last Course Accessed"

    # Visit the courses.
    When I follow "Course 2"
    Then I should see "C2" in the ".breadcrumb-nav" "css_element"

    When I click on "Dashboard" in the totara menu
    And I follow "Course 1"
    Then I should see "C1" in the ".breadcrumb-nav" "css_element"

    When I click on "Dashboard" in the totara menu
    And I follow "Course 3"
    Then I should see "C3" in the ".breadcrumb-nav" "css_element"
    And I log out

    # Login and check the last course accessed is Course 3.
    When I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I should see "Course 3" in the "Last Course Accessed" "block"
    And I log out

    # Login as admin so we can unenrol the learner.
    When I log in as "admin"
    And I am on "Course 3" course homepage
    Then I should see "C3" in the ".breadcrumb-nav" "css_element"

    # Unenrol the learner from Course 3.
    When I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Unenrol" "link" in the "Bob1 Learner1" "table_row"
    And I press "Continue"
    Then I should not see "Bob1 Learner1"
    And I log out

    # Login and check the last course accessed before Course 3 is Course 1.
    When I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    Then I should see "Course 1" in the "Last Course Accessed" "block"
    And I log out

    # Login as admin so we can unenrol the learner from Course 2.
    When I log in as "admin"
    And I am on "Course 2" course homepage
    Then I should see "C2" in the ".breadcrumb-nav" "css_element"

    # Unenrol the learner from Course 2.
    When I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Unenrol" "link" in the "Bob1 Learner1" "table_row"
    And I press "Continue"
    Then I should not see "Bob1 Learner1"
    And I log out

    # Login and check the last course accessed is Course 1.
    # Unenrolling the learner from Course 2 should not affect the block.
    When I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    Then I should see "Course 1" in the "Last Course Accessed" "block"
    And I log out
