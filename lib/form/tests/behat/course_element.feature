@mod @core_form @javascript
Feature: Test that the course form element works

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | user1    | first1    | last1    |
    And the following "courses" exist:
      | fullname | shortname | visible |
      | course1  | course1   | 1       |
      | course2  | course2   | 1       |
      | course3  | course3   | 1       |
      | course4  | course4   | 0       |
    And the following "roles" exist:
      | name      | shortname |
      | mapcourse | mapcourse |
    And the following "permission overrides" exist:
      | capability             | permission | role      | contextlevel | reference |
      | mod/feedback:mapcourse | Allow      | mapcourse | System       |           |
    And the following "system role assigns" exist:
      | user  | role      |
      | user1 | mapcourse |
    And the following "activities" exist:
      | activity   | name              | course               | idnumber  |
      | feedback   | frontpagefeedback | Acceptance test site | feedback0 |
    And I log in as "admin"
    And I turn editing mode on
    And I add the "Main menu" block
    And I log out

  Scenario: User can select courses using the course form element
    When I log in as "user1"
    And I am on site homepage
    And I click on "frontpagefeedback" "link" in the "Main menu" "block"
    And I click on "Map feedback to courses" "link"

    # Searching for "c" finds all three no-hidden courses.
    And I search for "c" in the "Courses" autocomplete
    Then I should see "course1" in the ".form-autocomplete-suggestions" "css_element"
    And I should see "course2" in the ".form-autocomplete-suggestions" "css_element"
    And I should see "course3" in the ".form-autocomplete-suggestions" "css_element"
    And I should not see "course4" in the ".form-autocomplete-suggestions" "css_element"
    And I should not see "Acceptance test site" in the ".form-autocomplete-suggestions" "css_element"

    # Searching for "2" finds just course2.
    When I search for "2" in the "Courses" autocomplete
    Then I should not see "course1" in the ".form-autocomplete-suggestions" "css_element"
    And I should see "course2" in the ".form-autocomplete-suggestions" "css_element"
    And I should not see "course3" in the ".form-autocomplete-suggestions" "css_element"

    # Select course2. The autocomplete only contains course2, so we can click on the autocomplete itself.
    When I click on ".form-autocomplete-suggestions" "css_element"
    Then I should not see "course1"
    And I should see "course2" in the "//div[@id='fitem_id_mappedcourses']" "xpath_element"
    And I should not see "course3"

    # Searching for "3" finds just course3, but course2 is already selected.
    When I search for "3" in the "Courses" autocomplete
    Then I should not see "course1" in the ".form-autocomplete-suggestions" "css_element"
    And I should see "course2" in the "//div[@id='fitem_id_mappedcourses']" "xpath_element"
    And I should not see "course2" in the ".form-autocomplete-suggestions" "css_element"
    And I should see "course3" in the ".form-autocomplete-suggestions" "css_element"

    # Select course3. The autocomplete only contains course3, so we can click on the autocomplete itself.
    When I click on ".form-autocomplete-suggestions" "css_element"
    Then I should not see "course1"
    And I should see "course2" in the "//div[@id='fitem_id_mappedcourses']" "xpath_element"
    And I should see "course3" in the "//div[@id='fitem_id_mappedcourses']" "xpath_element"

    # Search for all courses contains "o", which is all courses.
    When I search for "o" in the "Courses" autocomplete
    Then I should see "course1" in the ".form-autocomplete-suggestions" "css_element"
    And I should see "course2" in the "//div[@id='fitem_id_mappedcourses']" "xpath_element"
    And I should not see "course2" in the ".form-autocomplete-suggestions" "css_element"
    And I should see "course3" in the "//div[@id='fitem_id_mappedcourses']" "xpath_element"
    And I should not see "course3" in the ".form-autocomplete-suggestions" "css_element"

    # Save the selected courses. When the page loads, the selected courses should be visible because they are still selected.
    When I press "Save changes"
    And I follow "Mapped courses"
    Then I should not see "course1"
    And I should see "course2" in the "//div[@id='fitem_id_mappedcourses']" "xpath_element"
    And I should see "course3" in the "//div[@id='fitem_id_mappedcourses']" "xpath_element"

  Scenario: User can use the course form element when the selected course is the site course
    # Admin adds site course.
    When I log in as "admin"
    And I am on site homepage
    And I click on "frontpagefeedback" "link" in the "Main menu" "block"
    And I click on "Map feedback to courses" "link"
    And I search for "Acceptance" in the "Courses" autocomplete
    Then I should see "Acceptance test site" in the ".form-autocomplete-suggestions" "css_element"
    When I click on ".form-autocomplete-suggestions" "css_element"
    And I press "Save changes"
    And I click on "Map feedback to courses" "link"
    Then I should see "Acceptance test site" in the "//div[@id='fitem_id_mappedcourses']" "xpath_element"
    And I log out

    # Normal user can add and save without removing the site course.
    When I log in as "user1"
    And I am on site homepage
    And I click on "frontpagefeedback" "link" in the "Main menu" "block"
    And I click on "Mapped courses" "link"
    Then I should see "Acceptance test site" in the "//div[@id='fitem_id_mappedcourses']" "xpath_element"
    When I search for "1" in the "Courses" autocomplete
    Then I should see "course1" in the ".form-autocomplete-suggestions" "css_element"
    When I click on ".form-autocomplete-suggestions" "css_element"
    And I press "Save changes"
    And I follow "Mapped courses"
    Then I should see "Acceptance test site"
    And I should see "course1"

    # Normal user can remove the site course and save.
    When I click on "//span[contains(.,'Acceptance test site')]/span" "xpath_element"
    When I press "Save changes"
    And I follow "Mapped courses"
    Then "//span[contains(.,'Acceptance test site')]/span" "xpath_element" should not exist
    And I should see "course1" in the "//div[@id='fitem_id_mappedcourses']" "xpath_element"

    # Normal user can't add the site course back.
    When I set the field "Courses" to "Acceptance"
    Then I should not see "Acceptance test site" in the ".form-autocomplete-suggestions" "css_element"

  Scenario: User can use the course form element when the selected course is hidden to them
    # Admin adds hidden course.
    When I log in as "admin"
    And I am on site homepage
    And I click on "frontpagefeedback" "link" in the "Main menu" "block"
    And I click on "Map feedback to courses" "link"
    And I search for "4" in the "Courses" autocomplete
    Then I should see "course4" in the ".form-autocomplete-suggestions" "css_element"
    When I click on ".form-autocomplete-suggestions" "css_element"
    And I press "Save changes"
    And I click on "Map feedback to courses" "link"
    Then I should see "course4" in the "//div[@id='fitem_id_mappedcourses']" "xpath_element"
    And I log out

    # Normal user can add and save without removing the hidden course.
    When I log in as "user1"
    And I am on site homepage
    And I click on "frontpagefeedback" "link" in the "Main menu" "block"
    And I click on "Mapped courses" "link"
    Then I should see "course4" in the "//div[@id='fitem_id_mappedcourses']" "xpath_element"
    When I search for "1" in the "Courses" autocomplete
    Then I should see "course1" in the ".form-autocomplete-suggestions" "css_element"
    When I click on ".form-autocomplete-suggestions" "css_element"
    And I press "Save changes"
    And I follow "Mapped courses"
    Then I should see "course4"
    And I should see "course1"

    # Normal user can remove the hidden course and save.
    When I click on "//span[contains(.,'course4')]/span" "xpath_element"
    When I press "Save changes"
    And I follow "Mapped courses"
    Then "//span[contains(.,'course4')]/span" "xpath_element" should not exist
    And I should see "course1" in the "//div[@id='fitem_id_mappedcourses']" "xpath_element"

    # Normal user can't add the hidden course back.
    When I search for "4" in the "Courses" autocomplete
    Then I should not see "course4" in the ".form-autocomplete-suggestions" "css_element"
