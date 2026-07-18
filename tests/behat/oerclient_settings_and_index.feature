@local @local_oerclient
Feature: OER Client admin settings and landing page
  In order to configure and use the OER Client
  As an admin
  I need to reach its settings page and its landing page

  Scenario: The OER Client settings page is reachable from Site administration
    Given I log in as "admin"
    And I navigate to "Plugins > OER Client > General settings" in site administration
    Then I should see "Exchange URL"
    And I should see "Site token"

  Scenario: The OER Client landing page shows a link to browse the Exchange
    Given I log in as "admin"
    When I visit "/local/oerclient/index.php"
    Then I should see "Browse OER Exchange"
