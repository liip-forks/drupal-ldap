<?php
// $Id$

/**
 * @file
 * simpletests for ldap authorization
 *
 */
// require_once(drupal_get_path('module', 'ldap_servers') . '/tests/LdapTestFunctions.class.php');

ldap_server_module_load_include('php', 'ldap_test', 'LdapTestFunctions.class');

class LdapUserTestCase extends DrupalWebTestCase {

  public $module_name = 'ldap_user';
  public $testFunctions;

  // storage for test data
  public $useFeatureData;
  public $featurePath;
  public $featureName;

  public $ldapTestId;
  public $authorizationData;
  public $authenticationData;
  public $testData = array();

  public static function getInfo() {
    return array(
      'name' => 'LDAP User Basic Tests',
      'description' => 'Test ldap user functionality.',
      'group' => 'LDAP User'
    );
  }

  public $sid; // current, or only, sid

  function __construct($test_id = NULL) {
    parent::__construct($test_id);
    $this->testFunctions = new LdapTestFunctions();
  }

  function setUp() {
    parent::setUp(array('ldap_authentication', 'ldap_authorization', 'ldap_authorization_drupal_role', 'ldap_test'));
    variable_set('ldap_simpletest', 1);
    variable_set('ldap_help_watchdog_detail', 0);
  }

  function tearDown() {
    parent::tearDown();
    variable_del('ldap_help_watchdog_detail');
    variable_del('ldap_simpletest');
  }

  function prepTestData($sids,
      $ldap_user_conf_id = NULL,
      $ldap_authentication_conf_id = NULL,
      $ldap_authorization_conf_id = NULL,
      $ldap_feeds_conf_id = NULL,
      $ldap_query_conf_id = NULL
    ) {

    $this->testFunctions->configureLdapServers($sids);
    if ($ldap_user_conf_id) {
      $this->testFunctions->configureLdapUser($ldap_user_conf_id, $sids);
    }
    if ($ldap_authentication_conf_id) {
      $this->testFunctions->configureLdapAuthentication($ldap_authentication_conf_id, $sids);
    }
  }

}
