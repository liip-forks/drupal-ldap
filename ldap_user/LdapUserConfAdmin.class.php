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

    $values['provisionMethodsDescription'] = t('"LDAP Associated" Drupal user accounts (1) have
      data mapping the account to an LDAP entry and (2) can leverage LDAP module functionality
      such as authorization, profile field synching, etc.');

    $values['provisionMethodsOptions'] = array(
      LDAP_USER_PROV_ON_LOGON => t('On successful authentication with LDAP
        credentials and no existing Drupal account, create "LDAP Associated" Drupal account.  (Requires LDAP Authentication module).'),
      LDAP_USER_PROV_ON_MANUAL_ACCT_CREATE => t('On manual creation of Drupal
        user accounts, make account "LDAP Associated" if corresponding LDAP entry exists.
        Requires a server with binding method of "Service Account Bind" or "Anonymous Bind".'),
      LDAP_USER_PROV_ON_ALL_USER_CREATION => t('Anytime a Drupal user account
        is created, make account "LDAP Associated" if corresponding LDAP entry exists.
        (includes manual creation, feeds module, Shib, CAS, other provisioning modules, etc).
        Requires a server with binding method of "Service Account Bind" or "Anonymous Bind".'),
    );


    /**
    *  Drupal Account Provisioning and Syncing
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

  protected $provisionMethodsDescription;
  protected $provisionMethodsOptions = array();
  public $provisionMethods = array();


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
      }
    }
  }

  public function drupalForm() {

    if (count($this->provisionServersOptions) == 0) {
      $message = ldap_servers_no_enabled_servers_msg('configure LDAP User');
      $form['intro'] = array(
        '#type' => 'item',
        '#markup' => t('<h1>LDAP User Settings</h1>') . $message,
      );
      return $form;
    }

    $form['#theme'] = 'ldap_user_conf_form';

    $form['intro'] = array(
      '#type' => 'item',
      '#markup' => t('<h1>LDAP User Settings</h1>'),
    );

    $form['basic'] = array(
      '#type' => 'fieldset',
      '#title' => t('Basic Provisioning Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['basic']['provisionServers'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Servers Providing Provisioning Data'),
      '#required' => FALSE,
      '#default_value' => $this->sids,
      '#options' => $this->provisionServersOptions,
      '#description' => $this->provisionServersDescription
    );

    $form['basic']['provisionMethods'] = array(
      '#type' => 'checkboxes',
      '#title' => t('New Account Provisioning Options'),
      '#required' => FALSE,
      '#default_value' => $this->provisionMethods,
      '#options' => $this->provisionMethodsOptions,
      '#description' => $this->provisionMethodsDescription
    );

    $form['basic']['userConflictResolve'] = array(
      '#type' => 'radios',
      '#title' => t('Existing Drupal User Account Conflict'),
      '#required' => 1,
      '#default_value' => $this->userConflictResolve,
      '#options' => $this->userConflictOptions,
      '#description' => t( $this->userConflictResolveDescription),
    );

    $form['basic']['acctCreation'] = array(
      '#type' => 'radios',
      '#title' => t('Application of Drupal Account settings to LDAP Authenticated Users'),
      '#required' => 1,
      '#default_value' => $this->acctCreation,
      '#options' => $this->acctCreationOptions,
      '#description' => t($this->acctCreationDescription),
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
        //  dpm($action); dpm($enabled_actions); dpm(in_array($action, $enabled_actions));
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
mappings need to be setup for each server.
'),
    );

    foreach($this->sids as $sid) {
      $ldap_server = ldap_servers_get_servers($sid, 'all', TRUE);
      $this->addServerMappingFields($ldap_server, $form);
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
    );
//  print "<pre>"; print_r($form);
  return $form;
}



/**
 * validate form, not object
 */
  public function drupalFormValidate($values)  {
    dpm('validate'); dpm($values);
    $this->populateFromDrupalForm($values);

    $errors = $this->validate();

    return $errors;
  }

/**
 * validate object, not form
 */
  public function validate() {
    $errors = array();

    if (isset($this->synchMapping[LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER])) {
      $enabled_servers = ldap_servers_get_servers(NULL, 'enabled');
      $available_user_targets = array();
      foreach ($enabled_servers as $sid => $ldap_server) {
        $available_user_targets[$sid] = array();
        drupal_alter('ldap_user_targets_list', $available_user_targets[$sid], $ldap_server);
      }
      foreach ($this->synchMapping[LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER] as $user_target => $conf) {

       // dpm('user_target'. $user_target); dpm($conf);
        // neither of these should come up without altered form, but could check for:
        // 1. is field configurable ?
        // 2. is $user_target and $ldap_source both populated
      }
    }



    return $errors;
  }

  protected function populateFromDrupalForm($values) {
    dpm('populateFromDrupalForm'); dpm($values);
    $this->sids = $values['provisionServers'];
    $this->provisionMethods = $values['provisionMethods'];
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
    $this->synchMapping = $this->synchMappingsFromForm($values);
    // $this->synchMapping[LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER]['property_mail'][sid] => array(LDAP_USER_SYNCH_CONTEXT_INSERT_DRUPAL_USER, LDAP_USER_SYNCH_CONTEXT_UPDATE_DRUPAL_USER),

    //  $this->synchMapping = array();  // development just for clearing out old data.
   // dpm('populateFromDrupalForm this->synchMapping'); dpm($this->synchMapping);

  }

  private function synchMappingsFromForm($values) {
    $mappings = array();
    foreach ($values as $field => $value) {
      $parts = explode('__', $field);
      // $ldap_server->sid, 'add', 'user_target' ,  $i
      $action = NULL;
      if (count($parts) == 4 && in_array($parts[0], $this->sids) && $parts[2] == 'ldap_source' && $parts[1] == 'add') {
        $field_template = join('__', array($parts[0], 'add', '[field]', $parts[3]));
        $action = 'add';
      }
      elseif (count($parts) == 3 && in_array($parts[0], $this->sids) && $parts[2] == 'ldap_source') {
        $user_target = $parts[1];
        $field_template = join('__', array($parts[0], $user_target, '[field]'));
        $action = 'update';
      }
      if ($action) {
        $direction = LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER;
        $sid = $parts[0];

        $configurable_input_id = str_replace('[field]', 'configurable', $field_template);
        $configurable = $values[$configurable_input_id];

        $ldap_source_input_id = str_replace('[field]', 'ldap_source', $field_template);
        $ldap_source = $values[$ldap_source_input_id];

        $user_target_input_id = str_replace('[field]', 'user_target', $field_template);
        $user_target = $values[$user_target_input_id];

        $notes_input_id = str_replace('[field]', 'notes', $field_template);
        $notes = $values[$notes_input_id];

       // dpm("configurable=$configurable,configurable_input_id=$configurable_input_id,ldap_source=$ldap_source,user_target=$user_target");
        if ($configurable && $ldap_source && $user_target) {
          $mappings[$direction][$user_target] = array(
            'sid' => $sid,
            'ldap_source' => $ldap_source,
            'notes' => $notes,
            );
          foreach ($this->synchTypes as $synch_context => $synch_context_name) {
            $context_field_id = str_replace('[field]', $synch_context, $field_template);
            if ($values[$context_field_id]) {
              $mappings[$direction][$user_target]['contexts'][] = $synch_context;
            }
          }
        }
      }
    }
    return $mappings;
  }

  public function drupalFormSubmit($values) {
   // dpm('drupalFormSubmit'); dpm($values);
    $this->populateFromDrupalForm($values);
    try {
      $save_result = $this->save();
    }
    catch (Exception $e) {
      $this->errorName = 'Save Error';
      $this->errorMsg = t('Failed to save object.  Your form data was not saved.');
      $this->hasError = TRUE;
    }

  }


private function addServerMappingFields($ldap_server, &$form) {

  $synch_cols = count($this->synchTypes);

  $table = array();
  $table['#theme'] = 'table';
  $table['#type'] = 'element';


  // target options
  $available_user_targets = array();
  drupal_alter('ldap_user_targets_list', $available_user_targets, $ldap_server);
 // dpm('available_user_targets'); dpm($available_user_targets);
  $target_options = array('0' => 'Select Target');
  foreach ($available_user_targets as $target_id => $mapping) {
    if (!isset($mapping['configurable']) || $mapping['configurable']) {
      $target_options[$target_id] = substr($mapping['name'], 0, 25);
    }
  }

  $row = 0;

  // loop through each mapping and add to form
 //  dpm($available_user_targets);
  foreach ($available_user_targets as $target_id => $mapping) {
    if (!$mapping['configurable']) {
      $id =  join('__', array($ldap_server->sid, 'configurable', $row));
      $form[$id] = array(
        '#id' => $id,
        '#type' => 'hidden',
        '#default_value' => 0,
      );

      $id = join('__', array($ldap_server->sid, 'ldap_source', $row));
      $form[$id] = array(
        '#id' => $id,
        '#type' => 'item',
        '#markup' => isset($mapping['source']) ? $mapping['source'] : '?',
        '#row' => $row,
        '#col' => 0,
        );


      $id =  join('__', array($ldap_server->sid, 'user_target' , $row));
      $form[$id] = array(
        '#id' => $id,
        '#type' => 'item',
        '#markup' => isset($mapping['name']) ? $mapping['name'] : '?',
        '#row' => $row,
        '#col' => 1,
        );

      $col = 1;
      foreach ($this->synchTypes as $synch_method => $synch_method_name) {
        $col++;
        $id = join('__', array($ldap_server->sid, $synch_method, $row));
        $form[$id] = array(
          '#id' => $id ,
          '#type' => 'checkbox',
          '#default_value' => '',
          '#options' => array(1,0),
          '#row' => $row,
          '#col' => $col,
          '#disabled' => $this->synchMethodNotViable($ldap_server, $synch_method, $mapping),
          );
      }

      $id =  join('__', array($ldap_server->sid, 'notes' , $row));
      $form[$id] = array(
        '#id' => $id,
        '#type' => 'item',
        '#markup' => isset($mapping['configurable_text']) ? $mapping['configurable_text'] : '?',
        '#row' => $row,
        '#col' => $synch_cols + 2,
        );

      $row ++;
    }
  }

  if (isset($this->synchMapping[LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER])) {
    foreach ($this->synchMapping[LDAP_USER_SYNCH_DIRECTION_TO_DRUPAL_USER] as $user_target => $mapping) {

      $row++;
      $id =  join('__', array($ldap_server->sid, $user_target, 'configurable'));
      $form[$id] = array(
        '#id' => $id,
        '#type' => 'hidden',
        '#default_value' => 1,
      );

      $id =  join('__', array($ldap_server->sid, $user_target, 'ldap_source'));
      $form[$id] = array(
        '#id' => $id,
        '#type' => 'textfield',
        '#default_value' => isset($mapping['ldap_source']) ? $mapping['ldap_source'] : '',
        '#size' => 20,
        '#maxlength' => 255,
        '#row' => $row,
        '#col' => 0,
        );


      $id =  join('__', array($ldap_server->sid, $user_target, 'user_target'));
      $form[$id] = array(
        '#id' => $id,
        '#type' => 'select',
        '#default_value' => $user_target,
        '#options' => $target_options,
        '#row' => $row,
        '#col' => 1,
        );

      $col = 1;
      foreach ($this->synchTypes as $synch_method => $synch_method_name) {
        $col++;
        $id = join('__', array($ldap_server->sid, $user_target, $synch_method));
        $form[$id] = array(
          '#id' => $id ,
          '#type' => 'checkbox',
          '#default_value' => isset($mapping['contexts']) ? (int)(in_array($synch_method, $mapping['contexts'])) : '',
          '#options' => array(1,0),
          '#row' => $row,
          '#col' => $col,
          '#disabled' => $this->synchMethodNotViable($ldap_server, $synch_method, $mapping)
          );
      }

      $id =  join('__', array($ldap_server->sid, $user_target, 'notes'));
      $form[$id] = array(
        '#id' => $id,
        '#type' => 'textfield',
        '#default_value' => isset($mapping['notes']) ? $mapping['notes'] : '',
        '#size' => 40,
        '#maxlength' => 255,
        '#row' => $row,
        '#col' => $col + 1,
        );
    }
  }

  for ($i=0; $i<4; $i++) {
    $row++;
    $id =  join('__', array($ldap_server->sid, 'add', 'configurable', $i));
    $form[$id] = array(
      '#id' => $id,
      '#type' => 'hidden',
      '#default_value' => 1,
      );

    $id =  join('__', array($ldap_server->sid, 'add', 'ldap_source', $i));
    $form[$id] = array(
      '#id' => $id,
      '#type' => 'textfield',
      '#default_value' => '',
      '#size' => 20,
      '#maxlength' => 255,
      '#row' => $row,
      '#col' => 0,
      );


    $id =  join('__', array($ldap_server->sid, 'add', 'user_target' ,  $i));
    $form[$id] = array(
      '#id' => $id,
      '#type' => 'select',
      '#default_value' => '',
      '#options' => $target_options,
      '#row' => $row,
      '#col' => 1,
      );

    $col = 1;
    foreach ($this->synchTypes as $synch_method => $synch_method_name) {
      $col++;
      $id = join('__', array($ldap_server->sid, 'add', $synch_method,  $i));
      $form[$id] = array(
        '#id' => $id ,
        '#type' => 'checkbox',
        '#default_value' => '',
        '#options' => array(1,0),
        '#row' => $row,
        '#col' => $col,
        '#disabled' => $this->synchMethodNotViable($ldap_server, $synch_method, $mapping)
        );
    }

    $id =  join('__', array($ldap_server->sid, 'add', 'notes', $i));
    $form[$id] = array(
      '#id' => $id,
      '#type' => 'textfield',
      '#default_value' => '',
      '#size' => 40,
      '#maxlength' => 255,
      '#row' => $row,
      '#col' => $col + 1,
      );
  }

  return $table;

  }
  private function synchMethodNotViable($ldap_server, $synch_method, $mapping) {
   $viable = ((!isset($mapping['configurable']) || $mapping['configurable']) && ($ldap_server->queriableWithoutUserCredentials ||
      $synch_method == LDAP_USER_SYNCH_CONTEXT_AUTHENTICATE_DRUPAL_USER));
   return (boolean)(!$viable);
  }
}
