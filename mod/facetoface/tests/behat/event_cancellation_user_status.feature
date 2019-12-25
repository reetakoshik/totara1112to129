@mod @mod_facetoface @totara @javascript
Feature: Seminar event cancellation status
  After seminar events have been cancelled
  As admin
  I need to check the status for each user associated with it

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |
      | learner3 | Learner   | Three    | learner3@example.com |
      | learner4 | Learner   | Four     | learner4@example.com |
      | learner5 | Learner   | Five     | learner5@example.com |
      | manager1 | Manager   | One      | manager1@example.com |

    And the following job assignments exist:
      | user     | manager  |
      | learner1 | manager1 |
      | learner2 | manager1 |
      | learner3 | manager1 |
      | learner4 | manager1 |
      | learner5 | manager1 |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    Given the following "course enrolments" exist:
      | user     | course | role            |
      | learner3 | C1     | student         |
      | learner4 | C1     | student         |
      | manager1 | C1     | editingteacher  |

    Given I log in as "admin"
    And I expand "Site administration" node
    And I expand "Plugins" node
    And I expand "Enrolments" node
    And I follow "Manage enrol plugins"
    And I click on "Enable" "link" in the "Seminar direct enrolment" "table_row"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                               | Test Seminar |
      | Description                        | Test Seminar |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | sessiontimezone     | Pacific/Auckland |
      | timestart[day]      | 10               |
      | timestart[month]    | 2                |
      | timestart[year]     | 2025             |
      | timestart[hour]     | 9                |
      | timestart[minute]   | 0                |
      | timestart[timezone] | Pacific/Auckland |
      | timefinish[day]     | 10               |
      | timefinish[month]   | 2                |
      | timefinish[year]    | 2025             |
      | timefinish[hour]    | 15               |
      | timefinish[minute]  | 0                |
      | timefinish[timezone]| Pacific/Auckland |
    And I press "OK"
    And I press "Save changes"

  # -------------------------------------------------------------------------------------
  Scenario: Event cancellation in a Seminar with manager approval required.
    Given I click on "Test Seminar" "link"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I expand all fieldsets
    And I click on "#id_approvaloptions_approval_manager" "css_element"
    And I press "Save and display"

    Then I am on "Course 1" course homepage
    And I navigate to "Enrolment methods" node in "Course administration > Users"
    And I set the field "Add method" to "Seminar direct enrolment"
    And I press "Add method"
    And I log out

#    Users requesting approval
    Given I log in as "learner1"
    And I am on "Course 1" course homepage
    And I should see "Request approval"
    And I follow "Request approval"
    And I should see "Manager Approval"
    And I press "Request approval"
    And I log out

    Given I log in as "learner2"
    And I am on "Course 1" course homepage
    And I should see "Request approval"
    And I follow "Request approval"
    And I should see "Manager Approval"
    And I press "Request approval"
    And I log out

#   Learner Five requesting approval and immediately withdrawing his pending request
    Given I log in as "learner5"
    And I am on "Course 1" course homepage
    And I should see "Request approval"
    And I follow "Request approval"
    And I should see "Manager Approval"
    And I press "Request approval"
    And I should see "Your request was sent to your manager for approval."
    Then I am on "Course 1" course homepage
    And I should see "It is not possible to sign up for these events (manager request already pending)."
    And I should see "Withdraw pending request"
    And I click on "Withdraw pending request" "link"
    And I press "Confirm"
    And I should see "Request approval"
    And I log out

#   Manager adding Learners 3 and 4 as attendees, approving Learner 1 and declining request for Learner 2
    Given I log in as "manager1"
    And I am on "Course 1" course homepage
    And I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Learner Three, learner3@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I click on "Learner Four, learner4@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"

    When I follow "Approval required"
    Then I should see "Learner One"
    And I should see "Learner Two"
    And I should see "Learner Three"
    And I should see "Learner Four"
    And I should not see "Learner Five"

    And I set the following fields to these values:
      | Approve Learner Three for this event | 1 |
      | Approve Learner Four for this event  | 1 |
      | Approve Learner One for this event   | 1 |
      | Decline Learner Two for this event   | 1 |
    And I press "Update requests"
    Then I should see "Attendance requests updated"
    And I should see "No pending approvals"

#   Checking users status as a manager
    When I follow "Attendees"
    Then I should see "Learner Three" in the "#facetoface_sessions" "css_element"
    And I should see "Learner Four" in the "#facetoface_sessions" "css_element"
    And I should see "Learner One" in the "#facetoface_sessions" "css_element"
    And I should not see "Learner Two" in the "#facetoface_sessions" "css_element"
    And I should not see "Learner Five" in the "#facetoface_sessions" "css_element"
    When I follow "Cancellations"
    Then I should see "Learner Five" in the ".cancellations" "css_element"
    And I should not see "Learner Two" in the ".cancellations" "css_element"
    And I run all adhoc tasks
    And I log out

#  Checking status as learners
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar booking confirmation: Test Seminar"
    And I log out

    Given I log in as "learner2"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar booking decline"
    And I click on "Dashboard" in the totara menu
    And I should not see "Course 1" in the "div.block_current_learning" "css_element"
    And I log out

    Given I log in as "learner3"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar booking confirmation: Test Seminar"
    And I click on "Dashboard" in the totara menu
    And I should see "Course 1" in the "div.block_current_learning" "css_element"
    And I log out

    Given I log in as "learner4"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar booking confirmation: Test Seminar"
    And I should see "Course 1" in the "div.block_current_learning" "css_element"
    And I log out

    Given I log in as "learner5"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar booking request: Test Seminar"
    And I should not see "Seminar booking confirmation: Test Seminar"
    And I should not see "Seminar booking decline"
    And I click on "Dashboard" in the totara menu
    And I should not see "Course 1" in the "div.block_current_learning" "css_element"
    And I log out

#  Cancel the event and check status again. Cancelled users should remain in the cancellation tab and declined users
#  should not appear anywhere
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test Seminar"
    When I click on "Cancel event" "link" in the "3 / 10" "table_row"
    And I should see "Cancelling event in"
    And I should see "Are you completely sure you want to cancel this event?"
    And I press "Yes"
    Then I should see "Event cancelled" in the ".alert-success" "css_element"
    When I click on "Attendees" "link" in the "3 / 10" "table_row"
    And I click on "Cancellations" "link"
    Then I should see "Event cancellation" in the "Learner One" "table_row"
    And I should see "Event cancellation" in the "Learner Three" "table_row"
    And I should see "Event cancellation" in the "Learner Four" "table_row"
    And I should see "User cancellation" in the "Learner Five" "table_row"
    And I should not see "Learner Two"

  # -------------------------------------------------------------------------------------
  Scenario: Event cancellation in a Seminar with users that have cancelled their session.
    Given I log out
    When I log in as "learner3"
    And I am on "Course 1" course homepage
    And I should see "Sign-up"
    And I follow "Sign-up"
    And I press "Sign-up"
    Then I should see "Your request was accepted"
    And I should not see "Sign-up"
    And I log out

    Given I log in as "manager1"
    And I am on "Course 1" course homepage
    And I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Learner One, learner1@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I click on "Learner Five, learner5@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I wait "1" seconds
    And I press "Confirm"
    Then I should see "Learner One" in the "#facetoface_sessions" "css_element"
    And I should see "Learner Three" in the "#facetoface_sessions" "css_element"
    And I should see "Learner Five" in the "#facetoface_sessions" "css_element"
    And I log out

    When I log in as "learner5"
    And I am on "Course 1" course homepage
    And I should see "Cancel booking"
    And I follow "Cancel booking"
    And I press "Yes"
    Then I should see "Your booking has been cancelled."
    And I log out

    When I log in as "manager1"
    And I am on "Course 1" course homepage
    And I click on "Attendees" "link"
    Then I should see "Learner One" in the "#facetoface_sessions" "css_element"
    And I should see "Learner Three" in the "#facetoface_sessions" "css_element"
    And I should not see "Learner Two" in the "#facetoface_sessions" "css_element"
    And I should not see "Learner Four" in the "#facetoface_sessions" "css_element"
    And I should not see "Learner Five" in the "#facetoface_sessions" "css_element"
    When I follow "Cancellations"
    Then I should see "Learner Five" in the ".cancellations" "css_element"
    And I should not see "Learner Two" in the ".cancellations" "css_element"
    And I log out

    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test Seminar"
    When I click on "Cancel event" "link"
    And I should see "Cancelling event in"
    And I should see "Are you completely sure you want to cancel this event?"
    And I press "Yes"
    Then I should see "Event cancelled" in the ".alert-success" "css_element"
    When I click on "Attendees" "link"
    And I click on "Cancellations" "link"
    Then I should see "Event cancellation" in the "Learner One" "table_row"
    And I should see "Event cancellation" in the "Learner Three" "table_row"
    And I should see "User cancellation" in the "Learner Five" "table_row"
    And I should not see "Learner Two"