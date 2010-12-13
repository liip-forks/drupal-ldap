<?php
// $Id$



/**
 * @file
 * abstract class to represent an ldap_authorization consumer
 * such as drupal_role, og_group, etc.
 * 
 */
require_once(drupal_get_path('module','ldap_authorization') .'/LdapAuthorizationConsumerAbstract.class.php');
class LdapAuthorizationConsumerDrupalRole extends LdapAuthorizationConsumerAbstract {

  /**
   * the name of the consumer object (e.g. drupal role, og group)
   *
   * @var string
   */
 // public $consumer_type = 'drupal_role';
  public $consumerType = 'drupal_role';
  public $consumerModule = 'ldap_authorization_example';

  public $name = 'drupal role';  // e.g. drupal role, og group
  public $namePlural = 'drupal roles'; // e.g. drupal roles, og groups
  public $shortName = 'role'; // e.g. role, group
  public $shortNamePlural = 'roles'; // e.g. roles, groups
  public $description = 'A Drupal Role.';

  public $allowSynchBothDirections = FALSE;
  public $allowConsumerObjectCreation = TRUE;


  // default values for configurations
  public $onlyApplyToLdapAuthenticatedDefault = TRUE;
  public $useMappingsAsFilterDefault = TRUE;
  public $synchOnLogonDefault = TRUE;
  public $synchManuallyDefault = TRUE;
  public $revokeLdapProvisionedDefault = TRUE;
  public $regrantLdapProvisionedDefault = TRUE;
  public $createContainersDefault = TRUE;


  public function getAvailableTargetIDs() {
    return  array_values(user_roles(TRUE));
  }

  public function createTargets($creates) {

    //  determine existing drupal roles
  $existing_roles = $this->getAvailableTargetIDs();

  //  take diff to find which roles do not already exist
  $roles_to_create = array_diff($creates, $existing_roles);

  // create each role that is needed
  foreach ($roles_to_create as $i => $role_name) {
    $role = new stdClass();
    $role->name = $role_name;
    if (! ($status = user_role_save($role))) {
      // if role is not created, remove from array to user object doesn't have it stored as granted
      watchdog('user', 'failed to create drupal role %role in ldap_authorizations module', array('%role' => $role_name));
    } else {
      $created[] = $role_name;
      watchdog('user', 'drupal role %role in ldap_authorizations module', array('%role' => $role_name));
    }
  }
  // return all existing user roles
  return $this->getAvailableTargetIDs();  // return actual roles that exist

  }

  public function authorizationGrant(&$user, &$user_edit, $target_ids, &$ldap_entry, $user_save = TRUE) {
     $this->roleGrantsAndRevokes('grant', $user, $user_edit, $target_ids, $ldap_entry, $user_save);


   }

  protected function roleGrantsAndRevokes($op, &$user, &$user_edit, $target_ids, &$ldap_entry, $user_save) {

    if (!is_array($target_ids)) {
       $target_ids = array($target_ids);
     }
     $change_roles = array();
     foreach ($target_ids as $role_name) {
       if (is_scalar($role_name)  && ($role_object = user_role_load_by_name($role_name))) {
          $change_roles[$role_object->rid] = $role_name;
          if ($op == 'grant') {
            $user_edit['data']['ldap_authorizations'][$this->consumerType][$role_name] = array('date_granted' => time() );
          } elseif ($op == 'revoke' && isset($user_edit['data']['ldap_authorizations'][$this->consumerType][$role_name])) {
            unset($user_edit['data']['ldap_authorizations'][$this->consumerType][$role_name]);
          }
        }
     }

    if ($op == 'grant') {
      $user_edit['roles'] = $user->roles + $change_roles;
    } elseif ($op == 'revoke') {
      $user_edit['roles'] = array_diff_assoc($user->roles, $change_roles);
    }
    if ($user_save) {
     $user = user_save($user, $user_edit);
    }


   }
  public function authorizationRevoke(&$user, &$user_edit, $target_ids, &$ldap_entry, $user_save = TRUE) {
    $this->roleGrantsAndRevokes('revoke', $user, $user_edit, $target_ids, $ldap_entry, $user_save);
   }

  public function listAuthorizations(&$user) {
   return array_values($user->roles);
  }
   
  public function authorizationUserDataSync(&$user, &$ldap_entry) {
      $actual_authorizations = $this->listAuthorizations($user);
      if (isset($user->data['ldap_authorizations'][$this->consumerType])) {
        $user_data_authorizations = $user->data['ldap_authorizations'][$this->consumerType];
      } else {
        $user_data_authorizations = array();
      }

/**
 * not sure what to be doing here.  need to have synchronization configurable
 */
   /**
    *

      $user_edit['data'] = $user->data;
     foreach ($user_data_authorizations as $target_id => $discard) {
        if (in_array($target_id, $actual_authorizations)) 
          $user_edit['data']['ldap_authorizations'][$this->consumerType][$target_id] = $user_data_authorizations[$target_id];
        }
      }


      $user = user_save($user, $user_edit);
    * **/
  
     
   }
}
