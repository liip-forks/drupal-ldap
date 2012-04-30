<?php

/**
 * hook_ldap_user_targets_alter().
 *
 * alter list of available drupal user targets (fields, properties, etc.)
 *   for ldap_user provisioning mapping form (admin/config/people/ldap/user)
 *
 * return array with elements of the form:
 * <field_type>.<field_name> => array(
 *   'name' => <name>,
 *   'source' => ldap attribute (even if target)
 *   'configurable' => 0 | 1,
 *   'configurable_text' => <why population cant be configured>
 *   'notes' => <user notes>
 *   'convert' => 1 | 0
 *   'direction' => LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER or LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY
 *   )
 *
 * where
 *
 * 'field_name' machine name of property, field, profile2 field, or data associative array key
 * 'field_type' is one of the following:
 *   'property' (user property such as mail, picture, timezone that is not a field)
 *   'field' (any field attached to the user such as field_user_lname)
 *   'profile2' (profile2 fields)
 *   'data' ($user->data array.  field_name will be used as key such as $user->data[<field_name>] = mapped value
 * 'name' is the user friendly name for the UI
 * 'configurable' is this configurable in the ldap_user provisioning/synching interface?
 * 'notes' explanation of why not configurable for UI
 * 'convert' convert from binary to string
 * 'direction' synch direction.  leave empty if configurable.
 *
 *
 */

function hook_ldap_user_targets_list_alter(&$available_user_targets, &$ldap_server) {

  $available_user_targets['property.name'] = array(
    'name' => 'Property: Username',
    'source' => $ldap_server->user_attr,
    'configurable' => 0,
    'configurable_text' => t('Username is derived based on ldap server configuration at !edit_link.', $tokens),
    'direction' => LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER,
    );

  $available_user_targets['property.mail'] = array(
    'name' => 'Property: Email',
    'source' => ($ldap_server->mail_template) ? $ldap_server->mail_template : $ldap_server->mail_attr,
    'configurable' => 0,
    'configurable_text' => t('Email is derived based on ldap server configuration at !edit_link.', $tokens),
    'direction' => LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER,
    );

  $available_user_targets['field.field_user_lname'] = array(
    'name' => 'Feild: Last Name',
    'configurable' => 1,
    'configurable_text' => NULL,
  );

}

/**
 * alter synch mapping provided in ldap_user module
 * before syching of ldap to drupal user fields.
 * similar behavior to hook_ldap_user_targets_alter(),
 * but only called before synching, so alterations made
 * here will not show up on admin form, but will affect
 * synch configuration.
 *
 * $synch_mapping array has same form as $available_user_targets
 * in hook_ldap_user_targets_alter()
 *
 */
function hook_ldap_user_synch_mapping_alter(&$synch_mapping) {



}
