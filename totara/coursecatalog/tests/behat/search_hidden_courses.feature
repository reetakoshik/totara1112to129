@totara @totara_coursecatalog
Feature: Check that searching for hidden course when toggling course catalog works as expected
  Background:
    Given I am on a totara site
    And I log in as "admin"
    And the following "users" exist:
      | username   | firstname  | lastname    | email                  |
      | user1      | user1      | user1       | user1@example.com      |
      | sysmanager | sysmanager | sysmanager  | sysmanager@example.com |
      | catmanager | catmanager | catmanager  | catmanager@example.com |

    # Create category hierarchy.
    And the following "categories" exist:
      | name | category | idnumber |
      | top  | 0        | top      |
      | cat1 | 0        | cat1     |
      | cat2 | cat1     | cat2     |

    # Create courses (set both types of visibility to ensure normal doesn't interfere).
    And the following "courses" exist:
      | fullname     | shortname    | category | visible | audiencevisible |
      | cat0_course1 | cat0_course1 | top      | 0       | 2               |
      | cat0_course2 | cat0_course2 | top      | 1       | 0               |
      | cat0_course3 | cat0_course3 | top      | 0       | 3               |
      | cat1_course1 | cat1_course1 | cat1     | 0       | 2               |
      | cat1_course2 | cat1_course2 | cat1     | 1       | 0               |
      | cat1_course3 | cat1_course3 | cat1     | 0       | 3               |
      | cat2_course1 | cat2_course1 | cat2     | 0       | 2               |
      | cat2_course2 | cat2_course2 | cat2     | 1       | 0               |
      | cat2_course3 | cat2_course3 | cat2     | 0       | 3               |
    # 2 = COHORT_VISIBLE_ALL, 0 = COHORT_VISIBLE_ENROLLED, 3 = COHORT_VISIBLE_NOUSERS.

    # Create new roles.
    And the following "roles" exist:
      | name                  | shortname             | archetype | contextlevel |
      | capmanagecategories   | capmanagecategories   |           | System       |
      | capviewhiddenlearning | capviewhiddenlearning |           | Category     |

    And the following "permission overrides" exist:
      | capability                                    | permission | role                 | contextlevel | reference |
      | moodle/category:manage                        | Allow      | capmanagecategories  | System       |           |
      | moodle/course:viewhiddencourses               | Allow      | capmanagecategories  | System       |           |

    # Note that this isn't just applying these capabilities to the role, it's applying to the role IN THE SPECIFIED CONTEXT.
    And the following "permission overrides" exist:
      | capability                                    | permission | role                  | contextlevel | reference |
      | moodle/course:viewhiddencourses               | Allow      | capviewhiddenlearning | Category     | cat1      |

    # Assign manage categories capability, so manager1 can see the site admin menu item and categories.
    And the following "system role assigns" exist:
      | user       | role                  |
      | sysmanager | capmanagecategories   |

    # Assign catmanager to the capviewhiddenlearning role in the cat1 context.
    And the following "role assigns" exist:
      | user       | role                  | contextlevel | reference |
      | catmanager | capviewhiddenlearning | Category     | cat1      |

  @javascript
  Scenario: Search for hidden courses in the old catalog
    # Configure visibility to normal vis.
    Given I set the following administration settings values:
      | audiencevisibility | 0      |
      | catalogtype        | moodle |
    And I log out

    # Seeing the course catalog as sys manager.
    When I log in as "sysmanager"
    And I click on "Courses" in the totara menu
    Then I should see "top" in the ".subcategories" "css_element"
    And I should see "cat1" in the ".subcategories" "css_element"

    When I click to expand category "top" in the course catalog
    Then I should see "cat0_course1"
    And I should see "cat0_course2"
    And I should see "cat0_course3"

    When I click to expand category "cat1" in the course catalog
    Then I should see "cat1_course1"
    And I should see "cat1_course2"
    And I should see "cat1_course3"

    When I click to expand category "cat2" in the course catalog
    Then I should see "cat2_course1"
    And I should see "cat2_course2"
    And I should see "cat2_course3"

    When I set the field "coursesearchbox" to "cat0_course3"
    And I press "Go"
    Then I should see "cat0_course3" in the ".course-search-result" "css_element"
    When I set the field "coursesearchbox" to "cat0_course2"
    And I press "Go"
    Then I should see "cat0_course2" in the ".course-search-result" "css_element"
    And I log out

    # Seeing the course catalog as user1.
    When I log in as "user1"
    And I click on "Courses" in the totara menu
    Then I should see "top" in the ".subcategories" "css_element"
    And I should see "cat1" in the ".subcategories" "css_element"

    When I click to expand category "top" in the course catalog
    Then I should see "cat0_course2"
    And I should not see "cat0_course1"
    And I should not see "cat0_course3"

    When I click to expand category "cat1" in the course catalog
    Then I should see "cat1_course2"
    And I should not see "cat1_course1"
    And I should not see "cat1_course3"

    When I click to expand category "cat2" in the course catalog
    Then I should see "cat2_course2"
    And I should not see "cat2_course1"
    And I should not see "cat2_course3"

    When I set the field "coursesearchbox" to "cat0_course3"
    And I press "Go"
    Then I should see "No courses were found with the words 'cat0_course3'"
    When I set the field "coursesearchbox" to "cat0_course2"
    And I click on "Go" "link_or_button" in the "#coursesearch2" "css_element"
    Then I should see "cat0_course2" in the ".course-search-result" "css_element"
    And I log out

     # Seeing the course catalog as catmanager.
    When I log in as "catmanager"
    And I click on "Courses" in the totara menu
    Then I should see "top" in the ".subcategories" "css_element"
    And I should see "cat1" in the ".subcategories" "css_element"

    When I click to expand category "top" in the course catalog
    Then I should see "cat0_course2"
    And I should not see "cat0_course1"
    And I should not see "cat0_course3"

    When I click to expand category "cat1" in the course catalog
    Then I should see "cat1_course2"
    And I should see "cat1_course1"
    And I should see "cat1_course3"

    When I click to expand category "cat2" in the course catalog
    Then I should see "cat2_course2"
    And I should see "cat2_course1"
    And I should see "cat2_course3"

    When I set the field "coursesearchbox" to "cat0_course3"
    And I press "Go"
    Then I should see "No courses were found with the words 'cat0_course3'"
    When I set the field "coursesearchbox" to "cat1_course1"
    And I click on "Go" "link_or_button" in the "#coursesearch2" "css_element"
    Then I should see "cat1_course1" in the ".course-search-result" "css_element"
    When I set the field "coursesearchbox" to "cat1_course2"
    And I click on "Go" "link_or_button" in the "#coursesearch2" "css_element"
    Then I should see "cat1_course2" in the ".course-search-result" "css_element"

  @javascript
  Scenario: Search for hidden courses in the enhanced catalog
  # Configure visibility to normal vis.
    Given I set the following administration settings values:
      | audiencevisibility | 0        |
      | catalogtype        | enhanced |
    And I log out

  # Seeing the enhanced catalog as sys manager.
    When I log in as "sysmanager"
    And I click on "Courses" in the totara menu
    Then I should see "cat0_course1"
    And I should see "cat0_course2"
    And I should see "cat0_course3"
    And I should see "cat1_course1"
    And I should see "cat1_course2"
    And I should see "cat1_course3"
    And I should see "cat2_course1"
    And I should see "cat2_course2"
    And I should see "cat2_course3"

    When I set the following fields to these values:
      | Search by | cat0_course3 |
    And I press "toolbarsearchbutton"
    Then I should see "cat0_course3" in the "#catalogcourses" "css_element"
    When I set the following fields to these values:
      | Search by | cat0_course2 |
    And I press "toolbarsearchbutton"
    Then I should see "cat0_course2" in the "#catalogcourses" "css_element"
    And I log out

  # Seeing the enhanced catalog as user1.
    When I log in as "user1"
    And I click on "Courses" in the totara menu
    Then I should see "cat0_course2"
    And I should see "cat1_course2"
    And I should see "cat2_course2"
    And I should not see "cat0_course1"
    And I should not see "cat0_course3"
    And I should not see "cat1_course1"
    And I should not see "cat1_course3"
    And I should not see "cat2_course1"
    And I should not see "cat2_course3"

    When I set the following fields to these values:
      | Search by | cat0_course3 |
    And I press "toolbarsearchbutton"
    Then I should see "There are no records that match your selected criteria"
    When I set the following fields to these values:
      | Search by | cat0_course2 |
    And I press "toolbarsearchbutton"
    Then I should see "cat0_course2" in the "#catalogcourses" "css_element"
    And I log out

  # Seeing the enhanced catalog as catmanager.
    When I log in as "catmanager"
    And I click on "Courses" in the totara menu
    Then I should see "cat0_course2"
    And I should see "cat1_course1"
    And I should see "cat1_course2"
    And I should see "cat1_course3"
    And I should see "cat2_course1"
    And I should see "cat2_course2"
    And I should see "cat2_course3"
    And I should not see "cat0_course1"
    And I should not see "cat0_course3"

    When I set the following fields to these values:
      | Search by | cat0_course3 |
    And I press "toolbarsearchbutton"
    Then I should see "There are no records that match your selected criteria"
    When I set the following fields to these values:
      | Search by | cat1_course1 |
    And I press "toolbarsearchbutton"
    Then I should see "cat1_course1" in the "#catalogcourses" "css_element"
    When I set the following fields to these values:
      | Search by | cat1_course2 |
    And I press "toolbarsearchbutton"
    Then I should see "cat1_course2" in the "#catalogcourses" "css_element"
    And I log out

  @javascript
  Scenario: Search for hidden courses in the enhanced catalog with audience visibility on
    # Configure visibility audience visibility.
    Given I set the following administration settings values:
      | audiencevisibility | 1        |
      | catalogtype        | enhanced |
    And I log out

    # Seeing the enhanced catalog as sys manager.
    When I log in as "sysmanager"
    And I click on "Courses" in the totara menu
    Then I should see "cat0_course1"
    And I should see "cat0_course2"
    And I should see "cat0_course3"
    And I should see "cat1_course1"
    And I should see "cat1_course2"
    And I should see "cat1_course3"
    And I should see "cat2_course1"
    And I should see "cat2_course2"
    And I should see "cat2_course3"

    When I set the following fields to these values:
      | Search by | cat0_course3 |
    And I press "toolbarsearchbutton"
    Then I should see "cat0_course3" in the "#catalogcourses" "css_element"
    When I set the following fields to these values:
      | Search by | cat0_course1 |
    And I press "toolbarsearchbutton"
    Then I should see "cat0_course1" in the "#catalogcourses" "css_element"
    And I log out

    # Seeing the enhanced catalog as user1.
    When I log in as "user1"
    And I click on "Courses" in the totara menu
    Then I should see "cat0_course1"
    And I should see "cat1_course1"
    And I should see "cat2_course1"
    And I should not see "cat0_course2"
    And I should not see "cat0_course3"
    And I should not see "cat1_course2"
    And I should not see "cat1_course3"
    And I should not see "cat2_course2"
    And I should not see "cat2_course3"

    When I set the following fields to these values:
      | Search by | cat0_course2 |
    And I press "toolbarsearchbutton"
    Then I should see "There are no records that match your selected criteria"
    When I set the following fields to these values:
      | Search by | cat0_course1 |
    And I press "toolbarsearchbutton"
    Then I should see "cat0_course1" in the "#catalogcourses" "css_element"
    And I log out

  Scenario: Seeing the enhanced catalog as catmanager
        # Configure visibility audience visibility.
    Given I set the following administration settings values:
      | audiencevisibility | 1        |
      | catalogtype        | enhanced |
    And I log out

    # Seeing the enhanced catalog as catmanager.
    When I log in as "catmanager"
    And I click on "Courses" in the totara menu

    Then I should see "cat0_course1"
    And I should not see "cat0_course2"
    And I should not see "cat0_course3"
    And I should see "cat1_course1"
    And I should see "cat1_course2"
    And I should see "cat1_course3"
    And I should see "cat2_course1"
    And I should see "cat2_course2"
    And I should see "cat2_course3"

    When I set the following fields to these values:
      | Search by | cat1_course3 |
    And I press "toolbarsearchbutton"
    Then I should see "cat1_course3" in the "#catalogcourses" "css_element"
    And I should not see "cat1_course2" in the "#catalogcourses" "css_element"
    When I set the following fields to these values:
      | Search by | cat0_course2 |
    And I press "toolbarsearchbutton"
    Then I should see "There are no records that match your selected criteria"
