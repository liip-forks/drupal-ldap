<?php
// $Id$

/**
 * @file
 * simpletests base class for ldap user tests.
 *
 */

ldap_servers_module_load_include('php', 'ldap_test', 'LdapTestCasev2.class');

class LdapUserTestCasev2 extends LdapTestCasev2 {

  public $module_name = 'ldap_user';

  public static function getInfo() {
    return array(
      'name' => 'LDAP User Basic Tests',
      'description' => 'Test ldap user functionality.',
      'group' => 'LDAP User'
    );
  }

  function __construct($test_id = NULL) {
    parent::__construct($test_id);
  }

  function setUp() {
    parent::setUp(array('ldap_authentication', 'ldap_authorization', 'ldap_authorization_drupal_role', 'ldap_test'));
    $this->ldap_user_create_user_test_entity_fields();
  }

  function tearDown() {
    parent::tearDown();
  }

  function prepTestData($sids,
      $ldap_user_conf_id = NULL,
      $ldap_authentication_conf_id = NULL,
      $ldap_authorization_conf_id = NULL,
      $ldap_feeds_conf_id = NULL,
      $ldap_query_conf_id = NULL
    ) {
    parent::prepTestData($sids, $ldap_user_conf_id, $ldap_authentication_conf_id, $ldap_authorization_conf_id, $ldap_feeds_conf_id, $ldap_query_conf_id);
    
  }

}
