<?php
// $Id: LdapAuthorizationConsumerDrupalRole.class.php,v 1.3.2.1 2011/02/08 20:05:42 johnbarclay Exp $



/**
 * @file
 * abstract class to represent an ldap_authorization consumer
 * such as drupal_role, og_group, etc.
 *
 */
require_once(drupal_get_path('module', 'ldap_authorization') . '/LdapAuthorizationConsumerAbstract.class.php');
class LdapAuthorizationConsumerDrupalRole extends LdapAuthorizationConsumerAbstract {

  public $consumerType = 'drupal_role';


  public $allowSynchBothDirections = FALSE;
  public $allowConsumerObjectCreation = TRUE;
  protected $_availableConsumerIDs;

  // default values for configurations
  public $onlyApplyToLdapAuthenticatedDefault = TRUE;
  public $useMappingsAsFilterDefault = TRUE;
  public $synchOnLogonDefault = TRUE;
  public $synchManuallyDefault = TRUE;
  public $revokeLdapProvisionedDefault = TRUE;
  public $regrantLdapProvisionedDefault = TRUE;
  public $createContainersDefault = TRUE;

 /**
   * Constructor Method
   *
   */
  function __construct($consumer_type = NULL) {
    $params = ldap_authorization_drupal_role_ldap_authorization_consumer();
    parent::__construct('drupal_role', $params['drupal_role']);
  }


  public function availableConsumerIDs($reset = FALSE) {
    if ($reset || ! is_array($this->_availableConsumerIDs)) {
      $this->_availableConsumerIDs = array_values(user_roles(TRUE));
    }
    return $this->_availableConsumerIDs;
  }

  public function createConsumers($creates) {

    //  determine existing drupal roles
    $existing_roles = $this->availableConsumerIDs();

    //  take diff to find which roles do not already exist. because
    //  sql field is case insensitive, need to loop through
    $role_to_create = NULL;
    $roles_to_create = array();
    foreach ($creates as $desired_role) {
      $create = TRUE;
      foreach ($existing_roles as $existing_role) {
        if (drupal_strtolower($existing_role) == drupal_strtolower($desired_role)) {
          $create = FALSE;
        }
      }
      if ($create) {
        $roles_to_create[] = $desired_role;
      }
    }


   // $roles_to_create = array_diff($creates, $existing_roles); // ends up attempting to create duplicate entries.

    // create each role that is needed
    foreach ($roles_to_create as $i => $role_name) {
      $role = new stdClass();
      $role->name = $role_name;
      if (! ($status = user_role_save($role))) {
        // if role is not created, remove from array to user object doesn't have it stored as granted
        watchdog('user', 'failed to create drupal role %role in ldap_authorizations module', array('%role' => $role_name));
      }
      else {
        $created[] = $role_name;
        watchdog('user', 'drupal role %role in ldap_authorizations module', array('%role' => $role_name));
      }
    }
    // return all existing user roles
    return $this->availableConsumerIDs();  // return actual roles that exist, in case of failure

  }
  public function revokeSingleAuthorization(&$user, $role_name, &$user_edit, $user_save = TRUE) {
    $user_edit['roles'] = array_diff($user->roles, array($role_name));
    if ($user_save) {
      $user = user_save($user, $user_edit);
    }
  }

  public function grantSingleAuthorization(&$user, $role_name, &$user_edit, $user_save = TRUE) {
    $user_edit['roles'] = $user->roles + array($role_name);
    if ($user_save) {
      $user = user_save($user, $user_edit);
    }
  }



  public function usersAuthorizations(&$user) {
    return array_values($user->roles);
  }

  public function authorizationUserDataSync(&$user, &$ldap_entry) {
      $users_authorizations = $this->usersAuthorizations($user);
      if (isset($user->data['ldap_authorizations'][$this->consumerType])) {
        $user_data_authorizations = $user->data['ldap_authorizations'][$this->consumerType];
      }
      else {
        $user_data_authorizations = array();
      }

    /**
     * @todo for 7.x-2.x not sure what to be doing here.  need to have synchronization configurable such
     * that a given consumer can implement know synchrozition behaviours such as sych both ways,
     * sych both ways, revoke module granted only, etc.
     */

   /**
    *

    $user_edit['data'] = $user->data;
     foreach ($user_data_authorizations as $consumer_id => $discard) {
        if (in_array($consumer_id, $actual_authorizations))
          $user_edit['data']['ldap_authorizations'][$this->consumerType][$consumer_id] = $user_data_authorizations[$consumer_id];
        }
      }


      $user = user_save($user, $user_edit);
    * **/


  }
}
