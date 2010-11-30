<?php

/**
 * @file
 * abstract class to represent an ldap_authorization consumer
 * such as drupal_role, og_group, etc.  each authorization comsumer
 * will extend this class with its own class named
 * LdapAuthorizationConsumer<consumer type> such as LdapAuthorizationConsumerDrupalRole
 * 
 */

class LdapAuthorizationConsumerAbstract {

  public $name;  // e.g. drupal role, og group
  public $namePlural; // e.g. drupal roles, og groups
  public $shortName; // e.g. role, group
  public $shortNamePlural; // e.g. roles, groups
  public $description;

  /**
   * @property boolean $allowSynchBothDirections
   *
   *  Does this consumer module support synching in both directions?
   *
   */
  public $allowSynchBothDirections = FALSE;

   /**
   * @property boolean $allowConsumerObjectCreation
   *
   *  Does this consumer module support creating consumer objects
   * (drupal roles,  og groups, etc.)
   *
   **/

  public $allowConsumerObjectCreation = FALSE;


  /**
   * default mapping property values for this consumer type.
   * Should be overridden by child classes as appropriate
   */

  public $onlyApplyToLdapAuthenticatedDefault = TRUE;
  public $useMappingsAsFilterDefault = TRUE;
  public $synchOnLogonDefault = TRUE;
  public $synchManuallyDefault = TRUE;
  public $revokeLdapProvisionedDefault = TRUE;
  public $regrantLdapProvisioned = TRUE;
  public $createTargetsDefault = TRUE;

   /**
   * @property array $defaultableMappingProperties
   * mapping properties a consumer may provide defaults for
   * should include every item in "default mapping property values" above
   */
  public $defaultableMappingProperties = array(
      'onlyApplyToLdapAuthenticated',
      'useMappingsAsFilter',
      'synchOnLogon',
      'synchManually',
      'revokeLdapProvisioned',
      'regrantLdapProvisioned',
      'createTargets'
      );

  /**
   * @property array $translateableProperties
   * properties which have string values that should be passed into the token array
   * and used in drupal t() functions.
   */
  public $translateableProperties = array('name', 'namePlural', 'shortName','shortNamePlural','description');




  public function tokens() {
    $tokens = array();
    foreach (array('%','!','@') as $symbol) {
      foreach (array('name','namePlural','shortName','shortNamePlural','description') as $property) {
        $tokens[$symbol . 'consumer_'. $property] = $this->$property;
      }
    }
    return $tokens;
  }

  /**
   * get list of all authorization target ids available to a this authorization consumer.  For
   * example for drupal_roles, this would be an array of drupal roles such
   * as array('admin', 'author', 'reviewer',... ).  For organic groups in
   * might be all the names of organic groups.
   *
   * return array in form array(id1, id2, id3,...)
   *
   */
  public function getAvailableTargetIDs() {

  }


  /**
   *
   * create authorization targets
   *
   * @param array $creates an array of authorization target ids in form array(id1, id2, id3,...)
   *
   * return array in form array(id1, id2, id3,...) representing all
   *   existing target ids ($this->getAvailableTargetIDs())
   *
   */
  public function createTargets($creates) {

  }

  /**
   * grant authorizations to a user
   *
   * @param object $user drupal user object
   *
   * @param $target_ids string or array of strings that are authorization target ids
   *
   * @param array $ldap_entry is ldap data from ldap entry which drupal user is mapped to
   *
   * @param boolean $user_save.  should user object be saved by authorizationGrant method
   *
   * @return array $results.  Array of form
   *   array(
   *    <authz target id1> => 1,
   *    <authz target id2> => 0,
   *   )
   *   where 1s and 0s represent success and failure to grant
   *
   */

  public function authorizationGrant(&$user, &$user_edit, $target_ids, &$ldap_entry, $user_save = TRUE) {

      

   if (!is_array($target_ids)) {
     $target_ids = array($target_ids);
   }

   foreach ($target_ids as $target_id) {
     print $target_id;
     $results[$target_id] = $this->_authorizationGrant($user, $user_edit, $target_id, $ldap_entry);
   }
//print "<pre>results $user_save"; print_r($target_ids); print_r($results); print_r($user); print_r($user_edit); die;
   if ($user_save) {
     user_save($user, $user_edit);
   }
   return $results;
  }

    /**
   * revoke authorizations to a user
   *
   * @param object $user drupal user object
   *
   * @param $authz_id string or array of strings that are authorization target ids
   *
   * @param array $ldap_entry is ldap data from ldap entry which drupal user is mapped to
   *
   * @param boolean $user_save.  should user object be saved by authorizationGrant method
   *
   * @return array $results.  Array of form
   *   array(
   *    <authz target id1> => 1,
   *    <authz target id2> => 0,
   *   )
   *   where 1s and 0s represent success and failure to revoke
   *
   */
  
  public function authorizationRevoke(&$user, &$user_edit, $target_ids, &$ldap_entry, $user_save = TRUE) {
    if (!is_array($target_ids)) {
     $target_ids = array($target_ids);
    }

    foreach ($target_ids as $target_id) {
     $results[$target_id] = $this->_authorizationRevoke($user, $user_edit, $target_id, $ldap_entry);
    }

    if ($user_save) {
      user_save($user, $user_edit);
    }
    return $results;

   }



  /**
   * adjusts $user->data['ldap_authorizations'][<consumer_type>] data to reflect actual
   *   actual consumer authorizations and saves user object if changes made
   *
   * @param object $user drupal user object
   *
   * @param boolean $user_save.  should user object be saved by authorizationGrant method
   *
   */

  public function authorizationUserDataSync(&$user, $user_save = TRUE) {

   }





}
