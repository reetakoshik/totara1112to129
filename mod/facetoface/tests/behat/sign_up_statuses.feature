@mod @mod_facetoface @totara @javascript
Feature: Sign up status
  In order to ensure the status displayed for the sign-up is correct
  As admin
  I need to create seminars with different settings

  #  Sign-up status follows certain order. If the first is not met then it will look down the following statuses
  #  to show what corresponds:
  #  1. Event cancelled.
  #  2. Session in progress
  #  3. Session over
  #  4. Booked session
  #  5. Session full
  #  6. Registration not open
  #  7. Registration closed
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname |email                |
      | student1 | Sam1      | Student1 |student1@example.com |
      | student2 | Sam2      | Student2 |student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student2 | C1     | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"

  Scenario: Check session with booking full status is changed when event is cancelled.
    # Create a session with status full and then cancel it.
    Given I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | +1               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | +1               |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | +1               |
      | timefinish[minute] | 0                |
    And I press "OK"
    And I set the following fields to these values:
      | capacity           | 1                |
    And I press "Save changes"

    And I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I set the following fields to these values:
      | searchtext | Sam |
    And I click on "Search" "button" in the "#region-main" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Sam1 Student1"
    And I log out

    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    Then I should see "Booking full"
    And I should not see "Event cancelled"
    And I log out

    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on "Cancel event" "link" in the "1 / 1" "table_row"
    And I should see "Are you completely sure you want to cancel this event?"
    And I press "Yes"
    And I should see "Event cancelled" in the ".alert-success" "css_element"
    And I log out

    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    Then I should see "Event cancelled"
    And I log out

  Scenario Outline: Event cancelled should be displayed in the status column regardless the signup period
    Given I follow "Add a new event"
    And I click on "Delete" "link" in the ".f2fmanagedates" "css_element"
    And I set the following fields to these values:
      | registrationtimestart[enabled]   | <periodopen>  |
      | registrationtimestart[month]     | July          |
      | registrationtimestart[day]       | 30            |
      | registrationtimestart[year]      | <startyear>   |
      | registrationtimestart[hour]      | 01            |
      | registrationtimestart[minute]    | 00            |
      | registrationtimestart[timezone]  | <startzone>   |
      | registrationtimefinish[enabled]  | <periodclose> |
      | registrationtimefinish[month]    | July          |
      | registrationtimefinish[day]      | 30            |
      | registrationtimefinish[year]     | <endyear>     |
      | registrationtimefinish[hour]     | 01            |
      | registrationtimefinish[minute]   | 00            |
      | registrationtimefinish[timezone] | <endzone>     |
    And I press "Save changes"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "<signupavailable>"

    When I follow "View all events"
    Then I should see "<bookingstatus>"
    And I should see "<signupperiod>"
    And I should not see "Event cancelled"
    And I log out

    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on "Cancel event" "link" in the "<signupperiod>" "table_row"
    And I should see "Are you completely sure you want to cancel this event?"
    And I press "Yes"
    And I should see "Event cancelled" in the ".alert-success" "css_element"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    Then I should see "Event cancelled"
    And I log out

    Examples:
      | periodopen | startyear | startzone        | periodclose | endyear | endzone         | signupavailable     | bookingstatus                | signupperiod                                                                 |
      | 1          | 2014      | Australia/Perth  | 1           | 2015    | Australia/Perth | Sign-up unavailable | Sign-up period is now closed | 30 July 2014 1:00 AM Australia/Perth to 30 July 2015 1:00 AM Australia/Perth |
      | 1          | 2014      | Australia/Perth  | 1           | 2030    | Australia/Perth | Join waitlist       | Booking open                 | 30 July 2014 1:00 AM Australia/Perth to 30 July 2030 1:00 AM Australia/Perth |
      | 1          | 2029      | Australia/Perth  | 1           | 2030    | Australia/Perth | Sign-up unavailable | Sign-up period not open      | 30 July 2029 1:00 AM Australia/Perth to 30 July 2030 1:00 AM Australia/Perth |
      | 1          | 2029      | Pacific/Honolulu | 1           | 2030    | Pacific/Fiji    | Sign-up unavailable | Sign-up period not open      | 30 July 2029 7:00 PM Australia/Perth to 29 July 2030 9:00 PM Australia/Perth |
      | 0          | 2029      | Australia/Perth  | 0           | 2030    | Australia/Perth | Join waitlist       | Booking open                 | Booking open                                                                 |
      | 1          | 2029      | Australia/Perth  | 0           | 2030    | Australia/Perth | Sign-up unavailable | Sign-up period not open      | After 30 July 2029 1:00 AM Australia/Perth                                   |
      | 0          | 2029      | Australia/Perth  | 1           | 2030    | Australia/Perth | Join waitlist       | Booking open                 | Before 30 July 2030 1:00 AM Australia/Perth                                  |
