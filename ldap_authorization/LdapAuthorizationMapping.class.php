<?php
/**
 * @file
 * class to encapsulate an ldap entry to authorization target ids mapping configuration
 *
 * this is the lightweight version of the class for use on logon etc.
 * the LdapAuthorizationMappingAdmin extends this class and has save,
 * iterate, etc methods.
 */

/**
 * LDAP Authorization Mapping
 */
class LdapAuthorizationMapping {

  public $mappingID;
  public $sid = NULL;
  public $consumerModule = NULL; // id of module providing consumer
  public $inDatabase = FALSE;
  public $consumerType = NULL;
  public $description = NULL;
  public $status = NULL;
  public $onlyApplyToLdapAuthenticated = TRUE;

  public $deriveFromDn = FALSE;  
  public $deriveFromDnAttr = NULL;

  public $deriveFromAttr = FALSE;  
  public $deriveFromAttrAttr = NULL;

  public $deriveFromEntry = FALSE; 
  public $deriveFromEntryEntries = NULL;
  public $deriveFromEntryAttr = NULL;

  public $mappings = array(); 
  public $useMappingsAsFilter = TRUE; 

  public $synchToLdap = FALSE; 

  public $synchOnLogon = TRUE;
  public $synchManually = TRUE;

  public $revokeLdapProvisioned = TRUE;
  public $revokeNonLdapProvisioned = FALSE;
  public $regrantLdapProvisioned = TRUE;
  public $createTargets = TRUE;

  public $errorMsg = NULL;
  public $hasError = FALSE;
  public $errorName = NULL;
  

  public function clearError() {
    $this->hasError = FALSE;
    $this->errorMsg = NULL;
    $this->errorName = NULL;
  }
   /**
   * Constructor Method
   */
  function __construct($_mid, $_new = FALSE, $_sid = NULL, $_consumer_type = NULL, $_consumer_module = NULL) {

    $this->load($_mid, $_new, $_sid, $_consumer_type, $_consumer_module);
  }
  /**
   * Load Method
   */
  function load($_mid, $_new = FALSE, $_sid = NULL, $_consumer_type = NULL, $_consumer_module = NULL) {
    if (!is_scalar($_mid)  && $_new == FALSE) {
      return;      
    }
    if ($_consumer_type == '' && $_new == TRUE) {
      return;      
    }
    
    $this->consumerType = $_consumer_type;
    $this->consumerModule = $_consumer_module;
    $this->mappingID = $_mid;
    $this->sid = $_sid;
    
    
    if ($_new) {
      $this->inDatabase = FALSE;
    } else {
      $this->inDatabase = TRUE;
      $this->loadFromDb();
    //  $saved = variable_get("ldap_authorization_map_". $this->mappingID, array());
    //  foreach ($this->saveable as $property) {
     //   if (isset($saved[$property])) {
    //      $this->{$property} = $saved[$property];
    //    }
   //   }
    }
  }

  
  protected function  loadFromDb() {
    $select = db_select('ldap_authorization', 'ldap_authorization');
    $select->fields('ldap_authorization');
    $select->condition('ldap_authorization.mapping_id',  $this->mappingID);

    $mapping = $select->execute()->fetchObject();

    if (!$mapping) {
      $this->inDatabase = FALSE;
      return;
    }

    $this->sid = $mapping->sid;
    $this->consumerType = $mapping->consumer_type;
    $this->consumerModule = $mapping->consumer_module;
    $this->description = $mapping->description;
    $this->status = (bool)$mapping->status;
    $this->onlyApplyToLdapAuthenticated  = (bool)(@$mapping->only_ldap_authenticated);

    $this->deriveFromDn  = (bool)(@$mapping->derive_from_dn);
    $this->deriveFromDnAttr = $mapping->derive_from_dn_attr;

    $this->deriveFromAttr  = (bool)($mapping->derive_from_attr);
    $this->deriveFromAttrAttr =  $this->linesToArray($mapping->derive_from_attr_attr);

    $this->deriveFromEntry  = (bool)(@$mapping->derive_from_entry);
    $this->deriveFromEntryEntries = $this->linesToArray($mapping->derive_from_entry_entries);
    $this->deriveFromEntryAttr = $mapping->derive_from_entry_attr;

    $this->mappings = $this->pipeListToArray($mapping->mappings);
    $this->useMappingsAsFilter  = (bool)(@$mapping->use_filter);

    $this->synchToLdap = (bool)(@$mapping->synch_to_ldap);
    $this->synchOnLogon = (bool)(@$mapping->synch_on_logon);
    $this->synchManually = (bool)(@$mapping->synch_manually);
    $this->regrantLdapProvisioned = (bool)(@$mapping->regrant_ldap_provisioned);
    $this->revokeLdapProvisioned = (bool)(@$mapping->revoke_ldap_provisioned);
    $this->revokeNonLdapProvisioned = (bool)(@$mapping->revoke_non_ldap_provisioned);
    $this->createTargets = (bool)(@$mapping->create_targets);

    
  }
  /**
   * Destructor Method
   */
  function __destruct() {

  }


  protected $_mid;
  protected $_sid;
  protected $_new;

  protected $saveable = array(
    'mappingID',
    'sid',
    'consumerType',
    'consumerModule',
    'description',
    'status',
    'onlyApplyToLdapAuthenticated',
    'deriveFromDn',
    'deriveFromDnAttr',
    'deriveFromAttr',
    'deriveFromAttrAttr',
    'deriveFromEntry',
    'deriveFromEntryEntries',
    'deriveFromEntryAttr',
    'mappings',
    'useMappingsAsFilter',
    'synchToLdap',
    'synchOnLogon',
    'synchManually',
    'revokeLdapProvisioned',
    'revokeNonLdapProvisioned',
    'createTargets',
    'regrantLdapProvisioned',
    
  );
  
  
    protected function linesToArray($lines) {
    $lines = trim($lines);

     if ($lines) {
       $array = explode("\n", $lines);
     }
     else {
       $array = array();
     }

     return $array;
  }  protected function pipeListToArray($mapping_list_txt) {
    $result_array = array();
    foreach ((trim($mapping_list_txt) ? explode("\n", trim($mapping_list_txt)) : array()) as $line) {
      if (count($mapping = explode('|', trim($line))) == 2) {
       $result_array[] = array(trim($mapping[0]), trim($mapping[1]));
      }
    }
    return $result_array;
  }
}




