@course @totara @totara_catalog @javascript
Feature: Test file for catalog
  Background:
    Given I am on a totara site
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat1  | 0        | cat1     |
      | Cat2  | 0        | cat2     |
      | Cat1a | cat1     | cat1a    |
      | Cat1b | cat1     | cat1b    |
    And the following "courses" exist:
      | fullname                  | shortname | category | visible  |
      | Hello Kim Sa Rang         | course101 | 0        | 1        |
      | Wave Park Min Young       | course102 | cat2     | 0        |
      | Bye Bye Park Shin Hye     | course103 | cat1     | 1        |
      | Wow Han Hyo Joo           | course104 | cat1a    | 1        |
      | Leave Shin Min Ah         | course105 | cat1b    | 1        |
      | Motörhead with Smørrebrød | umlaut1   | 0        | 1        |
    And the following "programs" exist in "totara_program" plugin:
      | fullname                                  | shortname | idnumber | category | visible |
      | Han Hyo Joo's program                     | prog1     | prog1    | cat1     | 1       |
      | Bilbo Baggin's Secret Agents              | prog2     | prog2    | cat2     | 0       |
      | Tom and Jerry's Special Department        | prog3     | prog3    | cat1a    | 0       |
      | Kim Sa Rang's Journey to the Middle Earth | prog4     | prog4    | cat1b    | 1       |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname | shortname | category | visible |
      | cert1    | cert1     | 2        | 1       |
      | cert2    | cert2     | 3        | 0       |
      | cert3    | cert3     | 4        | 0       |
      | cert4    | cert4     | 1        | 1       |
    And the following "users" exist:
      | username | firstname | lastname |
      | jongsuk  | Jong Suk  | Lee      |
    And I log in as "admin"
    And I go to the courses management page
    And I click on "edit" action for "Cat1" in management category listing
    And I set the field "Parent category" to "1"
    And I click on "Save changes" "button"
    And I click on "edit" action for "Cat2" in management category listing
    And I set the field "Parent category" to "1"
    And I click on "Save changes" "button"

  # Full text search
  Scenario: User performs full text search within find learning home page
    Given I am on homepage
    And I click on "Find Learning" in the totara menu
    When I follow "Alphabetical"
    Then I should not see "Relevance"
    And I set the field with xpath "//*[@id='catalog_fts_input']" to "Kim"
    When I click on "Search" "button" in the "#region-main" "css_element"
    Then I should see "Relevance"
    And I should see "Hello Kim Sa Rang"
    And I should see "Kim Sa Rang's Journey to the Middle Earth"
    When I set the field with xpath "//*[@id='catalog_fts_input']" to ""
    And I click on "Search" "button" in the "#region-main" "css_element"
    Then I should not see "Relevance"
    And I should see "14 items"

  Scenario: User performs full text search with non-ASCII characters
    When I click on "Find Learning" in the totara menu
    And I set the field with xpath "//*[@id='catalog_fts_input']" to "Smørrebrød Motörhead"
    And I click on "Search" "button" in the "#region-main" "css_element"
    And I should see "Motörhead with Smørrebrød"

  # Browsing
  Scenario: User is browsing within find learning home page
    Given I am on homepage
    And I click on "Find Learning" in the totara menu
    And I should see "All"
    And I should see "Category"
    When I follow "All"
    Then I should see "Miscellaneous"
    # Click on the expand icon of category Miscellaneous
    And I click on "Expand" "link" in the "div.tw-selectTree__option" "css_element"
    When I follow "Cat1"
    Then I should see "Bye Bye Park Shin Hye"
    And I should see "Han Hyo Joo's program"
    And I should see "Kim Sa Rang's Journey to the Middle Earth"
    And I should see "Leave Shin Min Ah"
    And I should see "Tom and Jerry's Special Department"
    And I should see "Wow Han Hyo Joo"
    And I should see "cert1"
    And I should see "cert3"
    And I should see "8 items"
    And I follow "Cat1"
    # Click on the expand icon of category Cat1
    And I click on "Expand" "link" in the "div.tw-selectTree__active" "css_element"
    When I follow "Cat1a"
    Then I should see "3 items"
    And I should see "Wow Han Hyo Joo"
    And I should see "Tom and Jerry's Special Department"
    And I should see "cert3"
    When I follow "Cat1a"
    And I follow "All"
    And I should see "14 items"

  # Sorting and Viewing (Tile/List)
  Scenario: User is sorting the courses and modifying the layout of course page
    Given I am on homepage
    And I click on "Find Learning" in the totara menu
    And I should see "Sort by"
    And I should see "Alphabetical"
    And "Bilbo Baggin's Secret Agents" "text" should appear before "Bye Bye Park Shin Hye" "text"
    And I am on "Bye Bye Park Shin Hye" course homepage
    And I follow "Edit settings"
    # Update the course to change the time modified
    And I set the field "Course Type" to "Seminar"
    And I click on "Save and display" "button"
    And I click on "Find Learning" in the totara menu
    When I follow "Alphabetical"
    And I follow "Latest"
    Then "Bye Bye Park Shin Hye" "text" should appear before "Bilbo Baggin's Secret Agents" "text"
    # Viewing from tile to list
    When I click on "List view" "link"
    Then "span.fa-th-list" "css_element" should exist in the "a.tw-catalogItemStyleToggle__btn_active" "css_element"
    And "span.fa-th-large" "css_element" should not exist in the "a.tw-catalogItemStyleToggle__btn_active" "css_element"
    And "div.tw-grid__item--single-column" "css_element" should exist
    And "div.tw-grid__item--multi-column" "css_element" should not exist
    # Viewing from list to tile
    When I click on "Tile view" "link"
    Then "div.tw-grid__item--single-column" "css_element" should not exist
    And "div.tw-grid__item--multi-column" "css_element" should exist
    Then "span.fa-th-list" "css_element" should not exist in the "a.tw-catalogItemStyleToggle__btn_active" "css_element"
    And "span.fa-th-large" "css_element" should exist in the "a.tw-catalogItemStyleToggle__btn_active" "css_element"

  # Featured learning
  Scenario: Featured learning is not enabled by default, and user is not able to see it
    Given I am on homepage
    And I click on "Find Learning" in the totara menu
    When I follow "Alphabetical"
    Then I should not see "Featured"
    And I follow "Configure catalogue"
    And I follow "General"
    And I set the following Totara form fields to these values:
      | Featured learning | 1 |
    And I wait for pending js
    And I set the following Totara form fields to these values:
      | featured_learning_source | Course Type |
      | featured_learning_value  | Seminar     |
    And I click on "Save" "button"
    And I click on "Find Learning" in the totara menu
    Then I should see "Featured"
    And "Bilbo Baggin's Secret Agents" "text" should appear before "Bye Bye Park Shin Hye" "text"
    And I am on "Bye Bye Park Shin Hye" course homepage
    And I follow "Edit settings"
    And I set the field "Course Type" to "Seminar"
    And I click on "Save and display" "button"
    # When enable featured learning, it will be sorted by featured learning (default)
    When I click on "Find Learning" in the totara menu
    Then "Bye Bye Park Shin Hye" "text" should appear before "Bilbo Baggin's Secret Agents" "text"
    Then "Featured" "text" should exist in the "a[title='Bye Bye Park Shin Hye']" "css_element"

  # Visibility
  Scenario: Learner is not able to see the hidden course
    Given I log out
    And I log in as "jongsuk"
    And I click on "Find Learning" in the totara menu
    And I should not see "Bilbo Baggin's Secret Agents"
    And I should not see "Wave Park Min Young"
    And I should not see "Tom and Jerry's Special Department"
    And I should not see "cert2"
    And I should not see "cert3"
    And I should see "9 items"

  # Share feature
  Scenario: User is sharing the course catalog page
    Given I am on homepage
    And I click on "Find Learning" in the totara menu
    And I follow "Alphabetical"
    And I follow "Latest"
    # Checking the uri of catalog sharing contain orderbykey & itemstyle
    When I follow "Share"
    # The input of sharing url. At this point, when clicking the sharing button, the input
    # should appear on the browser
    # todo: checking for url content
    Then "input.tw-catalogResultsShare__expanded_input" "css_element" should exist
