<?php

/**
 * @file
 * admin interface for general ldap api settings
 *
 */

function ldap_servers_settings() {
  
  require_once('ldap_servers.functions.inc');
  
  if (! ldap_servers_ldap_extension_loaded()) {
    drupal_set_message(t('PHP LDAP Extension is not loaded.'), "warning");
  }$https_approaches = array();
  $https_approaches[] = t('Use secure pages or secure login module to redirect to SSL (https)');
  $https_approaches[] = t('Run entire site with SSL (https)');
  $https_approaches[] = t('Remove logon block and redirect all /user page to https via webserver redirect');
  
  $form['#title'] = "Configure LDAP Preferences";
  $form['ssl'] = array('#type' => 'fieldset', '#title' => t('Require HTTPS on Credential Pages'));
  $form['ssl']['ldap_servers_require_ssl_for_credentails'] = array( 
    '#type' => 'checkbox', 
    '#title' => t('If checked, modules using LDAP will not allow credentials to
      be entered on or submitted to HTTP pages, only HTTPS. This option should be used with an 
      approache to get all logon forms to be https, such as:') . 
      theme('item_list', array('items' => $https_approaches)),
    '#default_value' => variable_get('ldap_api_require_ssl_for_credentails', 1), 
    '#default_value' => variable_get('ldap_servers_require_ssl_for_credentails', 1), 
  );

  $options = ldap_servers_encrypt_types('encrypt');

   /**  when this is changed, need to decrypt and possibly encrypt pwd in newly selected format
    *   ... thus default needs to be "No Encryption" to avoid confusion.
    */
  $form['encryption'] = array('#type' => 'fieldset', '#title' => t('Encryption'));
  $form['encryption']['ldap_servers_encryption'] = array(
    '#type' => 'select',
    '#options' => $options,
    '#title' => t('Encrypt Stored LDAP Passwords?'),
    '#default_value' => variable_get('ldap_servers_encryption', 0), 
    '#description' => t('With encryption, passwords will be stored in encrypted form.  
    This is two way encryption because the actual password needs to used to bind to LDAP.
    So it offers minimal defense if someone gets in the filespace.  It mainly helps avoid the accidental
    discovery of a clear text password.'),   
    );
  
  $form['#submit'][] = 'ldap_servers_settings_submit';
  return system_settings_form($form);
}

function ldap_servers_settings_submit($form, &$form_state) {
  if ($form_state['submitted']) {
    watchdog('ldap', 'LDAP SERVERS settings updated.');
  }
}

