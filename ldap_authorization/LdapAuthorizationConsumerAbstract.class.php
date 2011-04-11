<?php
// $Id: LdapAuthorizationConsumerAbstract.class.php,v 1.2.2.1 2011/02/08 20:05:41 johnbarclay Exp $

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
  public $consumerConf; // each consumer type has cosumer conf object
  public $consumerModule;

  protected $_availableConsumerIDs;


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
   */

  public $allowConsumerObjectCreation = FALSE;


  /**
   * default consumer conf property values for this consumer type.
   * Should be overridden by child classes as appropriate
   */

  public $onlyApplyToLdapAuthenticatedDefault = TRUE;
  public $useMappingsAsFilterDefault = TRUE;
  public $synchOnLogonDefault = TRUE;
  public $synchManuallyDefault = TRUE;
  public $revokeLdapProvisionedDefault = TRUE;
  public $regrantLdapProvisioned = TRUE;
  public $createConsumersDefault = TRUE;



   /**
   * @property array $defaultableConsumerConfProperties
   * properties a consumer may provide defaults for
   * should include every item in "default mapping property values" above
   */
  public $defaultableConsumerConfProperties = array(
      'onlyApplyToLdapAuthenticated',
      'useMappingsAsFilter',
      'synchOnLogon',
      'synchManually',
      'revokeLdapProvisioned',
      'regrantLdapProvisioned',
      'createConsumers'
      );


 /**
   * Constructor Method
   *
   */
  function __construct($consumer_type, $params) {
    $this->consumerType = $consumer_type;
    $this->name = $params['consumer_name'];
    $this->namePlural= $params['consumer_name_plural'];
    $this->shortName = $params['consumer_short_name'];
    $this->shortNamePlural= $params['consumer_short_name_plural'];
    $this->description = $params['consumer_description'];
    $this->consumerModule = $params['consumer_module'];

    require_once('LdapAuthorizationConsumerConfAdmin.class.php');
    $this->consumerConf = new LdapAuthorizationConsumerConf($this);
  }


  /**
   * get list of all authorization consumer ids available to a this authorization consumer.  For
   * example for drupal_roles, this would be an array of drupal roles such
   * as array('admin', 'author', 'reviewer',... ).  For organic groups in
   * might be all the names of organic groups.
   *
   * return array in form array(id1, id2, id3,...)
   *
   */
  public function availableConsumerIDs() {
    $this->_availableConsumerIDs = array(); // calculate available ids here
    return $this->_availableConsumerIDs;
  }

  /**
   *
   * create authorization consumers
   *
   * @param array $creates an array of authorization consumer ids in form array(id1, id2, id3,...)
   *
   * return array in form array(id1, id2, id3,...) representing all
   *   existing consumer ids ($this->availableConsumerIDs())
   *
   */
  public function createConsumers($creates) {

  }

  /**
   * grant authorizations to a user
   *
   * @param object $user drupal user object
   *
   * @param $consumer_ids string or array of strings that are authorization consumer ids
   *
   * @param array $ldap_entry is ldap data from ldap entry which drupal user is mapped to
   *
   * @param boolean $user_save.  should user object be saved by authorizationGrant method
   *
   * @return array $results.  Array of form
   *   array(
   *    <authz consumer id1> => 1,
   *    <authz consumer id2> => 0,
   *   )
   *   where 1s and 0s represent success and failure to grant
   *
   */

  public function authorizationGrant(&$user, &$user_edit, $consumer_ids, &$ldap_entry, $user_save = TRUE) {
    $this->grantsAndRevokes('grant', $user, $user_edit, $consumer_ids, $ldap_entry, $user_save);
  }

  public function authorizationRevoke(&$user, &$user_edit, $consumer_ids, &$ldap_entry, $user_save = TRUE) {
    $this->grantsAndRevokes('revoke', $user, $user_edit, $consumer_ids, $ldap_entry, $user_save);
  }


  protected function grantsAndRevokes($op, &$user, &$user_edit, $consumer_ids, &$ldap_entry, $user_save) {

    if (!is_array($consumer_ids)) {
      $consumer_ids = array($consumer_ids);
    }

    $users_authorization_ids = $this->usersAuthorizations($user);

    foreach ($consumer_ids as $consumer_id) {
      if ($op == 'grant'  && !in_array($consumer_id, $users_authorization_ids)) {
        if (!in_array($consumer_id, $this->availableConsumerIDs(TRUE))) {
          if ($this->allowConsumerObjectCreation) {
            $this->createConsumers(array($consumer_id));
            if (in_array($consumer_id, $this->availableConsumerIDs(TRUE))) {
              $this->grantSingleAuthorization($user, $consumer_id);
              $user_edit['data']['ldap_authorizations'][$this->consumerType][$consumer_id] = array('date_granted' => time() );
            }
            else {
               // out of luck, failed to create consumer id
            }
          } else {
            // out of luck. can't create new consumer id.
          }
        } else {
          $this->grantSingleAuthorization($user, $consumer_id);
          $user_edit['data']['ldap_authorizations'][$this->consumerType][$consumer_id] = array('date_granted' => time() );
        }
      }
      elseif ($op == 'revoke' && isset($user_edit['data']['ldap_authorizations'][$this->consumerType][$consumer_id])) {
        unset($user_edit['data']['ldap_authorizations'][$this->consumerType][$consumer_id]);
        if (in_array($consumer_id, $users_authorization_ids)) {
          $this->revokeSingleAuthorization($user, $consumer_id);
        }
      }
    }

    if ($user_save) {
      $user = user_save($user, $user_edit);
    }

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
    // @todo for 7.x-2.x
  }





}
