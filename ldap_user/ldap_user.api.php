<?php

/**
 * hook_ldap_user_attrs_alter().
 *
 * alter list of available drupal user targets (fields, properties, etc.)
 *   for ldap_user provisioning mapping form (admin/config/people/ldap/user)
 *
 * return array with elements of the form:
 * <field_type>.<field_name> => array(
 *   'name' => string for user friendly name for the UI,
 *   'source' => ldap attribute (even if target of synch.  this should be refactored at some point to avoid confusion)
 *   'configurable' =>
 *   'configurable_to_drupal'  0 | 1, is this configurable?
 *   'configurable_to_ldap' =>  0 | 1, is this configurable?
 *   'user_tokens' => <user_tokens>
 *   'convert' => 1 | 0 convert from binary to string for storage and comparison purposes
 *   'direction' => LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER or LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY leave empty if configurable
 *   'config_module' => module providing synching configuration.
 *   'synch_module' => module providing actual synching of attributes.
 *   'contexts' => array of context constants for when syching should take place
 *   )
 *
 * where
 * 'field_type' is one of the following:
 *   'property' (user property such as mail, picture, timezone that is not a field)
 *   'field' (any field attached to the user such as field_user_lname)
 *   'profile2' (profile2 fields)
 *   'data' ($user->data array.  field_name will be used as key such as $user->data[<field_name>] = mapped value
 * 'field_name' machine name of property, field, profile2 field, or data associative array key
 */

function hook_ldap_user_attrs_list_alter(&$available_user_attrs, &$ldap_server, $ldap_user_conf) {

 /** search for _ldap_user_attrs_list_alter for good examples
  * the general trick to implementing this hook is:
  *   make sure to specify config and synch module
  *   if its configurable by ldap_user module, don't specify convert, user_tokens, direction, or contexts.  these will be set by UI and stored values
  *   be sure to merge with existing values as ldap_user configured values will already exist in $available_user_attrs
  */

}



/**
 * Allow modules to alter the user object in the context of an ldap entry
 * during synchronization
 *
 * @param array $edit
 *   The edit array (see hook_user_insert). Make changes to this object as
 *   required.
 * @param array $ldap_user
 *   Array, the ldap user object relating to the drupal user
 * @param object $ldap_server
 *   The LdapServer object from which the ldap entry was fetched
 * @param int $synch_context
 *   The synch context, refer to the constants in ldap_user for more info
 *
*/
function hook_ldap_user_edit_user_alter(&$edit, &$ldap_user, $ldap_server, $synch_context) {
  $edit['myfield'] = $ldap_server->getAttributeValue($ldap_user, 'myfield');
}
