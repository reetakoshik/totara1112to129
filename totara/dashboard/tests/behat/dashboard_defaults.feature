@totara @totara_dashboard @javascript
Feature: Test Dashboard defaults
    In order to test the correct behaviour related to the visibility settings for the dashboard feature
    As a admin
    I need to choose among the three different settings (show/hide/disabled) and check the GUI

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                   |
      | student1 | Student   | One      | student.one@example.com |
    # Login to get the Latest announcements created.
    And I log in as "admin"
    And I am on site homepage
    And I turn editing mode on
    And I add the "Latest announcements" block
    And I click on "Dashboard" in the totara menu
    And I should see "Latest announcements"
    And I log out

  Scenario: Dashboard is default page for all users except admin by default
    When I log in as "student1"
    Then I should see "My Learning" in the ".breadcrumb-nav" "css_element"
    And I should not see "Make Dashboard my default page"
    And I should see "Current Learning"

    When I click on "Home" in the totara menu
    Then I should see "Latest announcements"
    And I should not see "Current Learning"
    And I should see "Make Home my default page"

    When I click on "Dashboard" in the totara menu
    Then I should see "My Learning" in the ".breadcrumb-nav" "css_element"
    And I should not see "Make Dashboard my default page"

    When I click on "Home" in the totara menu
    And I click on "Make Home my default page" "link"
    And I should not see "Make Home my default page"
    And I should not see "Current Learning"
    And I log out
    And I log in as "student1"
    Then I should see "Latest announcements"
    And I should not see "Current Learning"
    And I should not see "Make Home my default page"

    When I click on "Dashboard" in the totara menu
    And I click on "Make Dashboard my default page" "link"
    And I should not see "Make Dashboard my default page"
    And I should see "Current Learning"
    And I log out
    And I log in as "student1"
    Then I should see "My Learning" in the ".breadcrumb-nav" "css_element"
    And I should see "Current Learning"
    And I should not see "Make Dashboard my default page"

  Scenario: Home is default page for admin by default
    When I log in as "admin"
    Then I should see "Latest announcements"
    And I should not see "Current Learning"

    When I click on "Dashboard" in the totara menu
    Then I should see "My Learning" in the ".breadcrumb-nav" "css_element"
    And I should see "Current Learning"

    When I click on "Home" in the totara menu
    Then I should see "Latest announcements"
    And I should not see "Current Learning"

    When I click on "Dashboard" in the totara menu
    And I click on "Make Dashboard my default page" "link"
    And I should not see "Make Dashboard my default page"
    And I should see "Current Learning"
    And I log out
    And I log in as "admin"
    Then I should see "My Learning" in the ".breadcrumb-nav" "css_element"
    And I should see "Current Learning"
    And I should not see "Make Dashboard my default page"

    When I click on "Home" in the totara menu
    And I click on "Make Home my default page" "link"
    And I should not see "Make Home my default page"
    And I should not see "Current Learning"
    And I log out
    And I log in as "admin"
    Then I should see "Latest announcements"
    And I should not see "Current Learning"
    And I should not see "Make Home my default page"

  Scenario: Disable Totara Dashboard feature
    Given I log in as "admin"
    And I set the following administration settings values:
      | enabletotaradashboard | Disable |
    And I log out

    When I log in as "student1"
    Then I should see "Latest announcements"
    And I should not see "Current Learning"
    And I log out

    When I log in as "admin"
    Then I should see "Latest announcements"
    And I should not see "Current Learning"
    And I log out

  Scenario: Set Home as default user page
    Given I log in as "admin"
    And I set the following administration settings values:
      | defaulthomepage | Site |
    And I log out

    When I log in as "student1"
    Then I should see "Latest announcements"
    And I should not see "Current Learning"
    When I click on "Dashboard" in the totara menu
    And I click on "Make Dashboard my default page" "link"
    And I should not see "Make Dashboard my default page"
    And I should see "Current Learning"
    And I log out
    And I log in as "student1"
    Then I should see "My Learning" in the ".breadcrumb-nav" "css_element"
    And I should see "Current Learning"
    And I should not see "Make Dashboard my default page"
    And I log out

    When I log in as "admin"
    Then I should see "Latest announcements"
    And I should not see "Current Learning"
    When I click on "Dashboard" in the totara menu
    And I click on "Make Dashboard my default page" "link"
    And I should not see "Make Dashboard my default page"
    And I should see "Current Learning"
    And I log out
    And I log in as "admin"
    Then I should see "My Learning" in the ".breadcrumb-nav" "css_element"
    And I should see "Current Learning"
    And I should not see "Make Dashboard my default page"
    And I log out
