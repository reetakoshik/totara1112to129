@javascript @mod @mod_facetoface @totara
Feature: Seminar sign-up periods validation
  In order to verify seminar sign-up periods
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

  Scenario Outline: Test sign-up period validation
    Given I follow "Add a new event"
    And I set the following fields to these values:
      | registrationtimestart[enabled]   | 1                  |
      | registrationtimestart[month]     | July               |
      | registrationtimestart[day]       | <periodstartday>   |
      | registrationtimestart[year]      | 2030               |
      | registrationtimestart[hour]      | <periodstarthour>  |
      | registrationtimestart[minute]    | 00                 |
      | registrationtimestart[timezone]  | <periodstartzone>  |
      | registrationtimefinish[enabled]  | 1                  |
      | registrationtimefinish[month]    | July               |
      | registrationtimefinish[day]      | <periodendday>     |
      | registrationtimefinish[year]     | 2030               |
      | registrationtimefinish[hour]     | <periodendhour>    |
      | registrationtimefinish[minute]   | 00                 |
      | registrationtimefinish[timezone] | <periodendzone>    |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[month]     | July               |
      | timestart[day]       | <sessionstartday>  |
      | timestart[year]      | 2030               |
      | timestart[hour]      | <sessionstarthour> |
      | timestart[minute]    | 00                 |
      | timestart[timezone]  | <sessionstartzone> |
      | timefinish[month]    | July               |
      | timefinish[day]      | <sessionendday>    |
      | timefinish[year]     | 2030               |
      | timefinish[hour]     | <sessionendhour>   |
      | timefinish[minute]   | 00                 |
      | timefinish[timezone] | Pacific/Auckland   |
    And I press "OK"
    And I wait "1" seconds
    And I press "Save changes"
    Then I should see "<message>"

    Examples:
      | periodstartday | periodstarthour | periodstartzone  | periodendday | periodendhour | periodendzone    | sessionstartday | sessionstarthour | sessionstartzone | sessionendday | sessionendhour | message                                                             | description unused                       |
      | 1              | 01              | Pacific/Auckland | 15           | 01            | Pacific/Auckland | 20              | 09               | Pacific/Auckland | 20            | 10             | Upcoming events                                                     | Normal case                              |
      | 16             | 01              | Pacific/Auckland | 15           | 01            | Pacific/Auckland | 20              | 09               | Pacific/Auckland | 20            | 10             | Sign-up period start time must be before sign-up finish time        | Clear start sign-up > end sign-up        |
      | 15             | 01              | Pacific/Auckland | 15           | 01            | Pacific/Auckland | 20              | 09               | Pacific/Auckland | 20            | 10             | Sign-up period start time must be before sign-up finish time        | Start sign-up = End Sign-up              |
      | 1              | 01              | Pacific/Auckland | 15           | 01            | Pacific/Auckland | 10              | 09               | Pacific/Auckland | 20            | 10             | Sign-up period closing time must be on or before session start time | session date inside sign-up range        |
      | 12             | 01              | Pacific/Auckland | 15           | 01            | Pacific/Auckland | 10              | 09               | Pacific/Auckland | 10            | 10             | Sign-up period opening time must be before session start time       | Clear session start before sign-up start |
      | 10             | 09              | Pacific/Auckland | 15           | 01            | Pacific/Auckland | 10              | 09               | Pacific/Auckland | 10            | 10             | Sign-up period opening time must be before session start time       | Sign-up start = session start            |
      | 1              | 01              | Pacific/Auckland | 20           | 09            | Pacific/Auckland | 20              | 09               | Pacific/Auckland | 20            | 10             | Upcoming events                                                     | End sign-up = session start              |
      # And now for some timezone fun
      | 15             | 01              | Europe/London    | 15           | 13            | Pacific/Auckland | 20              | 09               | Pacific/Auckland | 20            | 10             | Upcoming events                                                     | Normal case                              |
      | 15             | 02              | Europe/London    | 15           | 13            | Pacific/Auckland | 20              | 09               | Pacific/Auckland | 20            | 10             | Sign-up period start time must be before sign-up finish time        | Start sign-up = End Sign-up              |
      | 15             | 03              | Europe/London    | 15           | 13            | Pacific/Auckland | 20              | 09               | Pacific/Auckland | 20            | 10             | Sign-up period start time must be before sign-up finish time        | Clear start sign-up > end sign-up        |
      | 15             | 01              | Europe/London    | 15           | 23            | Pacific/Auckland | 20              | 12               | Pacific/Auckland | 20            | 13             | Upcoming events                                                     | Normal case                              |
      | 15             | 02              | Europe/London    | 15           | 23            | Pacific/Auckland | 15              | 12               | Pacific/Auckland | 20            | 13             | Sign-up period opening time must be before session start time       | Start sign-up = start session            |
      | 15             | 03              | Europe/London    | 15           | 23            | Pacific/Auckland | 15              | 12               | Pacific/Auckland | 20            | 13             | Sign-up period opening time must be before session start time       | Start sign-up > start session            |
      | 15             | 13              | Pacific/Auckland | 15           | 01            | Europe/London    | 20              | 09               | Pacific/Auckland | 20            | 10             | Sign-up period start time must be before sign-up finish time        | Normal case                              |
      | 15             | 14              | Pacific/Auckland | 15           | 01            | Europe/London    | 20              | 09               | Pacific/Auckland | 20            | 10             | Sign-up period start time must be before sign-up finish time        | Start sign-up = End Sign-up              |
      | 15             | 15              | Pacific/Auckland | 15           | 01            | Europe/London    | 20              | 09               | Pacific/Auckland | 20            | 10             | Sign-up period start time must be before sign-up finish time        | Clear start sign-up > end sign-up        |
      | 15             | 11              | Pacific/Auckland | 15           | 12            | Pacific/Auckland | 15              | 01               | Europe/London    | 20            | 10             | Upcoming events                                                     | Normal case                              |
      | 15             | 12              | Pacific/Auckland | 20           | 01            | Pacific/Auckland | 15              | 01               | Europe/London    | 20            | 10             | Sign-up period opening time must be before session start time       | Sign-up start = session start            |
      | 15             | 13              | Pacific/Auckland | 20           | 01            | Pacific/Auckland | 15              | 01               | Europe/London    | 20            | 10             | Sign-up period opening time must be before session start time       | Sign-up start > session start            |
