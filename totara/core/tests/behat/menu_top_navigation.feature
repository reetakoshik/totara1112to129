@totara @totara_core @totara_core_menu @javascript
Feature: Totara Main menu navigation
  In order to navigate the site
  As a user
  I need to be able to use the Top Navigation Menu

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I set the following administration settings values:
      | catalogtype | enhanced |
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I click on "Add new menu item" "button"
    And I set the following Totara form fields to these values:
      | Type                     | Parent          |
      | Parent item              | Find Learning   |
      | Menu title               | Extra courses   |
      | Visibility               | Show            |
    And I click on "Add" "button"
    And I click on "Add new menu item" "button"
    And I set the following Totara form fields to these values:
      | Type                     | URL             |
      | Parent item              | Find Learning / Extra courses |
      | Menu title               | 3rd Level item  |
      | Menu url address         | /admin/user.php |
      | Visibility               | Show            |
    And I click on "Add" "button"

  Scenario Outline: Navigation menu expanding and collapsing works on top level
    # Toggling navigation and waiting for a second is only necessary for small window size
    When I change viewport size to "<window_size>"
    And I <toggle_nav_action>

    # Click on "Find learning" to open drop-down menu.
    And I click on "Find Learning" "link" in the ".totaraNav" "css_element"
    Then Totara menu item "Find Learning" should be expanded
    And Totara menu item "Find Learning" should not be highlighted
    And I should see "Extra courses" in the totara menu
    And I should see "3rd Level item" in the totara menu

    # Click on "Find learning" again to close drop-down menu.
    When I click on "Find Learning" "link" in the ".totaraNav" "css_element"
    Then Totara menu item "Find Learning" should not be expanded
    And Totara menu item "Find Learning" should not be highlighted
    And I should see "Extra courses" in the totara menu

    # Open the same drop-down menu again.
    When I click on "Find Learning" "link" in the ".totaraNav" "css_element"
    Then Totara menu item "Find Learning" should be expanded
    And Totara menu item "Find Learning" should not be highlighted
    And I should see "Extra courses" in the totara menu
    And I should see "3rd Level item" in the totara menu

    # Expand sub-item in the drop-down menu.
    When I click on "Extra courses" "link" in the ".totaraNav" "css_element"
    Then Totara menu item "Find Learning" should be expanded
    And Totara menu item "Extra courses" should be expanded
    And I should see "3rd Level item" in the totara menu

    # Collapse and expand the whole drop-down again and verify the sub-item is also collapsed.
    When I click on "Find Learning" "link" in the ".totaraNav" "css_element"
    Then Totara menu item "Find Learning" should not be expanded
    When I click on "Find Learning" "link" in the ".totaraNav" "css_element"
    Then Totara menu item "Find Learning" should be expanded
    And Totara menu item "Extra courses" should not be expanded

    # Expand and collapse sub-item in the drop-down menu.
    When I click on "Extra courses" "link" in the ".totaraNav" "css_element"
    Then Totara menu item "Find Learning" should be expanded
    And Totara menu item "Extra courses" should be expanded
    And I should see "3rd Level item" in the totara menu
    When I click on "Extra courses" "link" in the ".totaraNav" "css_element"
    Then Totara menu item "Find Learning" should be expanded
    And Totara menu item "Extra courses" should not be expanded
    And I should see "3rd Level item" in the totara menu

    # Expand sub-item again and click on it.
    When I click on "Extra courses" "link" in the ".totaraNav" "css_element"
    And I start watching to see if a new page loads
    And I click on "3rd Level item" "link" in the ".totaraNav" "css_element"
    Then a new page should have loaded since I started watching

    Examples:
      | window_size | toggle_nav_action                             |
      | small       | click on "Toggle navigation" "link_or_button" |
      | medium      | wait "0" seconds                              |

  Scenario: Navigation menu expanding and collapsing works on second level
    # Click on "Certifications" to load a page with second level navigation displayed.
    When I click on "Find Learning" "link" in the ".totaraNav" "css_element"
    And I start watching to see if a new page loads
    And I click on "Certifications" "link" in the ".totaraNav" "css_element"
    Then a new page should have loaded since I started watching
    And Totara sub menu item "Certifications" should be highlighted
    And Totara sub menu item "Extra courses" should not be highlighted
    And I should see "Extra courses" in the totara menu
    And I should see "3rd Level item" in the totara menu

    # Expand and collapse second level drop-down.
    When I click on "Extra courses" "link" in the ".totaraNav_sub--list" "css_element"
    Then Totara sub menu item "Extra courses" should be expanded
    And I should see "3rd Level item" in the totara menu
    When I click on "Extra courses" "link" in the ".totaraNav_sub--list" "css_element"
    Then Totara sub menu item "Extra courses" should not be expanded
    And I should see "3rd Level item" in the totara menu

    # Expand again and click the link.
    When I click on "Extra courses" "link" in the ".totaraNav_sub--list" "css_element"
    And I start watching to see if a new page loads
    And I click on "3rd Level item" "link" in the ".totaraNav_sub--list" "css_element"
    Then a new page should have loaded since I started watching
    # Check regression from TL-20194 where second level navigation wasn't showing when visiting third level pages
    And I should see "Extra courses" in the ".totaraNav_sub--list" "css_element"