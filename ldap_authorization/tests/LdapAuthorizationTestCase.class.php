<?php
// $Id$

/**
 * @file
 * simpletests for ldap authorization
 *
 */
require_once(drupal_get_path('module', 'ldap_servers') . '/tests/LdapTestFunctions.class.php');
require_once(drupal_get_path('module', 'ldap_authorization') . '/LdapAuthorizationConsumerConfAdmin.class.php');


class LdapAuthorizationTestCase extends DrupalWebTestCase {

  public $module_name = 'ldap_authorization';
  public $testFunctions;

  // storage for test data
  public $useFeatureData;
  public $featurePath;
  public $featureName;

  public $ldapTestId;
  public $serversData;
  public $authorizationData;
  public $authenticationData;
  public $testData = array();




  public $sid; // current, or only, sid
  public $consumerType = 'drupal_role'; // current, or only, consumer type being tested

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

  function prepTestData() {

    $servers = array();
    $variables = array();
    $authentication = array();
    $authorization = array();
    $this->testFunctions = new LdapTestFunctions();
    if ($this->useFeatureData) {
       module_enable(array('ctools'), TRUE);
       module_enable(array('strongarm'), TRUE);
       module_enable(array('features'), TRUE);
       module_enable(array($this->featureName), TRUE);
        // will need to set non exportables such as bind password also
        // also need to create fake ldap server data.  use
      debug("features:" . module_exists('features') ."-". $this->featureName . ": ". module_exists($this->featureName));
      if (! (module_exists('ctools') && module_exists('strongarm') && module_exists('features') && module_exists('$this->featureName')) ) {
        drupal_set_message(t('Features and Strongarm modules must be available to use Features as configuratio of simpletests'), 'warning');
      }
      include(drupal_get_path('module', 'ldap_authorization') . '/tests/' . $this->serversData);
      $this->testData['servers'] = $servers;
      // make included fake sid match feature sid
      $this->testFunctions->prepTestConfiguration($this->testData);
    }
    else {
      include(drupal_get_path('module', 'ldap_authorization') . '/tests/' . $this->authorizationData);
      $this->testData['authorization'] = $authorization;

      include(drupal_get_path('module', 'ldap_authorization') . '/tests/' . $this->authenticationData);
      $this->testData['authentication'] = $authentication;

      include(drupal_get_path('module', 'ldap_authorization') . '/tests/' . $this->serversData);
      $this->testData['servers'] = $servers;

      $this->testData['variables'] = $variables;

      // if only one server, set as default in authentication and authorization
      if (count($this->testData['servers']) == 1) {
        $sids = array_keys($servers);
        $this->sid = $sids[0];
        foreach ($this->testData['authorization'] as $consumer_type => $consumer_conf) {
          $this->testData['authorization'][$consumer_type]['consumerType'] = $consumer_type;
          $this->testData['authorization'][$consumer_type]['sid'] = $this->sid;
        }
        $this->testData['authentication']['sids'] = array($this->sid => $this->sid);
        $this->testData['servers'][$this->sid]['sid'] = $this->sid;
      }
      $this->testFunctions->prepTestConfiguration($this->testData);
    }



  }

}
