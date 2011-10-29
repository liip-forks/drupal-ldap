<?php

/**
 * @file
 * This classextends by LdapProfileConf for configuration and other admin functions
 */

require_once('LdapProfileConf.class.php');
class LdapProfileConfAdmin extends LdapProfileConf {

  // no need for LdapAuthenticationConf id as only one instance will exist per drupal install
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
    variable_set('ldap_profile_conf', $save);
  }

  static public function uninstall() {
    variable_del('ldap_profile_conf');
  }

  public function __construct() {
    parent::__construct();
    if ($servers = ldap_servers_get_servers(NULL, 'enabled')) {
      foreach ($servers as $sid => $ldap_server) {
        $enabled = ($ldap_server->status) ? 'Enabled' : 'Disabled';
        $this->authenticationServersOptions[$sid] = $ldap_server->name . ' (' . $ldap_server->address . ') Status: ' . $enabled;
      }
    }
  }


  public function drupalForm($accounts = array()) {
    if (count($this->authenticationServersOptions) == 0) {
      $message = ldap_servers_no_enabled_servers_msg('configure LDAP Authentication');
      $form['intro'] = array(
        '#type' => 'item',
        '#markup' => t('<h1>LDAP Profile Settings</h1>') . $message,
      );
      return $form;
    }
  
    // grabs field information for a user account  
    $fields = field_info_instances('user','user');
    $profileFields = array();
    foreach($fields as $key => $field) {
      $profileFields[$key] = $field['label'];
    }

    $form['intro'] = array(
        '#type' => 'item',
        '#markup' => t('<h1>LDAP Profile Settings</h1>'),
    );

    $form['deafultMaps'] = array(
      '#type' => 'fieldset',
      '#title' => 'Profile Fields Already Mapped to Ldap Fields',
      '#collapsible' => FALSE,
      '#collapsed' => false,
      '#tree' => true,
    );

    $user_attr = 'No Value Set';
    $mail_attr = 'No Value Set';
    $servers = ldap_servers_get_servers('','enabled');
    foreach($servers as $key => $server) {
      $user_attr = $server->user_attr;
      $mail_attr = $server->mail_attr;
    }

    $form['deafultMaps']['username'] = array(
        '#type' => 'textfield',
        '#title' => 'UserName',
        '#default_value' => $user_attr,
        '#disabled' => true,
        '#description' => 'This must be altered in the ldap server configuration page',
    );
    $form['deafultMaps']['mail'] = array(
        '#type' => 'textfield',
        '#title' => 'Email',
        '#default_value' => $mail_attr,
        '#disabled' => true,
        '#description' => 'This must be altered in the ldap server configuration page',
    );
    $form['mapping'] = array(
      '#type' => 'fieldset',
      '#title' => t('Profile Fields to Ldap Fields Mapping'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => true,
    );
    foreach($profileFields as $field => $label) {
      $mapping = $this->mapping;
      $derivedMapping = $this->derivedMapping;
      
      if(!empty($mapping) && array_key_exists($field,$mapping)) $default = $mapping[$field];
      else $default = '';
      $form['mapping'][$field] = array(
         '#type' => 'fieldset',
         '#title' => $label.t(' Profile Field to LDAP Field Mapping'),
         '#collapsible' => TRUE,
         '#collapsed' => FALSE,
      );
      $form['mapping'][$field]['ldap'] = array(
        '#type' => 'textfield',
        '#title' => $label,
        '#default_value' => $default,
      );
      if(!empty($derivedMapping) && array_key_exists($field,$derivedMapping) && array_key_exists('derive',$derivedMapping[$field])) $default = $derivedMapping[$field]['derive'];
      else $default = '';
      $form['mapping'][$field]['derive'] = array(
         '#type' => 'checkbox',
         '#title' => t('Derive from DN Search'),
         '#default_value' =>  $default,
       );
      if(!empty($derivedMapping) && array_key_exists($field,$derivedMapping) && array_key_exists('derive_value',$derivedMapping[$field])) $default = $derivedMapping[$field]['derive_value'];
      else $default = '';
      $form['mapping'][$field]['derive_value'] = array(
         '#type' => 'textfield',
         '#title' => t('LDAP Field to Derive from'),
         '#default_value' =>  $default,
       );
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Update',
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

    return $errors;
  }

  protected function populateFromDrupalForm($values) {
    $this->ldap_fields = array();
    $this->mapping = array();
    foreach($values['deafultMaps'] as $field => $value) {
      if($value != '') {    
        //store value in lower case to fix a ldap searching bug
        $l_value = strtolower($value);
        $this->mapping[$field] = $l_value;
        // don't add duplicates & ignore case
        if(!in_array($l_value, array_map('strtolower', $this->ldap_fields))) {
          $this->ldap_fields[] = $l_value;
        }
      }
    }
    foreach(array_keys($values['mapping']) as $field) {
      if($values['mapping'][$field]['ldap'] != '') {    
        //store value in lower case to fix a ldap searching bug
        $l_value = strtolower($values['mapping'][$field]['ldap']);
        $this->mapping[$field] = $l_value;
        if((bool)($values['mapping'][$field]['derive']) && $values['mapping'][$field]['derive_value'] != '') {
            $l_value = strtolower($values['mapping'][$field]['derive_value']);
            $this->derivedMapping[$field]['derive'] = TRUE;
            $this->derivedMapping[$field]['derive_value'] = $l_value;
        } else {
            $this->derivedMapping[$field]['derive'] = FALSE;
            $this->derivedMapping[$field]['derive_value'] = '';
        }
      }
    }
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
