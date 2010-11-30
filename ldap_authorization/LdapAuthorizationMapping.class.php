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
    if (!is_scalar($_mid)) {
      return;      
    }
    $this->consumerType = $_consumer_type;
    $this->consumerModule = $_consumer_module;
    $this->mappingID = $_mid;
    $this->sid = $_sid;
    if ($_new) {
      $this->inDatabase = FALSE;
      return;
    }
    $this->inDatabase = TRUE;
    $saved = variable_get("ldap_authorization_map_". $this->mappingID, array());
    foreach ($this->saveable as $property) {
      if (isset($saved[$property])) {
        $this->{$property} = $saved[$property];
      }
      
    }
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
  
}


