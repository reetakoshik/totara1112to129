@javascript @mod @mod_facetoface @totara
Feature: Seminar sign-up periods display
  In order to verify seminar sign-up periods display
  As a f2fadmin
  I need to set various dates

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | student1 | Stu       | Dent     | student@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"

  Scenario Outline: Check the correct text is displayed in various states when there is a sign-up period
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

    Examples:
      | periodopen | startyear | startzone        | periodclose | endyear | endzone         | signupavailable     | bookingstatus                | signupperiod                                                                 |
      | 1          | 2014      | Australia/Perth  | 1           | 2015    | Australia/Perth | Sign-up unavailable | Sign-up period is now closed | 30 July 2014 1:00 AM Australia/Perth to 30 July 2015 1:00 AM Australia/Perth |
      | 1          | 2014      | Australia/Perth  | 1           | 2030    | Australia/Perth | Join waitlist       | Booking open                 | 30 July 2014 1:00 AM Australia/Perth to 30 July 2030 1:00 AM Australia/Perth |
      | 1          | 2029      | Australia/Perth  | 1           | 2030    | Australia/Perth | Sign-up unavailable | Sign-up period not open      | 30 July 2029 1:00 AM Australia/Perth to 30 July 2030 1:00 AM Australia/Perth |
      | 1          | 2029      | Pacific/Honolulu | 1           | 2030    | Pacific/Fiji    | Sign-up unavailable | Sign-up period not open      | 30 July 2029 7:00 PM Australia/Perth to 29 July 2030 9:00 PM Australia/Perth |
      | 0          | 2029      | Australia/Perth  | 0           | 2030    | Australia/Perth | Join waitlist       | Booking open                 | Booking open                                                                 |
      | 1          | 2029      | Australia/Perth  | 0           | 2030    | Australia/Perth | Sign-up unavailable | Sign-up period not open      | After 30 July 2029 1:00 AM Australia/Perth                                   |
      | 0          | 2029      | Australia/Perth  | 1           | 2030    | Australia/Perth | Join waitlist       | Booking open                 | Before 30 July 2030 1:00 AM Australia/Perth                                  |

  Scenario Outline: Sign up students regardless of sign in period status
    Given I follow "Add a new event"
    And I click on "Delete" "link" in the ".f2fmanagedates" "css_element"
    And I set the following fields to these values:
      | registrationtimestart[enabled]  | <periodopen>  |
      | registrationtimestart[month]    | June          |
      | registrationtimestart[day]      | 30            |
      | registrationtimestart[year]     | <startyear>   |
      | registrationtimestart[hour]     | 01            |
      | registrationtimestart[minute]   | 00            |
      | registrationtimestart[timezone] | <startzone>   |
      | registrationtimefinish[enabled] | <periodclose> |
      | registrationtimefinish[month]   | June          |
      | registrationtimefinish[day]     | 30            |
      | registrationtimefinish[year]    | <endyear>     |
      | registrationtimefinish[hour]    | 01            |
      | registrationtimefinish[minute]  | 00            |
      | registrationtimefinish[timezone]| <endzone>     |
    And I press "Save changes"
    And I click on "Attendees" "link"
    And I set the field "f2f-actions" to "Add users"
    And I click on "student@example.com" "option"
    And I press exact "add"
    And I click on "Continue" "button"
    And I click on "Confirm" "button"
    And I switch to "Wait-list" tab
    And I should see "Stu Dent"

    Examples:
      | periodopen | startyear | startzone        | periodclose | endyear | endzone          |
      | 1          | 2014      | Pacific/Auckland | 1           | 2015    | Pacific/Auckland |
      | 1          | 2014      | Pacific/Auckland | 1           | 2030    | Pacific/Auckland |
      | 1          | 2029      | Pacific/Auckland | 1           | 2030    | Pacific/Auckland |
      | 1          | 2029      | Pacific/Honolulu | 1           | 2030    | Pacific/Fiji     |
      | 0          | 2029      | Pacific/Auckland | 0           | 2030    | Pacific/Auckland |
      | 1          | 2029      | Pacific/Auckland | 0           | 2030    | Pacific/Auckland |
      | 0          | 2029      | Pacific/Auckland | 1           | 2030    | Pacific/Auckland |