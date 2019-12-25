@totara @core @core_course
Feature: An icon can be selected for a course
  In order to test I can give a course an icon
  As an admin I will set and icon and then change it

  @javascript
  Scenario: I can select an icon for a course and then change it
    Given I am on a totara site
    And I log in as "admin"
    And I create a course with:
      | Course full name  | Course 1 |
      | Course short name | C1 |
    When I navigate to "Edit settings" node in "Course administration"
    Then I should see "Course icon"
    And I should see "Current icon"

    When I click on "Choose icon" "button"
    And I click on "img[title='Event Management']" "css_element" in the "#icon-selectable" "css_element"
    And I click on "OK" "link_or_button" in the "div[aria-describedby='icon-dialog']" "css_element"
    And I wait "1" seconds
    Then I should see the "Event Management" image in the "#fitem_id_currenticon" "css_element"

    When I press "Save and display"
    And I navigate to "Edit settings" node in "Course administration"
    Then I should see the "Event Management" image in the "#fitem_id_currenticon" "css_element"
    And I should not see the "Emotional Intelligence" image in the "#fitem_id_currenticon" "css_element"

    When I click on "Choose icon" "button"
    And I click on "img[title='Emotional Intelligence']" "css_element" in the "#icon-selectable" "css_element"
    And I click on "OK" "link_or_button" in the "div[aria-describedby='icon-dialog']" "css_element"
    And I wait "1" seconds
    And I should see the "Emotional Intelligence" image in the "#fitem_id_currenticon" "css_element"

    When I press "Save and display"
    And I navigate to "Edit settings" node in "Course administration"
    Then I should not see the "Event Management" image in the "#fitem_id_currenticon" "css_element"
    And I should see the "Emotional Intelligence" image in the "#fitem_id_currenticon" "css_element"
