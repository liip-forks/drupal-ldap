<?php
// $Id$

/**
 * @file
 * Administrative page callbacks for the ldap_authorization module.
 */


/**
 * index of ldap authorization mappings
 *
 * @return string html table
 */

function ldap_authorizations_admin_index() {
  require_once('LdapAuthorizationMappingAdmin.class.php');
  $mappings = LdapAuthorizationMappingAdmin::getMappings();
  
  // check for authorization consumer modules
  $consumers = ldap_authorization_get_consumers( NULL, TRUE);
  if (!is_array($consumers) || count($consumers) == 0) {
    drupal_set_message('No authorization consumer modules are enabled.  Enable
      LDAP Authorization Drupal Roles, OG LDAP, or another LDAP Authorization consuming module','warning');
  }
 
   // check for ldap server configurations
  $servers = ldap_servers_get_servers(NULL, 'enabled');

  if (count($servers) == 0) {
      $message =  ldap_servers_no_enabled_servers_msg('ldap authorization can be set up'); 
      return $message;
    } else {
      return theme('ldap_authorization_admin_index',
        array('mappings' => $mappings, 'consumers' => $consumers));
    }
}



/**
 * form for adding, updating, and deleting a single ldap authorization mapping
 *
 * @param <type> $form
 * @param <type> $form_state
 * @param string $mapping_id unique identifier for authorization mapping
 * @param string $op add, update, delete
 * @param string $consumer_type drupal_roles, og_group, etc.  Only needed for adds
 * @return array drupal form array
 */
function ldap_authorization_admin_form($form, &$form_state, $mapping_id = NULL, $op = NULL, $consumer_type = NULL) {
    require_once('LdapAuthorizationMappingAdmin.class.php');
    $consumers = ldap_authorization_get_consumers($consumer_type, TRUE);
    if ($op == 'add') {
      if (!$consumer_type || (count($consumers) == 0)) {
        drupal_set_message(t('Bad URL with incorrect authorization type'), 'error'); 
      } else {
        $mapping = new LdapAuthorizationMappingAdmin(NULL, TRUE, NULL, $consumer_type, $consumers['consumer_module']);
      }
    } elseif ($op == 'update' || $op == 'delete') {
       if ($mapping = new LdapAuthorizationMappingAdmin(check_plain($mapping_id), FALSE)) {

       } else {
         drupal_set_message(t('Bad LDAP Authorization Mapping URL.  No mapping for mapping id = !id', array('!id' => $mapping_id)), 'error');
         drupal_goto(LDAP_SERVERS_MENU_BASE_PATH .'/authorization');
       }
    }

    $servers = ldap_servers_get_servers(NULL, 'enabled');
    
    if (count($servers) == 0) {
      drupal_goto('admin/config/people/ldap/authorization');
    } else {
      foreach ($servers as $sid => $server) {
        $server_options[$sid] = $server->name;
      }
      return  $mapping->drupalForm($server_options, $op);
    }




}


/**
 * validate handler for the ldap_authorization_admin_form
 */
function ldap_authorization_admin_form_validate($form, &$form_state) {
 list($consumer, $op, $op_past, $new, $values)  = _ldap_authorization_admin_parse_form($form, $form_state);

  require_once('LdapAuthorizationMappingAdmin.class.php');
  // create as new simply for validation
  $mapping = new LdapAuthorizationMappingAdmin($values['mapping_id'], TRUE, $values['sid'], $consumer->consumerType, $consumer->consumerModule);
  $errors = $mapping->drupalFormValidate($op, $values);
  foreach ($errors as $error_name => $error_text) {

    form_set_error($error_name, t($error_text));
    
  }

}


/**
 * submit handler function for ldap_authorization_admin_form
 */

function ldap_authorization_admin_form_submit($form, &$form_state) {
  list($consumer, $op, $op_past, $new, $values)  = _ldap_authorization_admin_parse_form($form, $form_state);
 
  require_once('LdapAuthorizationMappingAdmin.class.php');
  $mapping = new LdapAuthorizationMappingAdmin($values['mapping_id'], $new, $values['sid'], $consumer->consumerType, $consumer->consumerModule);
  $mapping->drupalFormSubmit($op, $values);  // add form data to object and save or create

  if ($mapping->hasError == FALSE) {
    drupal_set_message(t('LDAP Authorization %name !verb', array('!verb' => $op_past, '%name' => $values['description'])), 'status');
    drupal_goto(LDAP_SERVERS_MENU_BASE_PATH .'/authorization');
  } else {
    form_set_error($mapping->errorName, $mapping->errorMsg);
    $mapping->clearError();
  }

}

function _ldap_authorization_admin_parse_form($form, &$form_state) {
  $op = strtolower($form_state['clicked_button']['#value']);
  $values = $form_state['values'];

  if ($values['consumer_type']) {
    $consumer_type = $values['consumer_type'];
    $consumer = ldap_authorization_get_consumer_object(array('consumer_type' =>  $consumer_type));
  } else {
    return FALSE;
  }

  switch($op) {
      case 'add':
      $op_past = 'Added';
      $new = TRUE;
      break;

      case 'update':
      $op_past = 'Updated';
      $new = FALSE;

      break;

      case 'delete':
      $op_past = 'Deleted';
      $new = FALSE;
      break;
  }

  return array($consumer, $op, $op_past, $new, $form_state['values']);

}

