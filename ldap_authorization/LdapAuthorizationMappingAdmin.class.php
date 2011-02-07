<?php
// $Id$

/**
 * @file
 * class to encapsulate an ldap authorization ldap entry to authorization ids mapping
 *
 */
 require_once('LdapAuthorizationMapping.class.php');
/**
 * LDAP Authorization Mapping Admin Class
 */
class LdapAuthorizationMappingAdmin extends LdapAuthorizationMapping {


  public function save() {
    
    $op = $this->inDatabase ? 'update' : 'insert';

    $values['mapping_id'] = $this->mappingID;  
    $values['sid'] = $this->sid;
    $values['consumer_type'] = $this->consumerType;
    $values['consumer_module'] = $this->consumerModule;
    $values['description'] = $this->description;
    $values['status'] = (int)$this->status;
    $values['only_ldap_authenticated'] = (int)$this->onlyApplyToLdapAuthenticated;
    $values['derive_from_dn'] = (int)$this->deriveFromDn;
    $values['derive_from_dn_attr'] = $this->deriveFromDnAttr;
    $values['derive_from_attr'] = (int)$this->deriveFromAttr;
    $values['derive_from_attr_attr'] = $this->arrayToLines($this->deriveFromAttrAttr);
    $values['derive_from_entry'] = (int)$this->deriveFromEntry;
    $values['derive_from_entry_entries'] = $this->arrayToLines($this->deriveFromEntryEntries);
    $values['derive_from_entry_attr'] = $this->deriveFromEntryAttr;
    $values['mappings'] = $this->arrayToPipeList($this->mappings);
    $values['use_filter'] = (int)$this->useMappingsAsFilter;
    $values['synch_to_ldap'] = (int)$this->synchToLdap;
    $values['synch_on_logon'] = (int)$this->synchOnLogon;
    $values['synch_manually'] = (int)$this->synchManually;
    $values['revoke_ldap_provisioned'] = (int)$this->revokeLdapProvisioned;
    $values['revoke_non_ldap_provisioned'] = (int)$this->revokeNonLdapProvisioned;
    $values['create_targets'] = (int)$this->createTargets;
    $values['regrant_ldap_provisioned'] = (int)$this->regrantLdapProvisioned;
    
     if ($op == 'update') {
        try {
            $count = db_update('ldap_authorization')
             ->fields($values)
             ->condition('mapping_id', $values['mapping_id'])
             ->execute();
        }
        catch(Exception $e) {
          drupal_set_message(t('db update failed. Message = %message, query= %query',
            array('%message' => $e->getMessage(), '%query' => $e->query_string)), 'error');
        }
      }
      else { // insert

        try {
          $ret = db_insert('ldap_authorization')
             ->fields($values)
             ->execute();
        }
        catch(Exception $e) {
          drupal_set_message(t('db insert failed. Message = %message, query= %query',
            array('%message' => $e->getMessage(), '%query' => $e->query_string)), 'error');
        }

        $this->inDatabase = TRUE;
      }

  }

  public $fields;
  public $consumers;
  public $consumer;  // consumer object

 public function delete() {
    if ($this->mapping_id) {
      $this->inDatabase = FALSE;
       return db_delete('ldap_authorization')->condition('mapping_id', mapping_id)->execute();
    } else {
      return FALSE;
    }
    
 }
  public function __construct($_mid, $_new = FALSE, $_sid = NULL, $_consumer_type = NULL, $_consumer_module = NULL) {
    parent::__construct($_mid, $_new, $_sid, $_consumer_type, $_consumer_module);

    $this->fields = $this->fields();
    $this->consumers = ldap_authorization_get_consumers(NULL, TRUE);
    if ($_new) {
      $this->consumer = ldap_authorization_get_consumer_object(array('consumer_type' => $_consumer_type));
    } else {
      $this->consumer = ldap_authorization_get_consumer_object(array('mapping_id' => $_mid));
    }
    if ($_new) {
       $this->setConsumerDefaults();
    }
  }

  protected function setConsumerDefaults() {
    foreach ($this->consumer->defaultableMappingProperties as $property) {
      $default_prop_name = $property . 'Default';
      $this->$property = $this->consumer->$default_prop_name;
    }


  }

  static function getMappings($mapping_id = NULL, $consumer_type = NULL, $flatten = FALSE, $class = 'LdapAuthorizationMapping') {
    $select = db_select('ldap_authorization','ldap_authorization');
    $select->fields('ldap_authorization');
    if ($mapping_id) {
       $select->condition('ldap_authorization.mapping_id', $mapping_id);
    } 

    try {
      $mapping_vars = $select->execute()->fetchAllAssoc('mapping_id',  PDO::FETCH_ASSOC);
    }
    catch(Exception $e) {
      drupal_set_message(t('db index query failed. Message = %message, query= %query',
        array('%message' => $e->getMessage(), '%query' => $e->query_string)), 'error');
      return array();
    }

    $mappings = array();
    foreach ($mapping_vars as $_mapping_id => $mapping) {
      if (module_exists($mapping['consumer_module'])) {
        $mappings[$_mapping_id] = ($class == 'LdapAuthorizationMapping') ? new LdapAuthorizationMapping($_mapping_id) : new LdapAuthorizationMappingAdmin($_mapping_id);
      }
    }
    if ($flatten && $mapping_id && count($mappings) == 1) {
      return $mappings[$mapping_id];
    } else {
      return $mappings;
    }
  }

  public function drupalForm($server_options, $op) {

   
    $consumer_tokens = $this->consumer->tokens();
    $form['intro'] = array(
        '#type' => 'item',
        '#markup' => t('<h1>LDAP to !consumer_name Mapping Configuration</h1>', $consumer_tokens),
    );

    $form['status_intro'] = array(
        '#type' => 'item',
        '#title' => t('Part I.  Core Configuration.', $consumer_tokens),
    );

    $form['status'] = array(
      '#type' => 'fieldset',
      '#title' => t('Core Configuration', $consumer_tokens),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['status']['mapping_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Machine name for this !consumer_shortName mapping configuration.', $consumer_tokens),
      '#default_value' => $this->mappingID,
      '#size' => 20,
      '#maxlength' => 20,
      '#disabled' => ($op == 'update'),
      '#description' => t('May only contain alphanumeric characters (a-z, A-Z, 0-9, and _)' ),
    );

    $form['status']['sid'] = array(
      '#type' => 'radios',
      '#title' => t('LDAP Server used in !consumer_name mapping.', $consumer_tokens),
      '#required' => 1,
      '#default_value' => $this->sid,
      '#options' => $server_options,
    );

    $form['status']['consumer_type'] = array(
      '#type' => 'hidden',
      '#value' => $this->consumerType,
      '#required' => 1,
    );

    $form['status']['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Short description for this !consumer_shortName mapping configuration.', $consumer_tokens),
      '#default_value' => $this->description,
      '#size' => 60,
      '#maxlength' => 60,
    );

    $form['status']['status'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable this authorization mapping', $consumer_tokens),
      '#default_value' =>  $this->status,
    );

    $form['status']['only_ldap_authenticated'] = array(
      '#type' => 'checkbox',
      '#title' => t('Only apply the following LDAP to !consumer_name authorization mapping to users authenticated via LDAP.', $consumer_tokens),
      '#default_value' =>  $this->onlyApplyToLdapAuthenticated,
    );


    $form['mapping_intro'] = array(
        '#type' => 'item',
        '#title' => t('Part II.  How are !consumer_name !consumer_namePlural derived from LDAP data?', $consumer_tokens),
        '#markup' => t('First, we need to configure how LDAP data is used to derive !consumer_name !consumer_namePlural.
          One or more of the following 3 approaches may be used.', $consumer_tokens),
    );
    /**
     *  derive from DN option
     */
    $form['derive_from_dn'] = array(
      '#type' => 'fieldset',
      '#title' => t('II.A. Derive !consumer_name from DN', $consumer_tokens),
      '#collapsible' => TRUE,
      '#collapsed' => !$this->deriveFromDn,
    );

    $form['derive_from_dn']['derive_from_dn'] = array(
      '#type' => 'checkbox',
      '#title' => t('!consumer_name is derived from user\'s DN', $consumer_tokens),
      '#default_value' => $this->deriveFromDn,
      '#description' => t('<p>Check this option if your users\' DNs look like <code>cn=jdoe,<strong>ou=Group1</strong>,cn=example,cn=com</code> and <code>Group1</code> turns out to be the !consumer_name_short you want.</p>', $consumer_tokens),
    );

    $form['derive_from_dn']['derive_from_dn_attr'] = array(
      '#type' => 'textfield',
      '#title' => t('Attribute of the DN which contains the !consumer_shortName name', $consumer_tokens),
      '#default_value' => $this->deriveFromDnAttr,
      '#size' => 50,
      '#maxlength' => 255,
      '#description' => t('The name of the attribute which contains the !consumer_shortName name. In the example above, it would be
        <code>ou</code>, as the DN contains the string <code>ou=Group1</code> and <code>Group1</code>
        happens to be the desired !consumer_short_name name.', $consumer_tokens),
    );

     /**
     *  derive from attributes option
     */

    $form['derive_from_attr'] = array(
      '#type' => 'fieldset',
      '#title' => t('II.B. Derive !consumer_namePlural by attribute', $consumer_tokens),
      '#collapsible' => TRUE,
      '#collapsed' => !$this->deriveFromAttr,
    );

    $form['derive_from_attr']['derive_from_attr'] = array(
      '#type' => 'checkbox',
      '#title' => t('!consumer_shortNamePlural are specified by LDAP attributes', $consumer_tokens),
      '#default_value' => $this->deriveFromAttr,
    );

    $form['derive_from_attr']['derive_from_attr_attr'] = array(
      '#type' => 'textarea',
      '#title' => t('Attribute names (one per line)'),
      '#default_value' => $this->arrayToLines($this->deriveFromAttrAttr),
      '#cols' => 50,
      '#rows' => 6,
      '#description' => t('If the !consumer_shortNamePlural are stored in the user entries, along with the rest of their data, then enter here a list of attributes which may contain them.', $consumer_tokens),
    );


     /**
     *  derive from attributes option
     */

    $form['derive_from_entry'] = array(
      '#type' => 'fieldset',
      '#title' => t('II.C. Derive !consumer_namePlural from entry', $consumer_tokens),
      '#collapsible' => TRUE,
      '#collapsed' => !$this->deriveFromEntry,
    );

    $form['derive_from_entry']['derive_from_entry'] = array(
      '#type' => 'checkbox',
      '#title' => t('!consumer_shortNamePlural exist as LDAP entries where a multivalued attribute contains the members\' CNs', $consumer_tokens),
      '#default_value' => $this->deriveFromEntry,
    );


    $form['derive_from_entry']['derive_from_entry_entries'] = array(
      '#type' => 'textarea',
      '#title' => t('LDAP DNs containing !consumer_shortNamePlural (one per line)', $consumer_tokens),
      '#default_value' => $this->arrayToLines($this->deriveFromEntryEntries),
      '#cols' => 50,
      '#rows' => 6,
      '#description' => t('Enter here a list of LDAP nodes from where !consumer_shortNamePlural should be searched for.
        The module will look them up recursively from the given nodes.', $consumer_tokens),
    );




    $form['derive_from_entry']['derive_from_entry_attr'] = array(
      '#type' => 'textfield',
      '#title' => t('Attribute holding !consumer_shortNamePlural members', $consumer_tokens),
      '#default_value' => $this->deriveFromEntryAttr,
      '#size' => 50,
      '#maxlength' => 255,
      '#description' => t('Name of the multivalued attribute which holds the CNs of !consumer_shortNamePlural members,
         for example: memberUid', $consumer_tokens),
    );


     /**
     *  filter and whitelist
     */

     $form['filter_intro'] = array(
        '#type' => 'item',
        '#title' => t('Part III.  Mapping and White List.', $consumer_tokens),
        '#markup' => t('The rules in Part I. will create a list of !consumer_name !consumer_namePlural.
          The next field allows you to transform the !consumer_name !consumer_namePlural derived in Part I.
          By checking the checkbox below it, the same list can be used as a white list to limit which !consumer_name !consumer_namePlural
          are mapped from LDAP.', $consumer_tokens),
    );

    $form['filter_and_mappings'] = array(
      '#type' => 'fieldset',
      '#title' => t('III.A. LDAP to !consumer_name mapping and filtering', $consumer_tokens),
      '#description' => t('@todo (this needs some rewriting.  Its fuzzy. This module automatically decides the !consumer_namePlural based on LDAP data.
        For example:<ul>
        <li>LDAP group: Admins => !consumer_name: Admins <br/>...is written <code>as:Admins|Admins</code>  </li>
        <li>LDAP group: ou=Underlings,dc=myorg,dc=mytld => !consumer_name: Underlings. <br/>
        ...is written as:<code>ou=Underlings,dc=myorg,dc=mytld|Underlings</code> </li>
        </ul>', $consumer_tokens),
      '#collapsible' => TRUE,
      '#collapsed' => !($this->mappings || $this->useMappingsAsFilter),
    );

    $form['filter_and_mappings']['mappings'] = array(
      '#type' => 'textarea',
      '#title' => t('Mapping of LDAP to !consumer_name', $consumer_tokens),
      '#default_value' => $this->arrayToPipeList($this->mappings),
      '#cols' => 50,
      '#rows' => 5,
      '#description' => t('Enter a list of LDAP groups and their !consumer_name mappings, one per line with a | delimiter.
        Should be in the form [ldap group]|[!consumer_name] such as:
        <br/>cn=ED IT NAG Staff,DC=ad,DC=uiuc,DC=edu|admin
        <br/>cn=Ed Webs UIUC Webmasters,DC=ad,DC=uiuc,DC=edu|committee member'),
    );
    $form['filter_and_mappings']['use_filter'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use LDAP group to !consumer_namePlural filtering', $consumer_tokens),
      '#default_value' => $this->useMappingsAsFilter,
      '#description' => t('If enabled, only above mapped LDAP groups will be mapped to !consumer_namePlural.
        If not enabled, a !consumer_name will be created for every ldap group the user is associated with.')
    );

    $form['advanced_intro'] = array(
        '#type' => 'item',
        '#title' => t('Part IV.  More Settings.', $consumer_tokens),
        '#markup' => t('', $consumer_tokens),
    );


   $form['advanced_intro'] = array(
        '#type' => 'item',
        '#title' => t('IV.A. Map in both directions.', $consumer_tokens),
        '#markup' => t('', $consumer_tokens),
    );


   $form['misc_settings']['allow_synch_both_directions'] = array(
      '#type' => 'checkbox',
      '#disabled' => !$this->consumer->allowSynchBothDirections,
      '#default_value' => $this->synchToLdap,
      '#title' => t('Check this option if you want LDAP data to be modified if a user
        has a !consumer_name.  In other words, synchronize both ways.  For this to work the ldap server
        needs to writeable, the right side of the mappings list must be unique, and I.B or I.C.
        derivation must be used.', $consumer_tokens),
    );

    $synchronization_modes = array();
    if ($this->synchOnLogon)  {
      $synchronization_modes[] = 'user_logon';
    }
    if ($this->synchManually)  {
      $synchronization_modes[] = 'manually';
    }
    $form['misc_settings']['synchronization_modes'] = array(
      '#type' => 'checkboxes',
      '#title' => t('IV.B. When should !consumer_namePlural be granted/revoked from user?', $consumer_tokens),
      '#options' => array(
          'user_logon' => t('When a user logs on'),
          'manually' => t('Manually or via another module')
      ),
      '#default_value' => $synchronization_modes,
      '#description' => t('<p>"When a user logs on" is the common way to do this. Manually may make sense in the following cases:
        <ul>
        <li>If you are testing how you mappings would work on a test server and don\'t want to keep logging on.</li>
        <li>You have another module that is using this module as an API.</li>
        <li>You are just using LDAP Authorization to get the site going then want to deal with !consumer_name !consumer_namePlural
        via the drupal interface.</li>
        </ul></p>', $consumer_tokens),
    );

    $synchronization_actions = array();
    if ($this->revokeLdapProvisioned)  {
      $synchronization_actions[] = 'revoke_ldap_provisioned';
    }
    if ($this->revokeNonLdapProvisioned)  {
      $synchronization_actions[] = 'revoke_non_ldap_provisioned';
    }
    if ($this->createTargets)  {
      $synchronization_actions[] = 'create_targets';
    }
    if ($this->regrantLdapProvisioned)  {
      $synchronization_actions[] = 'regrant_ldap_provisioned';
    }
    $form['misc_settings']['synchronization_actions'] = array(
      '#type' => 'checkboxes',
      '#title' => t('IV.C. What actions would you like performed when !consumer_namePlural are granted/revoked from user?', $consumer_tokens),
      '#options' => array(
          'revoke_ldap_provisioned' => t('Revoke !consumer_namePlural previously granted by LDAP Authorization but no longer valid.', $consumer_tokens),
          'regrant_ldap_provisioned' => t('Re grant !consumer_namePlural previously granted by LDAP Authorization but removed manually.', $consumer_tokens),
          'revoke_non_ldap_provisioned' => t('Revoke !consumer_namePlural not created by LDAP Authorization and not currently valid.', $consumer_tokens),
          'create_targets' => t('Create !consumer_namePlural if they do not exist.', $consumer_tokens),
      ),
      '#default_value' => $synchronization_actions,
    );
        /**
     * @todo  some general options for an individual mapping (perhaps in an advance tab).
     *
     * - on synchronization allow: revoking authorizations made by this module, authorizations made outside of this module
     * - on synchronization create authorization contexts not in existance when needed (drupal roles etc)
     * - synchronize actual authorizations (not cached) when granting authorizations
     */

    switch($op) {
      case 'add':
      $action = 'Add';
      break;

      case 'update':
      $action = 'Update';
      break;

      case 'delete':
      $action = 'Delete';
      break;
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $action,
    );

  return $form;
   }


  protected function loadFromForm($values, $op) {


  }

  public function drupalFormValidate($op, $values)  {
    $errors = array();

    if ($op == 'delete') {
      if (!$this->mappingID) {
        $errors['mapping_id_missing'] = 'Mapping id missing from delete form.';
      }
    } else {

      $this->populateFromDrupalForm($op, $values);

      $errors = $this->validate();
      if (count($this->mappings) == 0 && trim($values['mappings'])) {
        $errors['mappings'] = t('Bad mapping syntax.  Text entered but not able to convert to array.');
      }
    }
    return $errors;
  }

  public function validate() {
    $errors = array();

    if (!$this->consumerType) {
      $errors['consumer_type'] = t('Consumer type is missing.');
   }

    if ($this->inDatabase  && (!$this->mappingID)) {  // update or delete but no mappingID set
        $errors['mapping_id'] = t('Update or delete called without mapping id in form.');
    } elseif (!$this->inDatabase  && !$this->mappingID) {  // new and no mappingID given
        $errors['mapping_id'] = t('Mapping ID is required');
    } elseif (!$this->inDatabase && $this->getMappings($this->mappingID)) {
        $errors['mapping_id'] = t('Mapping ID %mapping_id is not unique.', array('%mapping_id' => $this->mappingID)); 
    }

   // are correct values available for selected mapping approach
   if ($this->deriveFromDn && !trim($this->deriveFromDnAttr)) {
      $errors['derive_from_dn'] = t('DN attribute is missing.');
   }
   if ($this->deriveFromAttr && !count($this->deriveFromAttrAttr)) {
      $errors['derive_from_attr'] = t('Attribute names are missing.');
    }
   if ($this->deriveFromEntry && !count($this->deriveFromEntryEntries)) {
      $errors['derive_from_entry'] = t('Nodes are missing.');
   }
   if ($this->deriveFromEntry && !trim($this->deriveFromEntryAttr)) {
      $errors['derive_from_entry_attribute'] = t('Attribute is missing.');
    }

  if (count($this->mappings) > 0) {
    foreach ($this->mappings as $mapping_item) {
     list($map_from, $map_to) = $mapping_item;
    // validate $mapto is valid mapping target as much as possible.  perhaps alphanum or call hook validate to provider
    }
  }
  if ($this->useMappingsAsFilter && !count($this->mappings)) {
    $errors['mappings'] = t('Mappings are missing.');
  }
  return $errors;
}

  protected function populateFromDrupalForm($op, $values) {

    $this->inDatabase = ($op == 'update');

    $values['mappings'] = $this->pipeListToArray($values['mappings']);
    $values['derive_from_attr_attr'] = $this->linesToArray($values['derive_from_attr_attr']);
    $values['derive_from_entry_entries'] = $this->linesToArray($values['derive_from_entry_entries']);

    $this->consumerType = $values['consumer_type'];
    $this->description = $values['description'];
    $this->status = (bool)$values['status'];
    $this->onlyApplyToLdapAuthenticated  = (bool)(@$values['only_ldap_authenticated']);

    $this->deriveFromDn  = (bool)(@$values['derive_from_dn']);
    $this->deriveFromDnAttr = $values['derive_from_dn_attr'];

    $this->deriveFromAttr  = (bool)($values['derive_from_attr']);
    $this->deriveFromAttrAttr = $values['derive_from_attr_attr'];

    $this->deriveFromEntry  = (bool)(@$values['derive_from_entry']);
    $this->deriveFromEntryEntries = $values['derive_from_entry_entries'];
    $this->deriveFromEntryAttr = $values['derive_from_entry_attr'];

    $this->mappings = $values['mappings'];
    $this->useMappingsAsFilter  = (bool)(@$values['use_filter']);


    $this->synchOnLogon = (bool)(@$values['synchronization_modes']['user_logon']);
    $this->synchManually = (bool)(@$values['synchronization_modes']['manually']);
    $this->regrantLdapProvisioned = (bool)(@$values['synchronization_actions']['regrant_ldap_provisioned']);
    $this->revokeLdapProvisioned = (bool)(@$values['synchronization_actions']['revoke_ldap_provisioned']);
    $this->revokeNonLdapProvisioned = (bool)(@$values['synchronization_actions']['revoke_non_ldap_provisioned']);
    $this->createTargets = (bool)(@$values['synchronization_actions']['create_targets']);
  
}

  public function drupalFormSubmit($op, $values) {

   $this->populateFromDrupalForm($op, $values);
   if ($op == 'delete') {
     $this->delete();
   } else { // add or update

      try {
          $save_result = $this->save();
      }
      catch(Exception $e) {
        $this->errorName = 'Save Error';
        $this->errorMsg = t('Failed to save object.  Your form data was not saved.');
        $this->hasError = TRUE;
      }
   }
  }


  public static function fields() {

     /**
     * consumer_type is tag (unique alphanumeric id) of consuming authorization such as
     *   drupal_roles, og_groups, civicrm_memberships
     */
    $fields = array(
      'mapping_id' => array(
          'schema' => array(
              'type' => 'varchar',
              'length' => '20',
              'not null' => TRUE
          )
       ),
      'numeric_mapping_id' => array(
          'schema' => array(
            'type' => 'serial',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'description' => 'Primary ID field for the table.  Only used internally.',
            'no export' => TRUE,
           ),
        ),
      'sid' => array(
        'schema' => array(
          'type' => 'varchar',
          'length' => 20,
          'not null' => TRUE,
        )
       ),
      'consumer_type' => array(
         'schema' => array(
            'type' => 'varchar',
            'length' => 20,
            'not null' => TRUE,
         )
       ),
     'consumer_module' => array(
         'schema' => array(
            'type' => 'varchar',
            'length' => 30,
            'not null' => TRUE,
         )
       ),
      
      'description' => array(
        'schema' => array(
          'type' => 'varchar',
          'length' => '60',
          'not null' => FALSE
          )
       ),

      'status' => array(
          'schema' => array(
            'type' => 'int',
            'size' => 'tiny',
            'not null' => TRUE,
            'default' => 0,
          )
       ),
      'only_ldap_authenticated' => array(
        'schema' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 1,
         )
       ),
      'derive_from_dn' => array(
        'schema' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
        )
      ),
      'derive_from_dn_attr' => array(
         'schema' => array(
            'type' => 'varchar',
            'length' => 4,
            'default' => NULL,
         )
       ),
      'derive_from_attr' => array(
        'schema' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
         )
       ),
      'derive_from_attr_attr' => array(
        'schema' => array(
          'type' => 'varchar',
          'length' => 255,
          'default' => NULL,
         )
       ),
      'derive_from_entry'  => array(
          'schema' => array(
            'type' => 'int',
            'size' => 'tiny',
            'not null' => TRUE,
            'default' => 0,
         )
       ),
      'derive_from_entry_entries' => array(
        'form_default' => array(),
        'schema' => array(
          'default' => NULL,
          'type' => 'text',
        )
       ),

      'derive_from_entry_attr' => array(
        'schema' => array(
          'type' => 'varchar',
          'length' => 255,
          'default' => NULL,
         )
       ),

      'mappings'  => array(
        'form_default' => array(),
        'schema' => array(
          'type' => 'text',
          'not null' => FALSE,
          'default' => NULL,
         )
       ),

      'use_filter'   => array(
        'schema' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 1,
        )
       ),
      'synchronization_modes'  => array(
          'form_default' =>  array('user_logon'),
       ),

      'synchronization_actions'  => array(
         'form_default' =>  array('revoke_ldap_provisioned', 'create_targets'),
       ),
        
      'synch_to_ldap'  => array(
        'schema' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => '0',
         ),
      ),

      'synch_on_logon'  => array(
        'schema' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => '0',
         ),
      ),

      'synch_manually'  => array(
        'schema' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => '0',
         ),
      ),

      'revoke_ldap_provisioned'  => array(
        'schema' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => '0',
         ),
      ),
      'revoke_non_ldap_provisioned'  => array(
        'schema' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => '0',
         ),
      ),  
     'create_targets'  => array(
        'schema' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => '0',
        ),
      ),
     'regrant_ldap_provisioned'  => array(
        'schema' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => '0',
         ),
      ),
    );
    return $fields;
  }




  protected function arrayToPipeList($array) {
    $result_text = "";
    foreach ($array as $map_pair) {
      $result_text .= $map_pair[0] .'|'. $map_pair[1] . "\n";
    }
    return $result_text;
  }

  protected function arrayToLines($array) {
        $lines = "";
        if (is_array($array)) {
          $lines = join("\n", $array);
        }  elseif (is_array(@unserialize($array))) {
          $lines = join("\n", unserialize($array));
        }
        return $lines;
      }



}



