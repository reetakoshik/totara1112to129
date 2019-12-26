@totara @totara_core @totara_core_menu
Feature: Main menu behat step testing
  Background:
    Given I am on a totara site

  @javascript
  Scenario: Test I should see in the totara menu with javascript
    When I log in as "admin"
    Then I should see "Performance" in the totara menu
    And I should see "Goals" in the totara menu
    And I should not see "Perf" in the totara menu
    And I should not see "Goal" in the totara menu

# NOTE: uncomment following one by one and make sure it fails:

#  And I should not see "Performance" in the totara menu
#  And I should see "Perf" in the totara menu
#  And I should see "Goal" in the totara menu


  @javascript
  Scenario: Test I click in the totara menu with javascript
    Given I log in as "admin"
    When I click on "Dashboard" in the totara menu
    Then I should see "You do not have any current learning."
    When I click on "Goals" in the totara menu
    Then I should see "Company Goals"


# NOTE: uncomment following one by one and make sure it fails:

#  And I click on "Dash" in the totara menu
#  And I click on "Performance" in the totara menu
#  And I click on "Perf" in the totara menu


  Scenario: Test I should see in the totara menu without javascript
    When I log in as "admin"
    Then I should see "Performance" in the totara menu
    And I should see "Goals" in the totara menu
    And I should not see "Perf" in the totara menu
    And I should not see "Goal" in the totara menu

# NOTE: uncomment following one by one and make sure it fails:

#  And I should not see "Performance" in the totara menu
#  And I should see "Perf" in the totara menu
#  And I should see "Goal" in the totara menu


  Scenario: Test I click in the totara menu without javascript
    Given I log in as "admin"
    When I click on "Dashboard" in the totara menu
    Then I should see "You do not have any current learning."
    When I click on "Goals" in the totara menu
    Then I should see "Company Goals"

# NOTE: uncomment following one by one and make sure it fails:

#  And I click on "Dash" in the totara menu
#  And I click on "Performance" in the totara menu
#  And I click on "Perf" in the totara menu

