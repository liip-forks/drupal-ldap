<?php

/**
 * @file
 * LDAP User editing UI.
 *
 */




/**
 * form for adding, updating, and deleting a single ldap authorization mapping
 *
 * @param <type> $form
 * @param <type> $form_state
 * @return array drupal form array
 */
function ldap_user_admin_form($form, &$form_state) {
    ldap_server_module_load_include('php', 'ldap_user', 'LdapUserConfAdmin.class');
    $ldap_user_conf = new LdapUserConfAdmin();
    //dpm('ldap_user_conf in ldap_user_admin_form'); dpm($ldap_user_conf);
    $form = $ldap_user_conf->drupalForm();
  //  dpm('ldap_user_admin_form in ldap_user_admin_form'); dpm($form); dpm($form_state);
    return $form;
}


/**
 * validate handler for the ldap_user_admin_form
 */
function ldap_user_admin_form_validate($form, &$form_state) {
 //  dpm('ldap_user_admin_form_validate'); dpm($form_state['values']);
 // ldap_server_module_load_include('php', 'ldap_user', 'LdapUserConfAdmin.class');
 // $conf = new LdapUserConfAdmin();
 // $errors = $conf->drupalFormValidate($form_state['values']);
 // foreach ($errors as $error_name => $error_text) {
 //   form_set_error($error_name, t($error_text));
 // }

}


/**
 * submit handler function for ldap_user_admin_form
 */

function ldap_user_admin_form_submit($form, &$form_state) {
//dpm('form_state values'); dpm($form_state['values']);
//  dpm('ldap_user_admin_form_submit'); dpm($form); dpm($form_state);
  ldap_server_module_load_include('php', 'ldap_user', 'LdapUserConfAdmin.class');
  $conf = new LdapUserConfAdmin();

  $conf->drupalFormSubmit($form_state['values'], $form['#storage']);  // add form data to object and save or create
//dpm('values2'); dpm($form_state['values']);
  return "eeee";
 // if ($conf->hasError == FALSE) {
 //   drupal_set_message(t('LDAP user configuration saved'), 'status');
 //   drupal_goto(LDAP_SERVERS_MENU_BASE_PATH . '/user');
 // }
//  else {
 //   form_set_error($conf->errorName, $conf->errorMsg);
 //   $conf->clearError();
 // }

}
