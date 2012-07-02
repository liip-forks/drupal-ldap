<?php
// $Id$

/**
 * @file
 * simpletests for ldap authorization
 *
 */
// require_once(drupal_get_path('module', 'ldap_servers') . '/tests/LdapTestFunctions.class.php');

ldap_server_module_load_include('php', 'ldap_test', 'LdapTestFunctionsv2.class');

class LdapUserTestCasev2 extends DrupalWebTestCase {

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
    $this->testFunctions = new LdapTestFunctionsv2();
  }

  function setUp() {
    parent::setUp(array('ldap_authentication', 'ldap_authorization', 'ldap_authorization_drupal_role', 'ldap_test'));
    variable_set('ldap_simpletest', 2);
    variable_set('ldap_help_watchdog_detail', 0);
    // create field_lname, field_binary_test user fields



  foreach ($this->ldap_user_test_entity_fields() as $field_id => $field_conf) {
    $field_info = field_info_field($field_id);
    if (!$field_info) {
      field_create_field($field_conf['field']);
      field_create_instance($field_conf['instance']);
    }
  }


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



  function ldap_user_test_entity_fields() {

    $fields = array();

    $field_name = 'field_lname';
    $fields[$field_name]['field'] = array(
      'field_name' => $field_name,
      'type' => 'text',
      'entity_types' => array('user'),
      'cadinality' => 1,
      'translatable' => 0,
      );

    $fields[$field_name]['instance'] = array(
      'field_name' => $field_name,
      'entity_type' => 'user',
      'bundle' => 'user',
      'label' => 'Last Name',
      'description' => '',
      'required' => 0,
      'settings' => array(
        'text_processing' => 0,
      ),
      'widget' => array(
        'type' => 'text_textfield',
        'weight' => 0,
      ),
    );

    $field_name = 'field_fname';
    $fields[$field_name]['field'] = $fields['field_lname']['field'];
    $fields[$field_name]['field']['field_name'] = $field_name;
    $fields[$field_name]['instance'] = $fields['field_lname']['instance'];
    $fields[$field_name]['instance']['field_name'] =  $field_name;
    $fields[$field_name]['instance']['label'] =  'First Name';

    $field_name = 'field_binary_test';
    $fields[$field_name]['field'] = $fields['field_lname']['field'];
    $fields[$field_name]['field']['field_name'] = $field_name;
    $fields[$field_name]['instance'] = $fields['field_lname']['instance'];
    $fields[$field_name]['instance']['field_name'] =  $field_name;
    $fields[$field_name]['instance']['label'] =  'Binary test Field';

    return $fields;

  }

}
