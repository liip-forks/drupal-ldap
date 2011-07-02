<?php
// $Id:  $

/**
 * @file
 * FeedsDrupalUserLdapEntryFetcher
 */
define('LDAP_FEEDS_DRUPAL_USER_FETCHER_FILTER_AUTHENTICATED', FALSE);
define('LDAP_FEEDS_DRUPAL_USER_FETCHER_FILTER_ATTR', NULL);
define('LDAP_FEEDS_DRUPAL_USER_FETCHER_ADD_USER_ATTR', TRUE);
define('LDAP_FEEDS_DRUPAL_USER_FETCHER_USER_ATTR', 'uid,name,mail,created,status,language,signature');


class FeedsDrupalUserLdapEntryFetcherResult extends FeedsFetcherResult {

  public $filterLdapAuthenticated;
  public $filterCurrentDrupalRoles;
  public $attributes;
  public $addDrupalUserAttributes;
  public $available_user_attributes;


  /**
   * Constructor.
   */
  public function __construct($source_config) {
    $this->available_user_attributes = explode(',', LDAP_FEEDS_DRUPAL_USER_FETCHER_USER_ATTR);
    $this->addDrupalUserAttributes = isset($source_config['addDrupalUserAttributes']) ? $source_config['addDrupalUserAttributes'] : LDAP_FEEDS_DRUPAL_USER_FETCHER_ADD_USER_ATTR;
    $this->filterLdapAuthenticated = isset($source_config['filters']['filterLdapAuthenticated']) ? $source_config['filters']['filterLdapAuthenticated'] : LDAP_FEEDS_DRUPAL_USER_FETCHER_FILTER_AUTHENTICATED;
    $this->attributes = (isset($source_config['attributes']) && $source_config['attributes']) ? $source_config['attributes'] : LDAP_FEEDS_DRUPAL_USER_FETCHER_FILTER_ATTR;
    $this->filterRoles = isset($source_config['filters']['filterRoles']) ? array_filter($source_config['filters']['filterRoles']) : array();

    if ($this->attributes) {
      $temp = $this->attributes;
      $this->attributes = array();
      foreach (explode(",", $temp) as $i => $element) {
        $element = trim($element, '"');
        $element = trim($element);
        $element = trim($element, '"');
        $this->attributes[] = $element;
      }
    }
    else {
      $this->attributes = array(); // if LDAP_FEEDS_FETCHER_ATTRIBUTES is empty string
    }
    parent::__construct('');
    $this->ldap_result = $this->getRaw();
    return $this->ldap_result;
  }

  /**
   * Overrides parent::getRaw();
   */
  public function getRaw() {

    // needs to loop through all users, and query ldap for each, one at a time
    // if role or authmapped filter set, can put constraints on entity load.

    $query = new EntityFieldQuery;
    $entities = $query
      ->entityCondition('entity_type', 'user')
      ->execute();
    $users = entity_load('user', array_keys($entities['user']));

    $filter_roles = (boolean)(count($this->filterRoles) > 0);

    $limit_attributes =  (boolean)(count($this->attributes) > 0);
    if ($limit_attributes) {
      $attributes_mask = array_flip($this->attributes);
    }

    foreach ($users as $uid => $user) {
      if ($filter_roles && count(array_intersect($this->filterRoles, array_keys($user->roles))) == 0) {
        continue;
      }

      if ($this->filterLdapAuthenticated && !isset($user->data['ldap_authentication'])) {
        continue;
      }

      if ($ldap_entry = ldap_servers_get_user_ldap_data($user)) {
        if ($limit_attributes) {
          $ldap_entry['attr'] = array_intersect_key($ldap_entry['attr'], $attributes_mask);
        }

        if ($this->addDrupalUserAttributes) {
          foreach ($this->available_user_attributes as $attr_name) {
            $ldap_entry['attr']['drupal.' . $attr_name]['count'] = 1;
            $ldap_entry['attr']['drupal.' . $attr_name][0] = (string)$user->{$attr_name};
          }

        }
        $results[] = $ldap_entry;
      }
    }
    return $results;
  }
}

/**
 * Fetches data via LDAP Query.
 */
class FeedsDrupalUserLdapEntryFetcher extends FeedsFetcher {

  /**
   * Implements FeedsFetcher::fetch().
   */
  public function fetch(FeedsSource $source) {
    $source_config = $source->getConfigFor($this);
    $result = new FeedsDrupalUserLdapEntryFetcherResult($source_config);
    return $result;
  }



  /**
   * Override parent::configForm().
   */
  public function sourceForm($source_config) {
    $available_user_attributes = explode(',', LDAP_FEEDS_DRUPAL_USER_FETCHER_USER_ATTR);
    $filterRoles = isset($source_config['filters']['filterRoles']) ? $source_config['filters']['filterRoles'] : array();
    $filterLdapAuthenticated = isset($source_config['filters']['filterLdapAuthenticated']) ? $source_config['filters']['filterLdapAuthenticated'] : LDAP_FEEDS_DRUPAL_USER_FETCHER_FILTER_AUTHENTICATED;
    $attributes = isset($source_config['attributes']) ? $source_config['attributes'] : LDAP_FEEDS_DRUPAL_USER_FETCHER_FILTER_ATTR;
    $addDrupalUserAttributes = isset($source_config['addDrupalUserAttributes']) ? $source_config['addDrupalUserAttributes'] : LDAP_FEEDS_DRUPAL_USER_FETCHER_ADD_USER_ATTR;

    $form = array();

    $form['filters'] = array(
      '#type' => 'fieldset',
      '#title' => t('User Filters'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#description' => t("This fetcher will return that ldap entries for all drupal users associated with the ldap.  Use
        the filters below to limit which entries are returned.")
    );

    $form['filters']['filterLdapAuthenticated'] = array(
      '#type' => 'checkbox',
      '#title' => t('Only return ldap authenticated users.'),
      '#default_value' =>  $filterLdapAuthenticated,
      '#description' => t('If unchecked, the fetcher will still only return ldap entries for users
        that have an ldap entry that can be mapped to their drupal username.'),
    );

    $form['filters']['filterRoles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Only return entries from users with the following drupal roles.'),
      '#required' => FALSE,
      '#default_value' => $filterRoles,
      '#options' => user_roles(),
      '#description' => $this->authenticationServersDescription
    );


    $form['attributes'] = array(
      '#type' => 'textfield',
      '#title' => t('Attributes'),
      '#description' => t('Attributes to return from Query.  Comma separated attribute names such as <code>"mail", "sn", "cn"</code>. Leave empty to return all attributes.'),
      '#default_value' => $attributes,
      '#maxlength' => NULL,
      '#required' => FALSE,
    );


    $form['addDrupalUserAttributes'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add drupal user attributes to ldap entry attributes.'),
      '#default_value' =>  $addDrupalUserAttributes,
      '#description' => t('Drupal user attributes such as "uid" will be added to the ldap attributes array.
        They will have attribute names prefixed with drupal such as drupal.uid, drupal.name, etc.  Available drupal attributes: ') .
        join(', ', $available_user_attributes)
        ,
    );

    return $form;
  }

  /**
   * Override parent::sourceFormValidate().
   */
  public function sourceFormValidate(&$values) {
     // user form_set_error if anything doesn't validate
     // could execute query and see if it returns anything for validation
  }





}
