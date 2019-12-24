@totara @totara_hierarchy @totara_hierarchy_competency @core_course @javascript
Feature: Test competencies are updated when linked courses are deleted

  Scenario: Delete course linked to a single competency
    Given I am on a totara site
      And the following "competency" frameworks exist:
        | fullname             | idnumber | description                |
        | Competency Framework | CFrame   | Framework for Competencies |
      And the following "competency" hierarchy exists:
        | framework | fullname       | idnumber | description                           |
        | CFrame    | Competency101  | Comp101  | Competency with linked courses        |
      And the following "courses" exist:
        | fullname | shortname | format | enablecompletion | completionstartonenrol |
        | Test 1   | tst1      | topics | 1                | 1                      |
        | Test M   | tst2      | topics | 1                | 1                      |
      And I log in as "admin"

    #link courses
    When I navigate to "Manage competencies" node in "Site administration > Hierarchies > Competencies"
      And I click on "Competency Framework" "link"
      And I click on "Competency101" "link"
      And I click on "Assign course completions" "button"
      And I click on "Miscellaneous" "link" in the "evidence" "totaradialogue"
      And I click on "Test 1" "link" in the "evidence" "totaradialogue"
      And I click on "Test M" "link" in the "evidence" "totaradialogue"
      And I click on "Save" "button" in the "evidence" "totaradialogue"
    Then "Test 1" "link" should exist in the "list-evidence" "table"
      And "Test M" "link" should exist in the "list-evidence" "table"
    When I press "Return to competency framework"
    Then "2" "link" should exist in the "Competency101" "table_row"

    #delete a linked course
    When I navigate to "Manage courses and categories" node in "Site administration > Courses"
      And I click on "Miscellaneous" "text" in the ".category-listing" "css_element"
      And I go to the courses management page
      And I click on category "Miscellaneous" in the management interface
      And I click on "delete" action for "Test 1" in management course listing
      And I press "Delete"
      And I should see "tst1 has been completely deleted"
    Then I navigate to "Manage competencies" node in "Site administration > Hierarchies > Competencies"
      And I click on "Competency Framework" "link"
      And "1" "link" should exist in the "Competency101" "table_row"
      And I click on "Competency101" "link"
      And "Test 1" "link" should not exist in the "list-evidence" "table"
      And "Test M" "link" should exist in the "list-evidence" "table"
      And "//table[@id='list-evidence']//td[contains(@class, 'cell') and contains(@class, 'c0')]/a[not(text())]" "xpath_element" should not exist


  Scenario: Delete course linked to a multiple competencies
    Given I am on a totara site
      And the following "competency" frameworks exist:
        | fullname             | idnumber | description                |
        | Competency Framework | CFrame   | Framework for Competencies |
      And the following "competency" hierarchy exists:
        | framework | fullname       | idnumber | description                           |
        | CFrame    | Competency101  | Comp101  | Competency with linked courses        |
        | CFrame    | Competency102  | Comp102  | Second Competency with linked courses |
      And the following "courses" exist:
        | fullname | shortname | format | enablecompletion | completionstartonenrol |
        | Test 1   | tst1      | topics | 1                | 1                      |
        | Test M   | tst2      | topics | 1                | 1                      |
      And I log in as "admin"

    #link courses
    When I navigate to "Manage competencies" node in "Site administration > Hierarchies > Competencies"
      And I click on "Competency Framework" "link"
      And I click on "Competency101" "link"
      And I click on "Assign course completions" "button"
      And I click on "Miscellaneous" "link" in the "evidence" "totaradialogue"
      And I click on "Test 1" "link" in the "evidence" "totaradialogue"
      And I click on "Test M" "link" in the "evidence" "totaradialogue"
      And I click on "Save" "button" in the "evidence" "totaradialogue"
    Then "Test 1" "link" should exist in the "list-evidence" "table"
      And "Test M" "link" should exist in the "list-evidence" "table"
    When I press "Return to competency framework"
    Then "2" "link" should exist in the "Competency101" "table_row"

    # Second competency
    When I click on "Competency102" "link"
      And I click on "Assign course completions" "button"
      And I click on "Miscellaneous" "link" in the "evidence" "totaradialogue"
      And I click on "Test M" "link" in the "evidence" "totaradialogue"
      And I click on "Save" "button" in the "evidence" "totaradialogue"
    Then "Test M" "link" should exist in the "list-evidence" "table"
    Then "Test 1" "link" should not exist in the "list-evidence" "table"
    When I press "Return to competency framework"
    Then "1" "link" should exist in the "Competency102" "table_row"

    # delete the linked course
    When I navigate to "Manage courses and categories" node in "Site administration > Courses"
      And I click on "Miscellaneous" "text" in the ".category-listing" "css_element"
      And I go to the courses management page
      And I click on category "Miscellaneous" in the management interface
      And I click on "delete" action for "Test M" in management course listing
      And I press "Delete"
      And I should see "tst2 has been completely deleted"
    Then I navigate to "Manage competencies" node in "Site administration > Hierarchies > Competencies"
      And I click on "Competency Framework" "link"
      And "1" "link" should exist in the "Competency101" "table_row"
      And "0" "link" should exist in the "Competency102" "table_row"
      And I click on "Competency101" "link"
      And "Test 1" "link" should exist in the "list-evidence" "table"
      And "Test M" "link" should not exist in the "list-evidence" "table"
      And "//table[@id='list-evidence']//td[contains(@class, 'cell') and contains(@class, 'c0')]/a[not(text())]" "xpath_element" should not exist
      And I press "Return to competency framework"
      And I click on "Competency102" "link"
      And "Test M" "link" should not exist in the "list-evidence" "table"
      And "//table[@id='list-evidence']//td[contains(@class, 'cell') and contains(@class, 'c0')]/a[not(text())]" "xpath_element" should not exist
