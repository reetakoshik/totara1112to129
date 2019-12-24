@totara @totara_core
Feature: Test page title step

  @javascript
  Scenario: Test I should see page title with javascript
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | summary               | format |
      | Course 1 | C1        | <p>Course summary</p> | topics |
    And I log in as "admin"

    When I click on "Home" in the totara menu
    Then I should see "Acceptance test site" in the page title
    And I should see "ance test " in the page title

    When I click on "Dashboard" in the totara menu
    Then I should see "Acceptance test site: My Learning" in the page title

    When I click on "Home" in the totara menu
    And I follow "Course 1"
    Then I should see "Course: Course 1" in the page title
    And I should see "Course 1" in the page title

  Scenario: Test I should see page title without javascript
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | summary               | format |
      | Course 1 | C1        | <p>Course summary</p> | topics |
    And I log in as "admin"

    When I click on "Home" in the totara menu
    Then I should see "Acceptance test site" in the page title

    When I click on "Dashboard" in the totara menu
    Then I should see "Acceptance test site: My Learning" in the page title

    When I click on "Home" in the totara menu
    And I follow "Course 1"
    Then I should see "Course: Course 1" in the page title
    And I should see "Course 1" in the page title
