<?php
// $Id$

/**
 * @file
 * simpletests
 *
 */

ldap_server_module_load_include('php', 'ldap_test', 'LdapTestFunctionsv2.class');

class LdapTestCasev2 extends DrupalWebTestCase {

  public $testFunctions;
  public $module_name;
  
  // storage for test data
  public $useFeatureData;
  public $featurePath;
  public $featureName;

  public $ldapTestId;
  public $authorizationData;
  public $authenticationData;
  public $testData = array();

  public $sid; // current, or only, sid

  function __construct($test_id = NULL) {
    parent::__construct($test_id);
    $this->testFunctions = new LdapTestFunctionsv2();
  }

  function setUp() {
    $modules = func_get_args();
    if (isset($modules[0]) && is_array($modules[0])) {
      $modules = $modules[0];
    }
    parent::setUp($modules);
    variable_set('ldap_simpletest', 2);
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
      $this->testFunctions->configureLdapUser($ldap_user_conf_id);
    }
    if ($ldap_authentication_conf_id) {
      $this->testFunctions->configureLdapAuthentication($ldap_authentication_conf_id, $sids);
    }
    
    if ($ldap_authorization_conf_id) {
      $authorization_data = ldap_test_ldap_authorization_data();
      if (!empty($authorization_data[$ldap_authorization_conf_id])) {
        $this->testFunctions->prepConsumerConf($authorization_data[$ldap_authorization_conf_id]);
      }
    }
  }

  public function AttemptLogonNewUser($name, $goodpwd = TRUE) {

    $this->drupalLogout();

    $edit = array(
      'name' => $name,
      'pass' => ($goodpwd) ? "goodpwd" : "badpwd",
    );
    $user = user_load_by_name($name);
    if ($user) {
      user_delete($user->uid);
    }
    $this->drupalPost('user', $edit, t('Log in'));
  }
  
  /**
   * keep user entity fields function for ldap_user
   * in base class instead of user test class in case
   * module integration testing is needed
   */
  
  function ldap_user_create_user_test_entity_fields() {
    foreach ($this->ldap_user_test_entity_fields() as $field_id => $field_conf) {
      $field_info = field_info_field($field_id);
      if (!$field_info) {
        field_create_field($field_conf['field']);
        field_create_instance($field_conf['instance']);
      }
      $field_info = field_info_field($field_id);
    }
  }
  
  function ldap_user_test_entity_fields() {

    $fields = array();

    $fields['field_lname']['field'] = array(
      'field_name' => 'field_lname',
      'type' => 'text',
      'settings' => array(
        'max_length' => 64,
      )
    );

    $fields['field_lname']['instance'] = array(
      'field_name' => 'field_lname',
      'entity_type' => 'user',
      'label' => 'Last Name',
      'bundle' => 'user',
      'required' => FALSE,
      'widget' => array(
        'type' => 'text_textfield',
      ),
      'display' => array(
        'default' => array(
          'type' => 'text_default',
        ),
      ),
      'settings' => array('user_register_form' => FALSE)
    );

    $fields['field_fname']['field'] = array(
      'field_name' => 'field_fname',
      'type' => 'text',
      'settings' => array(
        'max_length' => 64,
      )
    );

    $fields['field_fname']['instance'] = array(
      'field_name' => 'field_fname',
      'entity_type' => 'user',
      'label' => 'Last Name',
      'bundle' => 'user',
      'required' => FALSE,
      'widget' => array(
        'type' => 'text_textfield',
      ),
      'display' => array(
        'default' => array(
          'type' => 'text_default',
        ),
      ),
      'settings' => array('user_register_form' => FALSE)
    );
    
    // display name for testing compound tokens
    $fields['field_display_name']['field'] = array(
      'field_name' => 'field_display_name',
      'type' => 'text',
      'settings' => array(
        'max_length' => 64,
      )
    );

    $fields['field_display_name']['instance'] = array(
      'field_name' => 'field_display_name',
      'entity_type' => 'user',
      'label' => 'Display Name',
      'bundle' => 'user',
      'required' => FALSE,
      'widget' => array(
        'type' => 'text_textfield',
      ),
      'display' => array(
        'default' => array(
          'type' => 'text_default',
        ),
      ),
      'settings' => array('user_register_form' => FALSE)
    );
    
    // display name for testing compound tokens
    $fields['field_binary_test']['field'] = array(
      'field_name' => 'field_binary_test',
      'type' => 'text',
      'size' => 'big',
    );

    $fields['field_binary_test']['instance'] = array(
      'field_name' => 'field_binary_test',
      'entity_type' => 'user',
      'label' => 'Binary Field',
      'bundle' => 'user',
      'required' => FALSE,
      'widget' => array(
        'type' => 'text_textfield',
      ),
      'display' => array(
        'default' => array(
          'type' => 'text_default',
        ),
      ),
      'settings' => array('user_register_form' => FALSE)
    );
    
    return $fields;

  }

}
