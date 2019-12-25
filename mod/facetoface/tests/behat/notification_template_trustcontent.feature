@mod @mod_facetoface @totara @javascript
Feature: Test notification templates update with none trust content
  In order to test notification templates
  I use unsafe chars in notification body

  Scenario: Update notification template body with unsafe chars when Enable trusted content is disabled
    Given I log in as "admin"
    And I navigate to "Notification templates" node in "Site administration > Seminars"
    And I click on "Edit" "link" in the "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]" "table_row"
    And I click on "Show more buttons" "button"
    And I click on "HTML" "button"
    And I set the field "Body" to "<a href='https://docs.google.com/a/example.com/forms/d/e/2GRStFENt3YkpRvng/viewform?entry.345654021=[facetofacename]'>Give a feedback</a>"
    And I click on "Save changes" "button"
    And I click on "Edit" "link" in the "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]" "table_row"
    And I click on "Show more buttons" "button"
    When I click on "HTML" "button"
    Then I should see "<a href=\"https://docs.google.com/a/example.com/forms/d/e/2GRStFENt3YkpRvng/viewform?entry.345654021=%5Bfacetofacename%5D\">Give a feedback</a>" in the "#id_body_editor" "css_element"

  Scenario: Update notification template body with unsafe chars when Enable trusted content is enabled
    Given I log in as "admin"
    And I set the following administration settings values:
      | Enable trusted content | 1 |
    And I navigate to "Notification templates" node in "Site administration > Seminars"
    And I click on "Edit" "link" in the "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]" "table_row"
    And I click on "Show more buttons" "button"
    And I click on "HTML" "button"
    And I set the field "Body" to "<a href='https://docs.google.com/a/example.com/forms/d/e/2GRStFENt3YkpRvng/viewform?entry.345654021=[facetofacename]'>Give a feedback</a>"
    And I click on "Save changes" "button"
    And I click on "Edit" "link" in the "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]" "table_row"
    And I click on "Show more buttons" "button"
    And I click on "HTML" "button"
    And I should see "<a href=\"https://docs.google.com/a/example.com/forms/d/e/2GRStFENt3YkpRvng/viewform?entry.345654021=[facetofacename]\">Give a feedback</a>" in the "#id_body_editor" "css_element"


