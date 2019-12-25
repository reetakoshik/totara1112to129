@javascript @core @core_block
Feature: Block deletion
  In order to modify page layout
  As an admin
  I need to be able to delete blocks

  Scenario: Block can be added and deleted from an admin settings page
    Given I log in as "admin"
    And I am on site homepage
    When I navigate to "Localisation > Language settings" in site administration
    And I press "Blocks editing on"
    And I should not see "Testing block deletion"
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Override default block title | Yes                                     |
      | Block title                  | Testing block deletion                  |
      | Content                      | <p>This is some test block content </p> |
    And I press "Save changes"
    Then I should see "Testing block deletion"
    And I open the "Testing block deletion" blocks action menu
    And I click on "Delete Testing block deletion block" "link"
    Then I should see "You are about to delete a block that may appear elsewhere."
    And I press "Yes"
    Then I should not see the "Testing block deletion" block
