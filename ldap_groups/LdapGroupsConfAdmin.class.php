<?php

/**
 * @file
 * This class represents a ldap_group module's configuration
 *   including admin functions like save and validate
 */

ldap_servers_module_load_include('module', 'ldap_groups');
ldap_servers_module_load_include('php', 'ldap_groups', 'LdapGroupsConf.class');
    
class LdapGroupsConfAdmin extends LdapGroupsConf {

  protected function setTranslatableProperties() {

    $values['ldapProvisionSidDescription'] = t('Check ONE LDAP server configuration to
      create ldap groups entries.');

    $values['provisionGroupEntryEventsDescription'] = t('');
    
    $values['provisionGroupEntryEventsOptions'] = array(
      LDAP_GROUPS_PROV_ON_LDAP_ENTRY_CREATED => t('When LDAP Entry is created (by Drupal).'),
      LDAP_GROUPS_PROV_ON_LDAP_ENTRY_UPDATED => t('When LDAP Entry is updated (by Drupal).'),
      LDAP_GROUPS_PROV_ON_LDAP_ENTRY_DELETED => t('When LDAP Entry is deleted (by Drupal).'),
    );

    $values['provisionOptionsDescription'] = t('');
    
    $values['provisionOptionsOptions'] =  array(
      LDAP_GROUPS_PROV_NO_CREATE => t('Do not create LDAP Groups if they do not exist.'),
      LDAP_GROUPS_PROV_NO_REMOVE => t('Do not remove LDAP Group memberships even if user no longer has corresponding Drupal role.'),
      LDAP_GROUPS_PROV_DELETE_EMPTY_GROUPS => t('Remove empty LDAP groups when last member is removed.'),
    );
    
    foreach ($values as $property => $value) {
      $this->$property = $value;
    }
  }

  protected $ldapProvisionSidDescription;
  protected $ldapProvisionSidOptions = array();
 
  protected $provisionGroupEntryEventsDescription;
  protected $provisionGroupEntryEventsOptions = array();

  protected $provisionOptionsOptions = array();

  public $errorMsg = NULL;
  public $hasError = FALSE;
  public $errorName = NULL;

  public function clearError() {
    $this->hasError = FALSE;
    $this->errorMsg = NULL;
    $this->errorName = NULL;
  }

  public function save() {
    foreach ($this->saveable as $property) {
      $save[$property] = $this->{$property};
    //  dpm( "<hr/>$property. set=" . isset($save[$property]) . "is property=" . property_exists($this, $property));
      
    }
    variable_set('ldap_group_conf', $save);
    ldap_groups_conf_cache_clear();
  }

  static public function uninstall() {
    variable_del('ldap_group_conf');
  }

  public function __construct() {
    parent::__construct();
    $this->setTranslatableProperties();

    if ($servers = ldap_servers_get_servers(NULL, 'enabled')) {
      foreach ($servers as $sid => $ldap_server) {
        $enabled = ($ldap_server->status) ? 'Enabled' : 'Disabled';
        $this->provisionServerOptions[$sid] = $ldap_server->name . ' (' . $ldap_server->address . ') Status: ' . $enabled;
      }
    }
    $this->provisionServerOptions[LDAP_USER_NO_SERVER_SID] = t('None');
  }

  public function drupalForm() {

    $form['intro'] = array(
      '#type' => 'item',
      '#markup' => t('<h1>LDAP Group Settings</h1>'),
    );
    
    if (count($this->provisionServerOptions) == 0) {
      $form['intro']['#markup']  .= ldap_servers_no_enabled_servers_msg('configure LDAP Groups');
      return $form;
    }

    $form['#theme'] = 'ldap_group_conf_form';

    $form['provisioning_to_ldap_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Settings for Provisioning to LDAP Group Entries'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    
    $form['provisioning_to_ldap_settings']['ldapProvisionSid'] = array(
      '#type' => 'radios',
      '#title' => t('LDAP Servers Providing Provisioning Data'),
      '#required' => 1,
      '#default_value' => $this->ldapProvisionSid,
      '#options' => $this->provisionServerOptions,
      '#description' => $this->ldapProvisionSidDescription,
    );

    $form['provisioning_to_ldap_settings']['ldapProvisionGroupsBaseDn'] = array(
      '#type' => 'textfield',
      '#size' => 40,
      '#title' => t('Base DN groups will be provisioned into'),
      '#description' => t('e.g.  ou=groups,dc=ldap,dc=myuniversity,DC=edu'),
      '#default_value' => $this->ldapProvisionGroupsBaseDn,
      '#required' => FALSE,
    );
    
    $form['provisioning_to_ldap_settings']['ldapProvisionGroupsRoleAttribute'] = array(
      '#type' => 'textfield',
      '#size' => 40,
      '#title' => t('Attribute drupal role will be associated with'),
      '#description' => t('e.g if the group for the Drupal role "admins" is: "cn=admin,ou=groups,dc=ldap,dc=myuniversity,dc=edu", this will be "cn"'),
      '#default_value' => $this->ldapProvisionGroupsRoleAttribute,
      '#required' => FALSE,
    );
  
    $form['provisioning_to_ldap_settings']['provisionGroupEntryEvents'] = array(
      '#type' => 'checkboxes',
      '#title' => t('When should creation of LDAP group entries and members added to LDAP group entries occur?'),
      '#required' => FALSE,
      '#default_value' => $this->provisionGroupEntryEvents,
      '#options' => $this->provisionGroupEntryEventsOptions,
      '#description' => $this->provisionGroupEntryEventsDescription
    );

    $form['provisioning_to_ldap_settings']['provisionOptions'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Check the special cases you would like to enable.'),
      '#required' => 0,
      '#default_value' => $this->provisionOptions,
      '#options' => $this->provisionOptionsOptions,
      '#description' => t($this->provisionOptionsDescription),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
    );

    return $form;
  }

  /**
   * validate form, not object
   */
  public function drupalFormValidate($values)  {
    $this->populateFromDrupalForm($values);
    list($errors, $warnings) = $this->validate($values);
    return array($errors, $warnings);
  }

  /**
   * validate object, not form
   *
   * @todo validate that a user field exists, such as field.field_user_lname
   *
   */
  public function validate($values) {
    $errors = array();
    $warnings = array();
    $tokens = array();

    $has_drupal_acct_prov_servers  = (boolean)($this->ldapProvisionSid);

    return array($errors, $warnings);
  }

  protected function populateFromDrupalForm($values) {

    $this->ldapProvisionSid = $values['ldapProvisionSid'];
    $this->ldapProvisionGroupsBaseDn = $values['ldapProvisionGroupsBaseDn'];
    $this->ldapProvisionGroupsRoleAttribute = $values['ldapProvisionGroupsRoleAttribute'];
    $this->provisionOptions = $values['provisionOptions'];
    $this->provisionGroupEntryEvents = $values['provisionGroupEntryEvents'];
  }

  public function drupalFormSubmit($values) {
    $this->populateFromDrupalForm($values);

  //  try {
      $save_result = $this->save();
  //  }
  //  catch (Exception $e) {
  //    $this->errorName = 'Save Error';
  //    $this->errorMsg = t('Failed to save object.  Your form data was not saved.');
  //    $this->hasError = TRUE;
  //  }

  }
}
