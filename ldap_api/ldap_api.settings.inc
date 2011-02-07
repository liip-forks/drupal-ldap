<?php


function ldap_api_settings() {
  
  require_once('includes/ldap.functions.inc');
  
  if (! ldap_api_ldap_extension_loaded()) {
    drupal_set_message("PHP LDAP Extension is not loaded.", "warning");
  }
  
  $form['#title'] = "Configure LDAP API Preferences";
  $form['ssl'] = array('#type' => 'fieldset', '#title' => t('Require HTTPS on Credential Pages'));
  $form['ssl']['ldap_api_require_ssl_for_credentails'] = array( 
    '#type' => 'checkbox', 
    '#title' => t('If checked, modules using LDAP API will not allow credentials to be entered on or submitted to HTTP pages, only HTTPS.'),
    '#default_value' => variable_get('ldap_api_require_ssl_for_credentails', 1), 
  );
  
  $options = array();
  $options["none"] = "No Encryption";
  if (module_exists("aes")) {
    $options["aes"] = "AES Module Encryption";
  }
  if (module_exists("encrypt")) {
    $options["encrypt"] = "Encrypt Module Encryption";
  }
  
   /**  when this is changed, need to decrypt and possibly encrypt pwd in newly selected format
    *   ... thus default needs to be "No Encryption" to avoid confusion.
   **/
  $form['encryption'] = array('#type' => 'fieldset', '#title' => t('Encryption'));
  $form['encryption']['ldap_api_encryption'] = array(
    '#type' => 'select',
    '#options' => $options,
    '#title' => t('Encrypt Stored LDAP Passwords?'),
    '#default_value' => variable_get('ldap_api_encryption', 0), 
    '#description' => t('With encryption, passwords will be stored in encrypted form.  You need to intstall AES or Encrypt modules for this option.  After you install them, the options will appear in the select list.
    This is two way encryption because the actual password needs to used to bind to LDAP.
    So it offers minimal defense if someone gets in the filespace.  It mainly helps avoid the accidental
    discovery of a clear text password.'),   
   );
  
  $form['#submit'][] = 'ldap_api_settings_submit';
  return system_settings_form($form);
}

function ldap_api_settings_submit($form, &$form_state) {
  if ($form_state['submitted']) {
    watchdog('ldap','LDAP API settings updated.');
  }
}

