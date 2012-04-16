<?php

/**
 * @file
 * functions related to REST webservices for LDAP User module
 *
 */

function ldap_user_ws($action, $drupal_user_name, $key) {

  $action = check_plain($action);
  $key = urldecode($key);
  $drupal_user_name = check_plain($drupal_user_name);
  ldap_server_module_load_include('php', 'ldap_user', 'LdapUserConfAdmin.class');
  $ldap_user_conf = new LdapUserConf();

  if (!$ldap_user_conf->wsEnabled) {
    $out = t('Webservice Not Enabled');
  }
  elseif ($key != $ldap_user_conf->wsKey) { // ldap_servers_encrypt($ldap_user_conf->wsKey, LDAP_SERVERS_ENC_TYPE_BLOWFISH)
    $out = t('Bad Webservice Key');
  }
  elseif (!in_array($_SERVER['REMOTE_ADDR'], array_values($ldap_user_conf->wsUserIps))) {
    $out = t('Request from non-allowed IP Address');
  }
  elseif (!in_array($action, $ldap_user_conf->wsActions)) {
    $out = t('Action %action not allowed', array('%action' => $action));
  }
  else {

   // $synch_context = $ldap_user_conf->wsActionsContexts[$action];
    $drupal_user = ($action == 'create') ? NULL : user_load_by_name($drupal_user_name);
  //  $ldap_user = ldap_servers_get_user_ldap_data($drupal_user_name, NULL, $synch_context);
    $user_edit = array();
    $account = array();

    switch ($action) {
      case 'create':
       $new_account = $ldap_user_conf->provisionDrupalAccount($account, $user_edit, $synch_context, $ldap_user, TRUE);
       $out = ($new_account) ? 'Created Account ' . $drupal_user_name : 'Failes to Create Account ' . $drupal_user_name ;

      break;

      case 'synch':
        $saved_account = $ldap_user_conf->synchDrupalAccount($drupal_user, $user_edit, $synch_context, $ldap_user, TRUE);
        $out = ($saved_account) ? 'Updated Account ' . $drupal_user_name : 'Failed to Update Account ' . $drupal_user_name ;
      break;

      case 'disable':
        $drupal_user->status = 0;
        $edit = array('status' => 0);
        user_save($drupal_user, array('status' => 0));
        $out = 'Disabled Account ' . $drupal_user_name;
      break;

      case 'delete':
        user_delete($drupal_user->uid);
        $out = 'Deleted Account ' . $drupal_user_name;
      break;
    }

  }

  return $out;

}
