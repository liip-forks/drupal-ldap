<?php

/**
 * hook_ldap_authentication_allowuser_results_alter().
 *
 *  allow other modules to block successful authentication of user
 *
 * return array with elements of the form:
 *
 * where
 *  $ldap_user  see README.developers.txt for structure
 *  $name is the drupal account name or proposed drupal account name if none exists yet
 *  $hook_result = TRUE for allow, FALSE for deny
 */

function hook_ldap_authentication_allowuser_results_alter($ldap_user, $name, &$hook_result) {
  
  if ($hook_result === FALSE) { // other module has denied user, should not override
    return;
  }
  elseif ($hook_result === TRUE) { // other module has allowed, maybe override
    if (mymodule_dissapproves($ldap_user, $name)) {
      $hook_result = FALSE;
    }
  }

}


