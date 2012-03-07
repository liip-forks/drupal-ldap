<?php
// $Id: LdapUserConfAdmin.class.php,v 1.4.2.1 2011/02/08 06:01:00 johnbarclay Exp $

/**
 * @file
 * This classextends by LdapUserConf for configuration and other admin functions
 */

require_once('LdapUserConf.class.php');
class LdapUserConfAdmin extends LdapUserConf {

  protected function setTranslatableProperties() {

    $values['provisionServersDescription'] = t('Check all LDAP server configurations to use
      in provisioning Drupal users and their user fields.');

    $values['provisionMethodsDescription'] = t('When should provisioning/creation of Drupal accounts be done?
      Provisioning includes associating a Drupal account with an LDAP entry.');

    $values['provisionMethodsOptions'] = array(
      LDAP_USER_PROV_ON_LOGON => t('On successful user logon. '),
      LDAP_USER_PROV_ON_MANUAL_ACCT_CREATE => t('On manual creation of Drupal user accounts.'),
      LDAP_USER_PROV_ON_ALL_USER_CREATION => t('When any user account is created. (via feeds, manual creation, other provisioning modules, etc.'),
    );


    /**
    *  Drupal Account Provisioning and Syncing
    */
    $values['userConflictResolveDescription'] = t('What should be done if a local Drupal or other external
      user account already exists with the same login name.');
    $values['userConflictOptions'] = array(
      LDAP_USER_CONFLICT_LOG => t('Don\'t associate Drupal account with ldap.  Require user to use drupal password. Log the conflict'),
      LDAP_USER_CONFLICT_RESOLVE => t('Associate local account with the LDAP entry.  This option
      is useful for creating accounts and assigning roles before an ldap user authenticates.'),
      );


    $values['acctCreationOptions'] = array(
      LDAP_USER_ACCT_CREATION_LDAP_BEHAVIOR => t('Create accounts automatically for ldap authenticated users.
        Account creation settings at /admin/config/people/accounts/settings will only affect non-ldap authenticated accounts.'),
      LDAP_USER_ACCT_CREATION_USER_SETTINGS_FOR_LDAP => t('Use account creation policy
         at /admin/config/people/accounts/settings for both Drupal and LDAP Authenticated users.
         "Visitors" option automatically creates and account when they successfully LDAP authenticate.
         "Admin" and "Admin with approval" do not allow user to authenticate until the account is approved.'),
      );

      foreach ($values as $property => $default_value) {
        $this->$property = $default_value;
      }
    }

  /**
   * basic settings
   */

  protected $provisionServersDescription;
  protected $provisionServersOptions = array();

  protected $provisionMethodsDescription;
  protected $provisionMethodsOptions = array();
  public $provisionMethods = array();


  /*
   * 3. Drupal Account Provisioning and Syncing
   */
  public $userConflictResolveDescription;
  public $userConflictResolveDefault = LDAP_USER_CONFLICT_RESOLVE_DEFAULT; // LDAP_CONFLICT_RESOLVE;
  public $userConflictOptions;

  public $acctCreationDescription = '';
  public $acctCreationDefault = LDAP_USER_ACCT_CREATION_LDAP_BEHAVIOR_DEFAULT;
  public $acctCreationOptions;


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
    }
    variable_set('ldap_user_conf', $save);
  }

  static public function getSaveableProperty($property) {
    $ldap_user_conf = variable_get('ldap_user_conf', array());
  //  debug($ldap_user_conf);
    return isset($ldap_user_conf[$property]) ? $ldap_user_conf[$property] : FALSE;

  }

  static public function uninstall() {
    variable_del('ldap_user_conf');
  }

  public function __construct() {
    parent::__construct();
    $this->setTranslatableProperties();
    if ($servers = ldap_servers_get_servers(NULL, 'enabled')) {
      foreach ($servers as $sid => $ldap_server) {
        $enabled = ($ldap_server->status) ? 'Enabled' : 'Disabled';
        $this->provisionServersOptions[$sid] = $ldap_server->name . ' (' . $ldap_server->address . ') Status: ' . $enabled;
      }
    }
  }


  public function drupalForm() {

    if (count($this->provisionServersOptions) == 0) {
      $message = ldap_servers_no_enabled_servers_msg('configure LDAP User');
      $form['intro'] = array(
        '#type' => 'item',
        '#markup' => t('<h1>LDAP User Settings</h1>') . $message,
      );
      return $form;
    }

    $tokens = array();  // not sure what the tokens would be for this form?

    $form['intro'] = array(
        '#type' => 'item',
        '#markup' => t('<h1>LDAP User Settings</h1>'),
    );

    $form['basic'] = array(
      '#type' => 'fieldset',
      '#title' => t('Basic Provisioning Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['basic']['provisionServers'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Servers Providing Provisioning Data'),
      '#required' => FALSE,
      '#default_value' => $this->sids,
      '#options' => $this->provisionServersOptions,
      '#description' => $this->provisionServersDescription
    );

    $form['basic']['provisionMethods'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Allowed Provisioning Methods'),
      '#required' => FALSE,
      '#default_value' => $this->provisionMethods,
      '#options' => $this->provisionMethodsOptions,
      '#description' => $this->provisionMethodsDescription
    );

    $form['basic']['loginConflictResolve'] = array(
      '#type' => 'radios',
      '#title' => t('Existing Drupal User Account Conflict'),
      '#required' => 1,
      '#default_value' => $this->userConflictResolve,
      '#options' => $this->userConflictOptions,
      '#description' => t( $this->userConflictResolveDescription),
    );

    $form['basic']['acctCreation'] = array(
      '#type' => 'radios',
      '#title' => t('Account Creation for LDAP Authenticated Users'),
      '#required' => 1,
      '#default_value' => $this->acctCreation,
      '#options' => $this->acctCreationOptions,
      '#description' => t($this->acctCreationDescription),
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

    $errors = $this->validate();

    return $errors;
  }

/**
 * validate object, not form
 */
  public function validate() {
    $errors = array();

    $enabled_servers = ldap_servers_get_servers(NULL, 'enabled');
    return $errors;
  }

  protected function populateFromDrupalForm($values) {

    $this->sids = $values['provisionServers'];
    $this->provisionMethods = $values['provisionMethods'];
    $this->userConflictResolve  = ($values['loginConflictResolve']) ? (int)$values['loginConflictResolve'] : NULL;
    $this->acctCreation  = ($values['acctCreation']) ? (int)$values['acctCreation'] : NULL;

  }

  public function drupalFormSubmit($values) {

    $this->populateFromDrupalForm($values);
    try {
        $save_result = $this->save();
    }
    catch (Exception $e) {
      $this->errorName = 'Save Error';
      $this->errorMsg = t('Failed to save object.  Your form data was not saved.');
      $this->hasError = TRUE;
    }

  }

}
