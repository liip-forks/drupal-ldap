<?php
// $Id$

/**
 * @file
 * simpletests for ldap authorization
 *
 */
require_once(drupal_get_path('module', 'ldap_servers') . '/tests/LdapTestFunctions.class.php');



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

  function setUp() {
    parent::setUp(array('ldap_authentication', 'ldap_authorization', 'ldap_authorization_drupal_role'));
    variable_set('ldap_simpletest', 1);
    variable_set('ldap_help_watchdog_detail', 0);
  }

  function tearDown() {
    parent::tearDown();
    variable_del('ldap_help_watchdog_detail');
    variable_del('ldap_simpletest');
  }


  function prepTestData($ldap_test_file, $ldif_file) {

    $servers = array();
    $variables = array();
    $authentication = array();
    $authorization = array();
    $this->testFunctions = new LdapTestFunctions();

    require_once(drupal_get_path('module', 'ldap_servers') . "/tests/$ldap_test_file");
    debug('test data'); debug($conf);
    $this->testData = $conf;

    // if only one server, some obvious defaults
     if (count($this->testData['ldap_servers']) == 1) {
       $sids = array_keys($this->testData['ldap_servers']);
       $this->testData['authentication']['sids'] = array($sids[0] => $sids[0]);
       $this->testData['ldap_servers'][$sids[0]]['sid'] = $sids[0];
     }

    $this->testFunctions->prepTestServers($this->testData['ldap_servers']);
    $this->testFunctions->configureAuthentication($test_data['ldap_authentication']);
    $this->testFunctions->configureLdapUser($test_data['ldap_user']);

  }

}
