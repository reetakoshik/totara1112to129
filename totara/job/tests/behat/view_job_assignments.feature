@totara @totara_job @javascript
Feature: View job assignments where I am assigned as temporary manager
  When I am assigned as a temporary manager to a user
  I must be able to view to the job assignment

  Background:
    Given I am on a totara site
      And the following "users" exist:
        | username | firstname | lastname | email                   |
        | user1    | User      | One      | user1@example.com       |
        | user2    | User      | Two      | user2@example.com       |
        | user3    | User      | Three    | user3@example.com       |
        | manager1 | Manager   | One      | manager1@example.com    |
        | manager2 | Manager   | Two      | manager2@example.com    |
      And the following job assignments exist:
        | user     | fullname            | idnumber | appraiser    | manager       | managerjaidnumber | tempmanager     | tempmanagerjaidnumber  | tempmanagerexpirydate |
        | manager1 | Development Manager | 1        |              |               |                   |                 |                        |                       |
        | manager2 | Design Manager      | 1        |              |               |                   |                 |                        |                       |
        | manager2 | Brand Manager       | 2        |              |               |                   |                 |                        |                       |
        | user1    | Designer            | 1        | user2        |               |                   | manager2        | 1                      | 2228554800            |
        | user2    | Developer           | 1        | user3        | manager1      | 1                 | manager2        | 2                      | 2228554800            |

  Scenario: View job assignments where I am the the temporary manager. User has no existing manager or appraiser
    Given I log in as "manager2"
    Then I should see "You are now User One's temporary manager"
    When I click on "You are now User One's temporary manager" "link"
    Then the following fields match these values:
        | fullname                          | Designer                      |
        | idnumber                          | 1                             |
      And "Manager Two - Design Manager" "link" should exist in the "#tempmanagertitle" "css_element"
      And "User Two" "link" should exist in the "#appraisertitle" "css_element"

  Scenario: View job assignments where I am the the temporary manager. User has existing manager and appraiser
    Given I log in as "manager2"
    Then I should see "You are now User Two's temporary manager"
    When I click on "You are now User Two's temporary manager" "link"
    Then the following fields match these values:
        | fullname                          | Developer                     |
        | idnumber                          | 1                             |
      And I should see "User Three" in the "#appraisertitle" "css_element"
      And "User Three" "link" should not exist in the "#appraisertitle" "css_element"
      And I should see "Manager One - Development Manager" in the "#managertitle" "css_element"
      And "Manager One - Development Manager" "link" should not exist in the "#managertitle" "css_element"
      And "Manager Two - Brand Manager" "link" should exist in the "#tempmanagertitle" "css_element"
