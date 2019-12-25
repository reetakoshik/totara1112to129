@totara @totara_reportbuilder @javascript
Feature: Test that paging in report builder works correctly
  In order to test paging in report builder
  I log in as the administrator
  And create a report with lots of content
  To test paging

  Scenario: Basic paging in report builder shows the correct data
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                 |
      | user1    | User      | 1-Test   | user1@example.com     |
      | user2    | User      | 2-Test   | user2@example.com     |
      | user3    | User      | 3-Test   | user3@example.com     |
      | user4    | User      | 4-Test   | user4@example.com     |
      | user5    | User      | 5-Test   | user5@example.com     |
      | user6    | User      | 6-Test   | user6@example.com     |
      | user7    | User      | 7-Test   | user7@example.com     |
      | user8    | User      | 8-Test   | user8@example.com     |
      | user9    | User      | 9-Test   | user9@example.com     |
      | user10   | User      | 10-Test  | user10@example.com    |
      | user11   | User      | 11-Test  | user11@example.com    |
      | user12   | User      | 12-Test  | user12@example.com    |
      | user13   | User      | 13-Test  | user13@example.com    |
      | user14   | User      | 14-Test  | user14@example.com    |
      | user15   | User      | 15-Test  | user15@example.com    |
      | user16   | User      | 16-Test  | user16@example.com    |
      | user17   | User      | 17-Test  | user17@example.com    |
      | user18   | User      | 18-Test  | user18@example.com    |
      | user19   | User      | 19-Test  | user19@example.com    |
      | user20   | User      | 20-Test  | user20@example.com    |
      | user21   | User      | 21-Test  | user21@example.com    |
      | user22   | User      | 22-Test  | user22@example.com    |
      | user23   | User      | 23-Test  | user23@example.com    |
      | user24   | User      | 24-Test  | user24@example.com    |
      | user25   | User      | 25-Test  | user25@example.com    |
      | user26   | User      | 26-Test  | user26@example.com    |
      | user27   | User      | 27-Test  | user27@example.com    |
      | user28   | User      | 28-Test  | user28@example.com    |
      | user29   | User      | 29-Test  | user29@example.com    |
      | user30   | User      | 30-Test  | user30@example.com    |
      | user31   | User      | 31-Test  | user31@example.com    |
      | user32   | User      | 32-Test  | user32@example.com    |
      | user33   | User      | 33-Test  | user33@example.com    |
      | user34   | User      | 34-Test  | user34@example.com    |
      | user35   | User      | 35-Test  | user35@example.com    |
      | user36   | User      | 36-Test  | user36@example.com    |
      | user37   | User      | 37-Test  | user37@example.com    |
      | user38   | User      | 38-Test  | user38@example.com    |
      | user39   | User      | 39-Test  | user39@example.com    |
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Custom user report"
    And I set the field "Source" to "User"
    When I press "Create report"
    Then I should see "Edit Report 'Custom user report'"

    When I follow "View This Report"
    Then I should see "Custom user report: 41 records shown"
    And ".paging" "css_element" should exist
    And I should see "Guest user" in the ".reportbuilder-table" "css_element"
    And I should see "Admin User" in the ".reportbuilder-table" "css_element"
    And I should see "User 1-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 38-Test" in the ".reportbuilder-table" "css_element"
    And I should not see "User 39-Test" in the ".reportbuilder-table" "css_element"

    When I follow "Next"
    Then I should see "Custom user report: 41 records shown"
    And I should not see "Guest user" in the ".reportbuilder-table" "css_element"
    And I should not see "Admin User" in the ".reportbuilder-table" "css_element"
    And I should not see "User 1-Test" in the ".reportbuilder-table" "css_element"
    And I should not see "User 38-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 39-Test" in the ".reportbuilder-table" "css_element"

    When I press "Edit this report"
    And I set the field "Number of records per page" to "13"
    And I press "Save changes"
    And I follow "View This Report"
    Then I should see "Custom user report: 41 records shown"
    And I should see "Guest user" in the ".reportbuilder-table" "css_element"
    And I should see "Admin User" in the ".reportbuilder-table" "css_element"
    And I should see "User 1-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 11-Test" in the ".reportbuilder-table" "css_element"
    And I should not see "User 12-Test" in the ".reportbuilder-table" "css_element"

    When I click on "2" "link" in the ".paging" "css_element"
    Then I should see "Custom user report: 41 records shown"
    And I should not see "User 11-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 12-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 24-Test" in the ".reportbuilder-table" "css_element"
    And I should not see "User 25-Test" in the ".reportbuilder-table" "css_element"
    And I should see "Previous" in the ".paging" "css_element"
    And I should see "1" in the ".paging" "css_element"
    And I should see "2" in the ".paging" "css_element"
    And I should see "3" in the ".paging" "css_element"
    And I should see "4" in the ".paging" "css_element"
    And I should not see "5" in the ".paging" "css_element"
    And I should see "Next" in the ".paging" "css_element"

    When I click on "3" "link" in the ".paging" "css_element"
    Then I should see "Custom user report: 41 records shown"
    And I should not see "User 24-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 25-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 37-Test" in the ".reportbuilder-table" "css_element"
    And I should not see "User 38-Test" in the ".reportbuilder-table" "css_element"

    When I click on "4" "link" in the ".paging" "css_element"
    Then I should see "Custom user report: 41 records shown"
    And I should not see "User 37-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 38-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 39-Test" in the ".reportbuilder-table" "css_element"

    When I click on "Previous" "link" in the ".paging" "css_element"
    Then I should see "Custom user report: 41 records shown"
    And I should not see "User 24-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 25-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 37-Test" in the ".reportbuilder-table" "css_element"
    And I should not see "User 38-Test" in the ".reportbuilder-table" "css_element"

    When I set the field "User's Fullname value" to "Test"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Custom user report: 39 records shown"
    And I should see "1" in the ".paging" "css_element"
    And I should see "2" in the ".paging" "css_element"
    And I should see "3" in the ".paging" "css_element"
    And I should see "User 1-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 13-Test" in the ".reportbuilder-table" "css_element"
    And I should not see "User 14-Test" in the ".reportbuilder-table" "css_element"

    When I click on "Next" "link" in the ".paging" "css_element"
    Then I should not see "User 1-Test" in the ".reportbuilder-table" "css_element"
    And I should not see "User 13-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 14-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 26-Test" in the ".reportbuilder-table" "css_element"
    And I should not see "User 27-Test" in the ".reportbuilder-table" "css_element"

    When I set the field "User's Fullname value" to "Admin"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Custom user report: 1 record shown"
    And I should see "Admin User" in the ".reportbuilder-table" "css_element"
    And I should not see "User 1-Test" in the ".reportbuilder-table" "css_element"
    And I should not see "User 39-Test" in the ".reportbuilder-table" "css_element"

    When I set the field "User's Fullname value" to "Test"
    And I set the field "User's Fullname field limiter" to "doesn't contain"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Custom user report: 2 records shown"
    And I should see "Admin User" in the ".reportbuilder-table" "css_element"
    And I should see "Guest user" in the ".reportbuilder-table" "css_element"
    And I should not see "User 1-Test" in the ".reportbuilder-table" "css_element"
    And I should not see "User 39-Test" in the ".reportbuilder-table" "css_element"

    # Now to quickly test the total count of these fields.
    When I press "Edit this report"
    And I click on "Performance" "link" in the ".tabtree" "css_element"
    Then I should see "Filters Performance Settings"
    And I should not see "Display a total count of records"

    When I set the following administration settings values:
      | Allow reports to show total count | 1 |
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "Custom user report"
    And I click on "Performance" "link" in the ".tabtree" "css_element"
    Then I should see "Filters Performance Settings"
    And I should see "Display a total count of records"

    When I set the field "Display a total count of records" to "1"
    And I press "Save changes"
    And I follow "View This Report"
    And I set the field "User's Fullname value" to "Test"
    And I set the field "User's Fullname field limiter" to "contains"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Custom user report: 39 of 41 records shown"
    And I should see "1" in the ".paging" "css_element"
    And I should see "2" in the ".paging" "css_element"
    And I should see "3" in the ".paging" "css_element"
    And I should see "User 1-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 13-Test" in the ".reportbuilder-table" "css_element"
    And I should not see "User 14-Test" in the ".reportbuilder-table" "css_element"

    When I click on "Next" "link" in the ".paging" "css_element"
    Then I should not see "User 1-Test" in the ".reportbuilder-table" "css_element"
    And I should not see "User 13-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 14-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 26-Test" in the ".reportbuilder-table" "css_element"
    And I should not see "User 27-Test" in the ".reportbuilder-table" "css_element"

    When I set the field "User's Fullname value" to "Admin"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Custom user report: 1 of 41 records shown"
    And I should see "Admin User" in the ".reportbuilder-table" "css_element"
    And I should not see "User 1-Test" in the ".reportbuilder-table" "css_element"
    And I should not see "User 39-Test" in the ".reportbuilder-table" "css_element"

    When I set the field "User's Fullname value" to "Test"
    And I set the field "User's Fullname field limiter" to "doesn't contain"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Custom user report: 2 of 41 records shown"
    And I should see "Admin User" in the ".reportbuilder-table" "css_element"
    And I should see "Guest user" in the ".reportbuilder-table" "css_element"
    And I should not see "User 1-Test" in the ".reportbuilder-table" "css_element"
    And I should not see "User 39-Test" in the ".reportbuilder-table" "css_element"

    When I press "Edit this report"
    And I set the field "Number of records per page" to "41"
    And I press "Save changes"
    And I click on "Performance" "link" in the ".tabtree" "css_element"
    And I set the field "Display a total count of records" to "0"
    And I press "Save changes"
    And I follow "View This Report"
    And I press "Clear"
    Then I should see "Custom user report: 41 records shown"
    And I should see "Guest user" in the ".reportbuilder-table" "css_element"
    And I should see "Admin User" in the ".reportbuilder-table" "css_element"
    And I should see "User 1-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 11-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 12-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 24-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 25-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 37-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 38-Test" in the ".reportbuilder-table" "css_element"
    And I should see "User 39-Test" in the ".reportbuilder-table" "css_element"
    And ".paging" "css_element" should not exist
