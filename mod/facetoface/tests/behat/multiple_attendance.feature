@mod @mod_facetoface @totara @javascript @totara_certification
Feature: Take attendance for a seminar with multiple sessions
  Ensure that the correct session is being used to mark activity completion when a seminar has multiple sessions
  To test whether the old or new session date is the current completion date, we run cron, and the older date will expire,
  whereas the newer date will merely open the recert window.

  Background:
    Given I am on a totara site

    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | first1    | last1    | user1@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | course1  | course1   | 1                |
    And the following "course enrolments" exist:
      | user  | course  | role           |
      | user1 | course1 | student        |

    # Create the seminar.
    And I log in as "admin"
    And I am on "course1" course homepage with editing mode on
    And I add the "Course completion status" block
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                                    | seminar1                                          |
      | Description                             | Test seminar description                          |
      | Completion tracking                     | Show activity as complete when conditions are met |
      | completionstatusrequired[100]           | 1                                                 |
      | How many times the user can sign-up?    | Unlimited                                         |
      | Fully attended                          | 1                                                 |

    # Set course completion to f2f completion.
    And I navigate to "Course completion" node in "Course administration"
    And I set the following fields to these values:
      | Seminar - seminar1 | 1 |
    And I press "Save changes"

    # Add sessions to f2f.
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | timestart[day]       | -10           |
      | timestart[month]     | 0             |
      | timestart[year]      | 0             |
      | timestart[hour]      | 0             |
      | timestart[minute]    | -25           |
      | timefinish[day]      | -10           |
      | timefinish[month]    | 0             |
      | timefinish[year]     | 0             |
      | timefinish[hour]     | 0             |
      | timefinish[minute]   | +5            |
    And I press "OK"

    And I click on "Select room" "link"
    And I click on "Create new room" "link"
    And I set the following fields to these values:
      | Name             | later session   |
      | id_roomcapacity  | 10              |
      | Building         | Building 123    |
      | Address          | 123 Tory street |
    # It doesn't click when asked just once.
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Use Address"
    And I click on "//div[@aria-describedby='editcustomroom0-dialog']//div[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element"
    And I press "Save changes"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | timestart[day]       | -40           |
      | timestart[month]     | 0             |
      | timestart[year]      | 0             |
      | timestart[hour]      | 0             |
      | timestart[minute]    | -30           |
      | timefinish[day]      | -40           |
      | timefinish[month]    | 0             |
      | timefinish[year]     | 0             |
      | timefinish[hour]     | 0             |
      | timefinish[minute]   | 0             |
    And I press "OK"

    And I click on "Select room" "link" in the ".lastrow" "css_element"
    And I click on "Create new room" "link"
    And I set the following fields to these values:
      | Name             | earlier session |
      | id_roomcapacity  | 10              |
      | Building         | Building 123    |
      | Address          | 123 Tory street |
    # It doesn't click when asked just once.
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Use Address"
    And I click on "//div[@aria-describedby='editcustomroom0-dialog']//div[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element"
    And I press "Save changes"

    # Create the certification and add the course.
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I press "Add new certification"
    And I press "Save changes"
    And I switch to "Certification" tab
    And I set the following fields to these values:
      | recertifydatetype | 1   |
      | activenum         | 15  |
      | activeperiod      | day |
      | windownum         | 10  |
      | windowperiod      | day |
    And I click on "Save changes" "button"
    And I click on "Save all changes" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#programcontent_ce" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "course1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait until the page is ready
    And I click on "addcontent_rc" "button" in the "#programcontent_rc" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "course1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait until the page is ready
    And I click on "Save changes" "button"
    And I click on "Save all changes" "button"

    # Assign the user to the cert.
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "first1 last1 (user1@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"
    And I wait until the page is ready

  Scenario: Complete older session, archive cert, complete newer session, see newer completion date on cert
    # Complete older session.
    Then I am on "course1" course homepage
    And I click on "View all events" "link"
    And I click on "Attendees" "link" in the "earlier session" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "first1 last1, user1@example.com" "option"
    And I press exact "add"
    And I wait until the page is ready
    And I press "Continue"
    And I press "Confirm"
    And I switch to "Take attendance" tab
    And I set the field "first1 last1's attendance" to "Fully attended"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"

    # Run cron to open window and expire the older completion.
    And I run the "\totara_certification\task\update_certification_task" task

    # Verify user is expired.
    And I log out
    And I log in as "user1"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Certifications" tab
    # Due date (when it expired, -40 + 15).
    And I should see date "-25 day" formatted "%d %B %Y"
    And I should see "Overdue!" in the "Certification program fullname 101" "table_row"
    And I should see "Expired" in the "Certification program fullname 101" "table_row"

    # Complete newer session.
    And I log out
    And I log in as "admin"
    And I am on "course1" course homepage
    And I click on "View all events" "link"
    And I click on "Attendees" "link" in the "later session" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "first1 last1, user1@example.com" "option"
    And I press exact "add"
    And I wait until the page is ready
    And I press "Continue"
    And I press "Confirm"
    And I switch to "Take attendance" tab
    And I set the field "first1 last1's attendance" to "Fully attended"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"

    # Run cron to open window for the newer completion.
    And I run the "\totara_certification\task\update_certification_task" task

    # Verify user has window open.
    And I log out
    And I log in as "user1"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Certifications" tab
    # Completion, window open and expiry.
    And I should see date "-10 day" formatted "%d %b %Y"
    And I should see date "-5 day" formatted "%d %b %Y"
    And I should see date "5 day" formatted "%d %b %Y"
    And I should see "Certified" in the "Certification program fullname 101" "table_row"
    And I should see "Due for renewal" in the "Certification program fullname 101" "table_row"
    And I should see "Open" in the "Certification program fullname 101" "table_row"

  Scenario: Complete newer session, archive cert, complete older session, still see newer completion date on cert
    # Complete newer session.
    Then I am on "course1" course homepage
    And I click on "View all events" "link"
    And I click on "Attendees" "link" in the "later session" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "first1 last1, user1@example.com" "option"
    And I press exact "add"
    And I wait until the page is ready
    And I press "Continue"
    And I press "Confirm"
    And I switch to "Take attendance" tab
    And I set the field "first1 last1's attendance" to "Fully attended"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"

    # Run cron to open window for the newer completion.
    And I run the "\totara_certification\task\update_certification_task" task

    # Verify user has window open.
    And I log out
    And I log in as "user1"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Certifications" tab
    # Completion, window open and expiry.
    And I should see date "-10 day" formatted "%d %b %Y"
    And I should see date "-5 day" formatted "%d %b %Y"
    And I should see date "5 day" formatted "%d %b %Y"
    And I should see "Certified" in the "Certification program fullname 101" "table_row"
    And I should see "Due for renewal" in the "Certification program fullname 101" "table_row"
    And I should see "Open" in the "Certification program fullname 101" "table_row"

    # Complete older session.
    And I log out
    And I log in as "admin"
    And I am on "course1" course homepage
    And I click on "View all events" "link"
    And I click on "Attendees" "link" in the "earlier session" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "first1 last1, user1@example.com" "option"
    And I press exact "add"
    And I wait until the page is ready
    And I press "Continue"
    And I press "Confirm"
    And I switch to "Take attendance" tab
    And I set the field "first1 last1's attendance" to "Fully attended"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"

    # Run cron which should do nothing.
    And I run the "\totara_certification\task\update_certification_task" task

    # Verify user has window open (so older session completion didn't trigger course and cert completion).
    And I log out
    And I log in as "user1"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Certifications" tab
    # Completion, window open and expiry.
    And I should see date "-10 day" formatted "%d %b %Y"
    And I should see date "-5 day" formatted "%d %b %Y"
    And I should see date "5 day" formatted "%d %b %Y"
    And I should see "Due for renewal" in the "Certification program fullname 101" "table_row"
    And I should see "Open" in the "Certification program fullname 101" "table_row"

  Scenario: Complete newer, complete older, see newer completion date, reset activity completion, see newer completion date
    # Complete newer session.
    Then I am on "course1" course homepage
    And I click on "View all events" "link"
    And I click on "Attendees" "link" in the "later session" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "first1 last1, user1@example.com" "option"
    And I press exact "add"
    And I wait until the page is ready
    And I press "Continue"
    And I press "Confirm"
    And I switch to "Take attendance" tab
    And I set the field "first1 last1's attendance" to "Fully attended"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"

    # Verify course completed with newer session date.
    And I log out
    And I log in as "user1"
    And I am on "course1" course homepage
    And I click on "More details" "link"
    And I should see date "-10 day" formatted "%d %B %Y"

    # Complete older session.
    And I log out
    And I log in as "admin"
    And I am on "course1" course homepage
    And I click on "View all events" "link"
    And I click on "Attendees" "link" in the "earlier session" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "first1 last1, user1@example.com" "option"
    And I press exact "add"
    And I wait until the page is ready
    And I press "Continue"
    And I press "Confirm"
    And I switch to "Take attendance" tab
    And I set the field "first1 last1's attendance" to "Fully attended"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"

    # Verify course still completed with newer session date.
    And I log out
    And I log in as "user1"
    And I am on "course1" course homepage
    And I click on "More details" "link"
    And I should see date "-10 day" formatted "%d %B %Y"

    # Reset activity completion.
    And I log out
    And I log in as "admin"
    And I am on "course1" course homepage
    And I click on "seminar1" "link"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I click on "Activity completion" "link"
    And I press "Unlock completion and delete completion data"
    And I press "Save and return to course"

    # The unlocked and deleted completions won't be restored until the necessary scheduled task runs.
    # The session should then be completed with the newer date.
    And I log out
    And I log in as "user1"
    And I am on "course1" course homepage
    And I click on "More details" "link"
    Then I should see "Not completed"
    When I run the "\core\task\completion_regular_task" task
    And I follow "course1"
    And I click on "More details" "link"
    And I should see date "-10 day" formatted "%d %B %Y"

  Scenario: Complete older, complete newer, see older completion date, reset activity completion, see newer completion date
    # Complete older session.
    Then I am on "course1" course homepage
    And I click on "View all events" "link"
    And I click on "Attendees" "link" in the "earlier session" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "first1 last1, user1@example.com" "option"
    And I press exact "add"
    And I wait until the page is ready
    And I press "Continue"
    And I press "Confirm"
    And I switch to "Take attendance" tab
    And I set the field "first1 last1's attendance" to "Fully attended"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"

    # Verify course completed with older session date.
    And I log out
    And I log in as "user1"
    And I am on "course1" course homepage
    And I click on "More details" "link"
    And I should see date "-40 day" formatted "%d %B %Y"

    # Complete newer session.
    And I log out
    And I log in as "admin"
    And I am on "course1" course homepage
    And I click on "View all events" "link"
    And I click on "Attendees" "link" in the "later session" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "first1 last1, user1@example.com" "option"
    And I press exact "add"
    And I wait until the page is ready
    And I press "Continue"
    And I press "Confirm"
    And I switch to "Take attendance" tab
    And I set the field "first1 last1's attendance" to "Fully attended"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"

    # Verify course still completed with older session date.
    And I log out
    And I log in as "user1"
    And I am on "course1" course homepage
    And I click on "More details" "link"
    And I should see date "-40 day" formatted "%d %B %Y"

    # Reset activity completion.
    And I log out
    And I log in as "admin"
    And I am on "course1" course homepage
    And I click on "seminar1" "link"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I click on "Activity completion" "link"
    And I press "Unlock completion and delete completion data"
    And I press "Save and return to course"

    # The unlocked and deleted completions won't be restored until the necessary scheduled task runs.
    # The session should then be completed with the newer date.
    And I log out
    And I log in as "user1"
    And I am on "course1" course homepage
    And I click on "More details" "link"
    Then I should see "Not completed"
    When I run the "\core\task\completion_regular_task" task
    And I follow "course1"
    And I click on "More details" "link"
    And I should see date "-10 day" formatted "%d %B %Y"

  Scenario: Take attendance with minimum permissions
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | taker    | taker     | taker    | taker@example.com |
    And the following "permission overrides" exist:
      | capability                     | permission | role       | contextlevel | reference |
      | mod/facetoface:addattendees    | Prohibit   | teacher    | Course       | course1   |
      | mod/facetoface:addinstance     | Prohibit   | teacher    | Course       | course1   |
      | mod/facetoface:editevents      | Prohibit   | teacher    | Course       | course1   |
      | mod/facetoface:removeattendees | Prohibit   | teacher    | Course       | course1   |
      | mod/facetoface:takeattendance  | Allow      | teacher    | Course       | course1   |
    And the following "course enrolments" exist:
      | user  | course  | role    |
      | taker | course1 | teacher |
    And I am on "course1" course homepage
    And I click on "View all events" "link"
    And I click on "Attendees" "link" in the "earlier session" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "first1 last1, user1@example.com" "option"
    And I press exact "add"
    And I wait until the page is ready
    And I press "Continue"
    And I press "Confirm"
    And I log out
    When I log in as "taker"
    And I am on "course1" course homepage
    And I click on "seminar1" "link"
    And I click on "Attendees" "link" in the "earlier session" "table_row"
    And I switch to "Take attendance" tab
    And I set the field "first1 last1's attendance" to "Fully attended"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"
