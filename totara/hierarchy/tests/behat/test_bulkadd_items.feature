@totara_hierarchy @totara @javascript
Feature: It is possible to bulk add a hierarchy tree structure

  Scenario Outline: A number of <hierarchy> items can be generated including children
    Given I am on a totara site
    And the following "<hierarchy>" frameworks exist:
      | fullname                  | idnumber | description           |
      | Test <hierarchy> framework   | FW001    | Framework description |
    When I log in as "admin"
    And I navigate to "Manage <hierarchyplural>" node in "Site administration > <hierarchypluralcapital>"
    And I click on "Test <hierarchy> framework" "link"
    And I select "Add" from the "jump" singleselect
    #redirect
    Given I set the following fields to these values:
      | parentid | 0 |
    And I set the field "itemnames" to multiline:
    """
    My new item
    My second new item
      My child of second new item
    """
    And I press "Save changes"
    Then I should see "My new item"
    And I should see "My second new item"
    And I should see "My child of second new item"
    And I should see "3 items were successfully added to the hierarchy"

    Examples:
      | hierarchy     | hierarchyplural | hierarchypluralcapital |
      | position      | positions       | Positions              |
      | organisation  | organisations   | Organisations          |
      | competency    | competencies    | Competencies           |
      | goal          | goals           | Goals                  |

  Scenario Outline: An invalid <hierarchy> structure gives a validation error
    Given I am on a totara site
    And the following "<hierarchy>" frameworks exist:
      | fullname                  | idnumber | description           |
      | Test <hierarchy> framework   | FW001    | Framework description |
    When I log in as "admin"
    And I navigate to "Manage <hierarchyplural>" node in "Site administration > <hierarchypluralcapital>"
    And I click on "Test <hierarchy> framework" "link"
    And I select "Add" from the "jump" singleselect
    #redirect
    Given I set the following fields to these values:
      | parentid | 0 |
    And I set the field "itemnames" to multiline:
    """
    My new item
        This is an invalid depth
      My child of new item
    """
    And I press "Save changes"
    Then I should see "Could not locate parent for item 'This is an invalid depth'."
    And I should see "Add multiple <hierarchyplural>"
    And I should not see "3 items were successfully added to the hierarchy"

    Examples:
      | hierarchy     | hierarchyplural | hierarchypluralcapital |
      | position      | positions       | Positions              |
      | organisation  | organisations   | Organisations          |
      | competency    | competencies    | Competencies           |
      | goal          | goals           | Goals                  |
