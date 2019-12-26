@block @block_admin_bookmarks @javascript
Feature: Add a bookmarks to an admin pages
  In order to speed up common tasks
  As an admin
  I need to add and access pages through bookmarks

  Background:
    Given I log in as "admin"
    And I navigate to "Notifications" node in "Site administration > System information"
    And I click on "Blocks editing on" "button"
    And I add the "Admin bookmarks" block if not present
    And I configure the "Admin bookmarks" block
    And I set the following fields to these values:
      | Display on page types | Any site administration page |
    And I press "Save changes"
    And I click on "Blocks editing off" "button"
    And I navigate to "Scheduled tasks" node in "Site administration > Server"
    And I click on "Bookmark this page" "link" in the "Admin bookmarks" "block"
    And I log out

  # Test bookmark functionality using the "User profile fields" page as our bookmark.
  Scenario: Admin page can be bookmarked
    Given I log in as "admin"
    And I navigate to "User profile fields" node in "Site administration > Users"
    When I click on "Bookmark this page" "link" in the "Admin bookmarks" "block"
    Then I should see "User profile fields" in the "Admin bookmarks" "block"
    # See the existing bookmark is there too.
    And I should see "Scheduled tasks" in the "Admin bookmarks" "block"

  Scenario: Admin page can be accessed through bookmarks block
    Given I log in as "admin"
    And I navigate to "Notifications" node in "Site administration > System information"
    And I click on "Scheduled tasks" "link" in the "Admin bookmarks" "block"
    # Verify that we are on the right page.
    Then I should see "Scheduled tasks" in the page title

  Scenario: Admin page can be removed from bookmarks
    Given I log in as "admin"
    And I navigate to "Notifications" node in "Site administration > System information"
    And I click on "Scheduled tasks" "link" in the "Admin bookmarks" "block"
    When I click on "Unbookmark this page" "link" in the "Admin bookmarks" "block"
    Then I should see "Bookmark deleted"
    And I wait to be redirected
    And I should not see "Scheduled tasks" in the "Admin bookmarks" "block"

  # Facetoface report pages.
  Scenario: Admin facetoface report pages can be bookmarked
    Given I log in as "admin"

    # Sessions report
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Seminars:"
    And I press "id_submitgroupstandard_addfilter"
    And I follow "Seminars: View and manage sessions"
    And I follow "View This Report"
    When I click on "Bookmark this page" "link" in the "Admin bookmarks" "block"
    Then I should see "Sessions report" in the "Admin bookmarks" "block"

    # Events report
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Seminars:"
    And I press "id_submitgroupstandard_addfilter"
    And I follow "Seminars: View and manage events"
    And I follow "View This Report"
    When I click on "Bookmark this page" "link" in the "Admin bookmarks" "block"
    Then I should see "Events report" in the "Admin bookmarks" "block"

    # Rooms report
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Seminars:"
    And I press "id_submitgroupstandard_addfilter"
    And I follow "Seminars: Manage rooms"
    And I follow "View This Report"
    When I click on "Bookmark this page" "link" in the "Admin bookmarks" "block"
    Then I should see "Rooms" in the "Admin bookmarks" "block"

    # Assets report
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Seminars:"
    And I press "id_submitgroupstandard_addfilter"
    And I follow "Seminars: Manage assets"
    And I follow "View This Report"
    When I click on "Bookmark this page" "link" in the "Admin bookmarks" "block"
    Then I should see "Assets" in the "Admin bookmarks" "block"
