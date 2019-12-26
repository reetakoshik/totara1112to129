@tool @tool_usertours
Feature: Adding a step to a tour with the default set of options
  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I add a new user tour with:
      | Name                     | Tour 1   |
      | Description              | Tour 101 |
      | Show with backdrop       | 1        |
      | Show if target not found | 1        |
      | Proceed on click         | 1        |

  @javascript
  Scenario: I add a step to a user's tour
    Given I navigate to "Development > Experimental > User tours" in site administration
    And I follow "Tour 1"
    And I follow "New step"
    And I follow "Options"
    And I should not see "Default (No)"
    And I should see "Default (Yes)"
    And I set the following fields to these values:
      | Title   | Step 1          |
      | Content | Step 1 contents |
    When I click on "Save changes" "button"
    Then I should see "Step 1"
    And I should see "Step 1 contents"
