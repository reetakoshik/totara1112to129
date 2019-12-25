@core @core_calendar
Feature: Open calendar popup
  In order to view calendar information
  As a user
  I need to interact with the calendar

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
    And I log in as "admin"
    And I click on "Dashboard" in the totara menu
    And I follow "Make Dashboard my default page"

  @javascript
  Scenario: I view calendar details of a day with multiple events
    Given I follow "Go to calendar"
    And I create a calendar event:
      | Type of event     | site |
      | Event title       | Event 1:1 |
      | timestart[day]    | 1  |
    And I create a calendar event:
      | Type of event     | site |
      | Event title       | Event 1:2 |
      | timestart[day]    | 1  |
    When I hover over day "1" of this month in the calendar
    Then I should see "Event 1:1"
    And I should see "Event 1:2"
    # Replaced 'follow "Home"' with am on homepage to prevent failure
    # when Home button is hidden in the dropdown menu
    And I am on homepage
    And I follow "Go to calendar"
    And I hover over day "1" of this month in the calendar
    And I should see "Event 1:1"
    And I should see "Event 1:2"

  @javascript
  Scenario: I view calendar details for today
    Given I follow "Go to calendar"
    And I create a calendar event:
      | Type of event     | site |
      | Event title       | Today's event |
    When I hover over today in the calendar
    Then I should see "Today's event"
    # Replaced 'follow "Home"' with am on homepage to prevent failure
    # when Home button is hidden in the dropdown menu
    And I am on homepage
    And I follow "Go to calendar"
    And I hover over today in the calendar
    And I should see "Today's event"
