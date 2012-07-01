<?php
// $Id: LdapUserConfAdmin.class.php,v 1.4.2.1 2011/02/08 06:01:00 johnbarclay Exp $

/**
 * @file
 * This classextends by LdapUserConf for configuration and other admin functions
 */

module_load_include('php', 'ldap_user', 'LdapUserConf.class');

class LdapUserConfAdmin extends LdapUserConf {

  protected function setTranslatableProperties() {

    $values['provisionServersDescription'] = t('Check all LDAP server configurations to use
      in provisioning Drupal users and their user fields.');
    $values['provisionTargetServersDescription'] = t('Check all LDAP server configurations to create ldap entries on.');

    $values['drupalAccountProvisionEventsDescription'] = t('"LDAP Associated" Drupal user accounts (1) have
      data mapping the account to an LDAP entry and (2) can leverage LDAP module functionality
      such as authorization, profile field synching, etc.');

    $values['drupalAccountProvisionEventsOptions'] = array(
      LDAP_USER_PROV_ON_LOGON => t('On successful authentication with LDAP
        credentials and no existing Drupal account, create "LDAP Associated" Drupal account.  (Requires LDAP Authentication module).'),
      LDAP_USER_PROV_ON_MANUAL_ACCT_CREATE => t('On manual creation of Drupal
        user accounts, make account "LDAP Associated" if corresponding LDAP entry exists.
        Requires a server with binding method of "Service Account Bind" or "Anonymous Bind".'),
      LDAP_USER_PROV_ON_ALL_USER_CREATION => t('Anytime a Drupal user account
        is created, make account "LDAP Associated" if corresponding LDAP entry exists.
        (includes manual creation, feeds module, Shib, CAS, other provisioning modules, etc).
        Requires a server with binding method of "Service Account Bind" or "Anonymous Bind".'),
      LDAP_USER_PROV_CANCEL_DRUPAL_USER_ON_LDAP_ENTRY_MISSING => t('Anytime the corresponding
        LDAP entry for a user is gone, disable the Drupal Account.'),
      LDAP_USER_PROV_DELETE_DRUPAL_USER_ON_LDAP_ENTRY_MISSING => t('Anytime the corresponding
        LDAP entry for a user is gone, delete the Drupal Account.'),
    );

    $values['ldapEntryProvisionEventsDescription'] = t('When should a corresponding LDAP Entry
      be created for a Drupal User?');

    $values['ldapEntryProvisionEventsOptions'] = array(
      LDAP_USER_LDAP_ENTRY_CREATION_ON_CREATE => t('When a Drupal Account has a status of approved.
        This could be when an account is initially created, when it is approved, or when confirmation
        via email sets enables an account.'),
      LDAP_USER_LDAP_ENTRY_DELETION_ON_DELETE => t('When a Drupal Account that has a corresponding LDAP
        entry is deleted, delete the corresponding LDAP entry.'),
    );


    /**
    *  Drupal Account Provisioning and Synching
    */
    $values['userConflictResolveDescription'] = t('What should be done if a local Drupal or other external
      user account already exists with the same login name.');
    $values['userConflictOptions'] = array(
      LDAP_USER_CONFLICT_LOG => t('Don\'t associate Drupal account with LDAP.  Require user to use Drupal password. Log the conflict'),
      LDAP_USER_CONFLICT_RESOLVE => t('Associate Drupal account with the LDAP entry.  This option
      is useful for creating accounts and assigning roles before an LDAP user authenticates.'),
      );

    $values['acctCreationOptions'] = array(
      LDAP_USER_ACCT_CREATION_LDAP_BEHAVIOR => t('Account creation settings at
        /admin/config/people/accounts/settings do not affect "LDAP Associated" Drupal accounts.'),
      LDAP_USER_ACCT_CREATION_USER_SETTINGS_FOR_LDAP => t('Account creation policy
         at /admin/config/people/accounts/settings applies to both Drupal and LDAP Authenticated users.
         "Visitors" option automatically creates and account when they successfully LDAP authenticate.
         "Admin" and "Admin with approval" do not allow user to authenticate until the account is approved.'),

      );

      foreach ($values as $property => $default_value) {
        $this->$property = $default_value;
      }
    }

  /**
   * basic settings
   */

  protected $provisionServersDescription;
  protected $provisionServersOptions = array();
  protected $provisionTargetServersOptions = array();

  protected $drupalAccountProvisionEventsDescription;
  protected $drupalAccountProvisionEventsOptions = array();

  protected $ldapEntryProvisionEventsDescription;
  protected $ldapEntryProvisionEventsOptions = array();

  protected $synchFormRow = 0;

  /*
   * 3. Drupal Account Provisioning and Syncing
   */
  public $userConflictResolveDescription;
  public $userConflictResolveDefault = LDAP_USER_CONFLICT_RESOLVE_DEFAULT; // LDAP_CONFLICT_RESOLVE;
  public $userConflictOptions;

  public $acctCreationDescription = '';
  public $acctCreationDefault = LDAP_USER_ACCT_CREATION_LDAP_BEHAVIOR_DEFAULT;
  public $acctCreationOptions;


  public $errorMsg = NULL;
  public $hasError = FALSE;
  public $errorName = NULL;

  public function clearError() {
    $this->hasError = FALSE;
    $this->errorMsg = NULL;
    $this->errorName = NULL;
  }

  public function save() {
    foreach ($this->saveable as $property) {
      $save[$property] = $this->{$property};
    }
    variable_set('ldap_user_conf', $save);
  }

  static public function getSaveableProperty($property) {
    $ldap_user_conf = variable_get('ldap_user_conf', array());
    return isset($ldap_user_conf[$property]) ? $ldap_user_conf[$property] : FALSE;
  }

  static public function uninstall() {
    variable_del('ldap_user_conf');
  }

  public function __construct() {
    parent::__construct();
    $this->setTranslatableProperties();

    if ($servers = ldap_servers_get_servers(NULL, 'enabled')) {
      foreach ($servers as $sid => $ldap_server) {
        $enabled = ($ldap_server->status) ? 'Enabled' : 'Disabled';
        $this->provisionServersOptions[$sid] = $ldap_server->name . ' (' . $ldap_server->address . ') Status: ' . $enabled;
        $this->provisionTargetServersOptions[$sid] = $ldap_server->name . ' (' . $ldap_server->address . ') Status: ' . $enabled;
      }
    }
  }

  public function drupalForm() {
   // // temp_out dpm('this in drupal form'); // temp_out dpm($this->ldapUserSynchMappings['uiuc_ad']);
    if (count($this->provisionServersOptions) == 0) {
      $message = ldap_servers_no_enabled_servers_msg('configure LDAP User');
      $form['intro'] = array(
        '#type' => 'item',
        '#markup' => t('<h1>LDAP User Settings</h1>') . $message,
      );
      return $form;
    }
    $form['#storage'] = array();
    $form['#theme'] = 'ldap_user_conf_form';

    $form['intro'] = array(
      '#type' => 'item',
      '#markup' => t('<h1>LDAP User Settings</h1>'),
    );

    $form['basic_to_drupal'] = array(
      '#type' => 'fieldset',
      '#title' => t('Basic Provisioning to Drupal Account Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['basic_to_drupal']['provisionServers'] = array(
      '#type' => 'checkboxes',
      '#title' => t('LDAP Servers Providing Provisioning Data'),
      '#required' => FALSE,
      '#default_value' => $this->sids,
      '#options' => $this->provisionServersOptions,
      '#description' => $this->provisionServersDescription
    );

    $form['basic_to_drupal']['drupalAcctProvisionEvents'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Drupal Account Provisioning Options'),
      '#required' => FALSE,
      '#default_value' => $this->drupalAcctProvisionEvents,
      '#options' => $this->drupalAccountProvisionEventsOptions,
      '#description' => $this->drupalAccountProvisionEventsDescription
    );



    $form['basic_to_drupal']['userConflictResolve'] = array(
      '#type' => 'radios',
      '#title' => t('Existing Drupal User Account Conflict'),
      '#required' => 1,
      '#default_value' => $this->userConflictResolve,
      '#options' => $this->userConflictOptions,
      '#description' => t( $this->userConflictResolveDescription),
    );

    $form['basic_to_drupal']['acctCreation'] = array(
      '#type' => 'radios',
      '#title' => t('Application of Drupal Account settings to LDAP Authenticated Users'),
      '#required' => 1,
      '#default_value' => $this->acctCreation,
      '#options' => $this->acctCreationOptions,
      '#description' => t($this->acctCreationDescription),
    );

    $form['basic_to_ldap'] = array(
      '#type' => 'fieldset',
      '#title' => t('Basic Provisioning to LDAP Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => !(count(array_filter($this->provisionTargetServers))),
    );

    $form['basic_to_ldap']['provisionTargetServers'] = array(
      '#type' => 'checkboxes',
      '#title' => t('LDAP Servers to Provision LDAP Entries on'),
      '#required' => FALSE,
      '#default_value' => $this->provisionTargetServers,
      '#options' => $this->provisionTargetServersOptions,
      '#description' => $this->provisionTargetServersDescription,
    );

    $form['basic_to_ldap']['ldapEntryProvisionEvents'] = array(
      '#type' => 'checkboxes',
      '#title' => t('LDAP Entry Provisioning Options'),
      '#required' => FALSE,
      '#default_value' => $this->ldapEntryProvisionEvents,
      '#options' => $this->ldapEntryProvisionEventsOptions,
      '#description' => $this->ldapEntryProvisionEventsDescription
    );

    $form['ws'] = array(
      '#type' => 'fieldset',
      '#title' => t('REST Webservice for Provisioning and Synching.'),
      '#collapsible' => TRUE,
      '#collapsed' => !$this->wsEnabled,
      '#description' => t('Once configured, this webservice can be used to trigger creation, synching, deletion, etc of an LDAP associated Drupal account.'),
    );

    $form['ws']['wsEnabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable REST Webservice'),
      '#required' => FALSE,
      '#default_value' => $this->wsEnabled,
    );


    $form['ws']['wsActions'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Actions Allowed via REST webservice'),
      '#required' => FALSE,
      '#default_value' => $this->wsActions,
      '#options' => $this->wsActionsOptions,
      '#states' => array(
        'visible' => array(   // action to take.
          ':input[name="wsEnabled"]' => array('checked' => TRUE),
        ),
      ),
    );
/**
    $form['ws']['wsUserId'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of LDAP Attribute passed to identify user. e.g. DN, CN, etc.'),
      '#required' => FALSE,
      '#default_value' => $this->wsUserId,
      '#description' => t('This will be used to find user in LDAP so must be unique.'),
      '#states' => array(
        'visible' => array(   // action to take.
          ':input[name="wsEnabled"]' => array('checked' => TRUE),
        ),
      ),
    );
**/
    $form['ws']['wsUserIps'] = array(
      '#type' => 'textarea',
      '#title' => t('Allowed IP Addresses to request webservice.'),
      '#required' => FALSE,
      '#default_value' => join("\n", $this->wsUserIps),
      '#description' => t('One Per Line. The current server address is LOCAL_ADDR and the client ip requesting this page is REMOTE_ADDR .', $_SERVER),
      '#cols' => 20,
      '#rows' => 2,
      '#states' => array(
        'visible' => array(   // action to take.
          ':input[name="wsEnabled"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['ws']['wsKey'] = array(
      '#type' => 'textfield',
      '#title' => t('Key for webservice'),
      '#required' => FALSE,
      '#default_value' => $this->wsKey,
      '#description' => t('Any random string of characters.  Once submitted REST URLs will be generated below.'),
      '#states' => array(
        'visible' => array(   // action to take.
          ':input[name="wsEnabled"]' => array('checked' => TRUE),
        ),
      ),
    );

    $urls = '';
    if ($this->wsEnabled) {
      if (!$this->wsKey) {
        $urls = t('URLs are not available until a key is create a key and urls will be generated');
      }
      elseif (count($this->wsActionsOptions) == 0) {
        $urls = t('URLs are not available until at least one action is enabled.');
      }
      else {
        $key = $this->wsKey; // ldap_servers_encrypt($this->wsKey, LDAP_SERVERS_ENC_TYPE_BLOWFISH);
        $urls = array();

        $enabled_actions = array_filter(array_values($this->wsActions));
        foreach ($this->wsActionsOptions as $action => $description) {
          $disabled = (in_array($action, $enabled_actions)) ? t('ENABLED') :  t('DISABLED');
          $urls[] = $disabled .": $action url: " . join('/', array(LDAP_USER_WS_USER_PATH, $action, '[drupal username]', urlencode($key)));
        }
        $urls = theme('item_list', array('items' => $urls, 'title' => 'REST URLs', 'type' => 'ul', 'attributes' => array()))
         . '<p>' . t('Where %token is replaced by actual users LDAP %attribute', array('%token' => '[drupal username]', '%attribute' => 'drupal username')) .
         '</p>';

        ;
      }
    }
    $form['ws']['wsURLs'] = array(
      '#type' => 'markup',
      '#markup' => '<h2>' . t('Webservice URLs') . '</h2>' . $urls,
      '#states' => array(
        'visible' => array(   // action to take.
          ':input[name="wsEnabled"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['server_mapping_preamble'] = array(
      '#type' => 'markup',
      '#markup' => t('
The relationship between a Drupal user and an LDAP entry is defined within the LDAP server configurations.


The mappings below are for user fields, properties, and profile2 data that are not automatically mapped elsewhere.
Mappings such as username or email address that are configured elsewhere are shown at the top for clarity.
When more than one ldap server is enabled for provisioning data (or simply more than one configuration for the same ldap server),
mappings need to be setup for each server.  If no tables are listed below, you have not enabled any provisioning servers at
the top of this form.
'),

    );
   // debug("this->sids"); debug($this->sids);
    foreach($this->sids as $sid => $enabled_for_ldap_user) {
      $ldap_server = ldap_servers_get_servers($sid, NULL, TRUE);

      $form['mappings__'. $sid] = array(
        '#type' => 'fieldset',
        '#title' =>  t('%ldap_server LDAP Server Mappings', array('%ldap_server' => $ldap_server->name)),
        '#collapsible' => TRUE,
        '#collapsed' => !$enabled_for_ldap_user,
        '#description' => t('If provisioning or synching to or from ldap is enabled, this table is used to control mapping of attributes.'),
        '#states' => array(
          'invisible' => array(   // action to take.
            ':input[name="provisionServers['. $sid . ']"]' => array('checked' => FALSE),
            ':input[name="provisionTargetServers['. $sid . ']"]' => array('checked' => FALSE),
          ),
        ),
      );
/**
 *           'visible' => array(   // action to take.
            ':input[name=provisionServers[uiuc_ad]]' => array('checked' => FALSE),
          ),
          **/

      $form['mappings__'. $sid]['table__'. $sid] = array(
        '#type' => 'markup',
        '#markup' => '[replace_with_table__' . $sid. ']',
      );

      $this->addServerMappingFields($ldap_server, $form);

    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
    );

  return $form;
}



/**
 * validate form, not object
 */
  public function drupalFormValidate($values, $storage)  {
    $this->populateFromDrupalForm($values, $storage);
    $errors = $this->validate();
    return $errors;
  }

/**
 * validate object, not form
 */
  public function validate() {
    $errors = array();

    if (isset($this->ldapUserSynchMappings)) {
      $enabled_servers = ldap_servers_get_servers(NULL, 'enabled');
      $available_user_attrs = array();
      foreach ($enabled_servers as $sid => $ldap_server) {
        $available_user_attrs[$sid] = array();
        drupal_alter('ldap_user_attrs_list', $available_user_attrs[$sid], $ldap_server, $this);
      //  foreach ($this->synchMapping[$sid] as $user_attr => $conf) {
      // don't validate on delete
              // neither of these should come up without altered form, but could check for:
              // 1. is field configurable ?
              // 2. is $user_attr and $ldap_attr both populated
      //  }
      }
    }
    return $errors;
  }

  protected function populateFromDrupalForm($values, $storage) {
    //// temp_out dpm('populateFromDrupalForm'); // temp_out dpm($values); // temp_out dpm($storage);
    $this->sids = $values['provisionServers'];
    $this->provisionTargetServers = $values['provisionTargetServers'];
    $this->drupalAcctProvisionEvents = $values['drupalAcctProvisionEvents'];
    $this->ldapEntryProvisionEvents = $values['ldapEntryProvisionEvents'];
    $this->userConflictResolve  = ($values['userConflictResolve']) ? (int)$values['userConflictResolve'] : NULL;
    $this->acctCreation  = ($values['acctCreation']) ? (int)$values['acctCreation'] : NULL;
    $this->wsKey  = ($values['wsKey']) ? $values['wsKey'] : NULL;
   // $this->wsUserId  = ($values['wsUserId']) ? $values['wsUserId'] : NULL;
    $this->wsUserIps  = ($values['wsUserIps']) ? explode("\n", $values['wsUserIps']) : array();
    foreach ($this->wsUserIps as $i => $ip) {
      $this->wsUserIps[$i] = trim($ip);
    }

    $this->wsEnabled  = ($values['wsEnabled']) ? (int)$values['wsEnabled'] : 0;
    $this->wsActions = ($values['wsActions']) ? $values['wsActions'] : array();
    $this->ldapUserSynchMappings = $this->synchMappingsFromForm($values, $storage);
  //  dpm('populateFromDrupalForm this->ldapUserSynchMappings'); dpm($this->ldapUserSynchMappings);

  }



/**
 * $values input names in form:

    sm__configurable__N, sm__remove__N, sm__ldap_attr__N, sm__convert__N, sm__direction__N, sm__user_attr__N, sm__user_tokens__N
    sm__1__N, sm__2__N, sm__3__N, sm__4__N
    ...where N is the row in the configuration form

   where additiond data is in $form['#storage']['synch_mapping_fields'][N]
    $form['#storage']['synch_mapping_fields'][N] = array(
      'sid' => $sid,
      'action' => 'add',
    );
**/
  private function synchMappingsFromForm($values, $storage) {
    $mappings = array();
 //   dpm('synchMappingsFromForm'); dpm($values);
    foreach ($values as $field => $value) {

      $parts = explode('__', $field);
      // since synch mapping fields are in n-tuples, process entire n-tuple at once
      if (count($parts) != 3 || $parts[0] !== 'sm' || $parts[1] != 'configurable_to_drupal') {
        continue;
      }

      list($discard, $column_name, $i) = $parts;
      $action = $storage['synch_mapping_fields'][$i]['action'];
      $sid = $storage['synch_mapping_fields'][$i]['sid'];

      $row_mappings = array();
      foreach (array('remove', 'configurable_to_drupal', 'configurable_to_ldap', 'convert', 'direction', 'ldap_attr', 'user_attr', 'user_tokens') as $column_name) {
        $input_name = join('__', array('sm',$column_name, $i));
        $row_mappings[$column_name] = isset($values[$input_name]) ? $values[$input_name] : NULL;
      }
    //  // temp_out dpm("$field row mappings"); // temp_out dpm($row_mappings);
      if ($row_mappings['remove'] || $row_mappings['direction'] == LDAP_USER_SYNCH_DIRECTION_NONE) {
        continue;
      }

      $key = ($row_mappings['direction'] == LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER) ? $row_mappings['user_attr'] : $row_mappings['ldap_attr'];
     // dpm("key=$key");
      if ($row_mappings['configurable_to_drupal'] && $row_mappings['ldap_attr'] && $row_mappings['user_attr']) {
        $mappings[$sid][$key] = array(
          'sid' => $sid,
          'ldap_attr' => $row_mappings['ldap_attr'],
          'user_attr' => $row_mappings['user_attr'],
          'convert' => $row_mappings['convert'],
          'direction' => $row_mappings['direction'],
          'user_tokens' => $row_mappings['user_tokens'],
          'config_module' => 'ldap_user',
          'synch_module' => 'ldap_user',
          'enabled' => 1,
          );
        foreach ($this->synchTypes as $synch_context => $synch_context_name) {
          $input_name = join('__', array('sm', $synch_context, $i));
         // dpm($input_name);

          if (isset($values[$input_name]) && $values[$input_name]) {
            $mappings[$sid][$key]['contexts'][] = $synch_context;
          }
        }
     //   dpm('final contexts'); dpm($mappings[$sid][$key]['contexts']);
      }
     //  // temp_out dpm("final mappings"); // temp_out dpm($mappings[$sid][$key]);
    }
   // debug('mappings in form submit'); debug($mappings);
    return $mappings;
  }

  public function drupalFormSubmit($values, $storage) {
   //  // temp_out dpm('drupalFormSubmit'); // temp_out dpm($values);
    $this->populateFromDrupalForm($values, $storage);
  //  // temp_out dpm('populateFromDrupalForm'); // temp_out dpm($this);
    try {
      $save_result = $this->save();
    }
    catch (Exception $e) {
      $this->errorName = 'Save Error';
      $this->errorMsg = t('Failed to save object.  Your form data was not saved.');
      $this->hasError = TRUE;
    }

  }

  /**
   * add mapping form section to mapping form array
   *
   * @param object $ldap_server
   * @param drupal form array $form
   *
   * @return by reference to $form
   */

  private function addServerMappingFields($ldap_server, &$form) {

    if (!is_array($this->synchMapping) || count($this->synchMapping) == 0) {
      return;
    }

    $target_options = array('0' => 'Select Target');
  //  // temp_out dpm('addServerMappingFields:synchMapping['. $ldap_server->sid . ']'); // temp_out dpm($this->synchMapping[$ldap_server->sid]);
    foreach ($this->synchMapping[$ldap_server->sid] as $target_id => $mapping) {
      if (isset($mapping['exclude_from_mapping_ui']) && $mapping['exclude_from_mapping_ui']) {
        continue;
      }
      if (
        (isset($mapping['configurable_to_drupal']) && $mapping['configurable_to_drupal'])
        ||
        (isset($mapping['configurable_to_ldap']) && $mapping['configurable_to_ldap'])
        ){
        $target_options[$target_id] = substr($mapping['name'], 0, 25);
      }
    }
     $target_options['user_tokens'] = '-- user tokens --';

    $this->synchFormRow = 0;

    // 1. non configurable mapping rows
    foreach ($this->synchMapping[$ldap_server->sid] as $target_id => $mapping) {
      //

      if (isset($mapping['exclude_from_mapping_ui']) && $mapping['exclude_from_mapping_ui']) {
        continue;
      }
      if ( !$this->isMappingConfigurable($mapping, 'ldap_user')) {
      //  dpm("non configurable addSynchFormRow - $target_id");  dpm($mapping);
        $this->addSynchFormRow($form, 'nonconfigurable', $mapping, $target_options, $ldap_server);
      }
      else {
      //  dpm("configurable addSynchFormRow - $target_id");  dpm($mapping);
      }
    }



    // 2. existing configurable mappings rows
   // dpm('this->ldapUserSynchMappings');  dpm($this->ldapUserSynchMappings);
  //  dpm('this->synchMapping');  dpm($this->synchMapping);
    if (isset($this->ldapUserSynchMappings[$ldap_server->sid])) {
      foreach ($this->ldapUserSynchMappings[$ldap_server->sid] as $target_attr_name => $mapping) {  // key could be ldap attribute name or user attribute name
     //   dpm("target_attr_name=$target_attr_name");  dpm($mapping);
        if (isset($mapping['enabled']) && $mapping['enabled'] && $this->isMappingConfigurable($this->synchMapping[$ldap_server->sid][$target_attr_name], 'ldap_user')) {
     //   dpm("addSynchFormRow - $target_id");  dpm($mapping);
          $this->addSynchFormRow($form, 'update', $mapping, $target_options, $ldap_server);
        }
      }
      // temp_out dpm('form'); // temp_out dpm($form);
    }


    // 3. leave 4 rows for adding more mappings
    for ($i=0; $i<4; $i++) {
      $this->addSynchFormRow($form, 'add', NULL, $target_options, $ldap_server);
    }

  }


    private function isMappingConfigurable($mapping = NULL, $module = 'ldap_user') {
      $configurable = (
        (
          (!isset($mapping['configurable_to_drupal']) && !isset($mapping['configurable_to_ldap'])) ||
          (isset($mapping['configurable_to_drupal']) && $mapping['configurable_to_drupal']) ||
          (isset($mapping['configurable_to_ldap']) && $mapping['configurable_to_ldap'])
        )
        &&
        (
          !isset($mapping['config_module']) ||
          (isset($mapping['config_module']) && $mapping['config_module'] == $module)
        )
      );
   //   dpm($mapping); dpm($module); dpm("result = $configurable");
      return $configurable;
    }

  /**
   * add mapping form row
   *
   * @param drupal form array $form
   * @param string $action is 'add', 'update', or 'nonconfigurable'
   * @param array $mapping is current setting for updates or nonconfigurable items
   * @param array $target_options of drupal user target options
   * @param string $user_attr is current drupal user field/property for updates or nonconfigurable items
   * @param object $ldap_server
   *
   * @return by reference to $form
   */
  private function addSynchFormRow(&$form, $action, $mapping, $target_options, $ldap_server) {

    $row = $this->synchFormRow;
    $form['#storage']['synch_mapping_fields'][$row] = array(
      'sid' => $ldap_server->sid,
      'action' => $action,
    );

    $id = 'sm__configurable_to_drupal__' . $row;
    $form[$id] = array(
      '#id' => $id,
      '#type' => 'hidden',
      '#default_value' => ($action != 'nonconfigurable'),
    );

    $id = 'sm__remove__' . $row;
    $form[$id] = array(
      '#id' => $id, '#row' => $row, '#col' => 0,
      '#type' => 'checkbox',
      '#default_value' => NULL,
      '#disabled' => ($action == 'add' || $action == 'nonconfigurable'),
    );

    $id = 'sm__ldap_attr__'. $row;
    if ($action == 'nonconfigurable') {
      $form[$id] = array(
        '#id' => $id, '#row' => $row, '#col' => 1,
        '#type' => 'item',
        '#markup' => isset($mapping['source']) ? $mapping['source'] : '?',
      );
    }
    else {
      $form[$id] = array(
        '#id' => $id, '#row' => $row, '#col' => 1,
        '#type' => 'textfield',
        '#default_value' => isset($mapping['ldap_attr']) ? $mapping['ldap_attr'] : '',
        '#size' => 20,
        '#maxlength' => 255,
      );
    }

    $id = 'sm__convert__' . $row;
    $form[$id] = array(
      '#id' => $id, '#row' => $row, '#col' => 2,
      '#type' => 'checkbox',
      '#default_value' =>  isset($mapping['convert']) ? $mapping['convert'] : '',
      '#disabled' => ($action == 'nonconfigurable'),
    );

    $direction_options = array(
       0 => '-- select --',
       LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER => 'to Drupal user -->',
       LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY => '<-- to LDAP entry',
       LDAP_USER_SYNCH_DIRECTION_NONE => 'no synch',
     );
   // $css_class =

    $direction_id = isset($mapping['direction']) ? $mapping['direction'] : 0;
    $css_class = 'ldap-user-synch-dir-' . $direction_id;
    $direction_input_id = 'sm__direction__'. $row;
    if ($action == 'nonconfigurable') {
      $form[$direction_input_id] = array(
        '#id' => $direction_input_id,
        '#row' => $row,
        '#col' => 3,
        '#type' => 'item',
        '#markup' => '<span class="'. $css_class . '">' . $direction_options[$direction_id] . '</span>',
      );
    }
    else {
      $form[$direction_input_id] = array(
        '#id' => $direction_input_id,
        '#row' => $row,
        '#col' => 3,
        '#type' => 'select',
        '#default_value' => $direction_id,
        '#options' => $direction_options,
      );
    }

    $user_attr_input_id = 'sm__user_attr__'. $row;
    if ($action == 'nonconfigurable') {
      $form[$user_attr_input_id] = array(
        '#id' => $user_attr_input_id,
        '#row' => $row,
        '#col' => 4,
        '#type' => 'item',
        '#markup' => isset($mapping['name']) ? $mapping['name'] : '?',
      );
    }
    else {
      $form[$user_attr_input_id] = array(
        '#id' => $user_attr_input_id,
        '#row' => $row,
        '#col' => 4,
        '#type' => 'select',
        '#default_value' => isset($mapping['user_attr']) ? $mapping['user_attr'] : '',
        '#options' => $target_options,
      );
    }

    $col = 4;
    foreach ($this->synchTypes as $synch_method => $synch_method_name) {
      $col++;
      $id = join('__', array('sm', $synch_method, $row));
      $form[$id] = array(
        '#id' => $id ,
        '#type' => 'checkbox',
        '#default_value' => isset($mapping['contexts']) ? (int)(in_array($synch_method, $mapping['contexts'])) : '',
        '#row' => $row,
        '#col' => $col,
        '#disabled' => ($this->synchMethodNotViable($ldap_server, $synch_method, $mapping) || ($action == 'nonconfigurable')),
      );
    }

    $col++;
    $id = 'sm__user_tokens__'. $row;
    $form[$id] = array(
      '#id' => $id, '#row' => $row, '#col' => $col,
      '#type' => 'textfield',
      '#default_value' => isset($mapping['user_tokens']) ? $mapping['user_tokens'] : '',
      '#size' => 40,
      '#maxlength' => 255,
      '#disabled' => ($action == 'nonconfigurable'),
      '#states' => array(
        'visible' => array(   // action to take.
          ':input[name="'. $user_attr_input_id .'"]' => array('value' => 'user_tokens'),
          ':input[name="'. $direction_input_id .'"]' => array('value' => LDAP_USER_SYNCH_DIRECTION_TO_LDAP_ENTRY),
        )
      ),
    );
    // temp_out dpm($id); // temp_out dpm($mapping);
    $this->synchFormRow = $this->synchFormRow + 1;
  }

  /**
   * is a particular synch method viable for a given mapping
   *
   * @param object $ldap_server
   * @param int $synch_method LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER, LDAP_USER_SYNCH_CONTEXT_UPDATE_DRUPAL_USER,...
   * @param array $mapping is array of mapping configuration.
   *
   * @return boolean
   */

  private function synchMethodNotViable($ldap_server, $synch_method, $mapping = NULL) {
    if ($mapping) {
      $viable = ((!isset($mapping['configurable_to_drupal']) || $mapping['configurable_to_drupal']) && ($ldap_server->queriableWithoutUserCredentials ||
         $synch_method == LDAP_USER_SYNCH_CONTEXT_AUTHENTICATE_DRUPAL_USER));
    }
    else {
      $viable = TRUE;
    }
   return (boolean)(!$viable);
  }
}
