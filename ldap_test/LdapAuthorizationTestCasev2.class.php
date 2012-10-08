<?php
// $Id$

/**
 * @file
 * simpletests for ldap authorization
 *
 */

ldap_servers_module_load_include('php', 'ldap_test', 'LdapTestCasev2.class');
module_load_include('php', 'ldap_authorization', 'LdapAuthorizationConsumerConfAdmin.class');

class LdapAuthorizationTestCasev2 extends LdapTestCasev2 {

  public $module_name = 'ldap_authorization';
  public $consumerType = 'drupal_role'; 

  function setUp() {
    parent::setUp(array('ldap_authentication', 'ldap_authorization', 'ldap_authorization_drupal_role', 'ldap_authorization_og', 'ldap_test'));
  }

  function tearDown() {
    parent::tearDown();
  }


  function prepTestData() {
    parent::prepTestData();
    // if only one server, set as default in authorization
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
    $this->testFunctions->prepTestConfiguration($this->testData, FALSE);
  }

}
