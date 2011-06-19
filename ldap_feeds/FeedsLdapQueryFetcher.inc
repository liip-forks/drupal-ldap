<?php
// $Id:  $

/**
 * @file
 * FeedsLdapQueryFetcher
 */

define('LDAP_FEEDS_QUERY_FETCHER_MULTI_COMMA', 1);
define('LDAP_FEEDS_QUERY_FETCHER_MULTI_SHOW_FIRST', 2);
define('LDAP_FEEDS_QUERY_FETCHER_MULTI_IGNORE', 3);
define('LDAP_FEEDS_QUERY_FETCHER_MULTI_ARRAY', 4);
define('LDAP_FEEDS_QUERY_FETCHER_MULTI_DEFAULT', 3);


/**
 * RFeedsLdapQueryFetcherResult
 */
class FeedsLdapQueryFetcherResult extends FeedsFetcherResult {
  public $basedn = array();  // should default to servers basedn
  public $sid;
  public $filter;
  public $attributes = array();
  public $sizelimit = LDAP_FEEDS_FETCHER_SIZELIMIT;
  protected $LdapServer;
  public $ldap_result;

  /**
   * Constructor.
   */
  public function __construct($source_config) {

    $this->sid = isset($source_config['sid']) ? $source_config['sid'] : FALSE;
    $this->filter = isset($source_config['filter']) ? $source_config['filter'] : FALSE ;
    $this->attributes = (isset($source_config['attributes']) && $source_config['attributes']) ? $source_config['attributes'] : LDAP_FEEDS_FETCHER_ATTRIBUTES;
    $this->sizelimit = isset($source_config['sid']) ? $source_config['sizelimit'] : LDAP_FEEDS_FETCHER_SIZELIMIT;

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
    $this->basedn = (isset($source_config['basedn'])) ? $source_config['basedn'] : FALSE;
    if ($this->basedn) {
      $this->basedn = explode("\n", $this->basedn);
    }
    parent::__construct('');
    $this->ldap_result = $this->getRaw();
    return $this->ldap_result;
  }

  /**
   * Overrides parent::getRaw();
   */
  public function getRaw() {
    $this->LdapServer = ldap_servers_get_servers($this->sid, 'enabled', TRUE, TRUE);
    $tokens = array('!sid' => $this->LdapServer->sid, '!server_admin' => '<a href="' . LDAP_SERVERS_INDEX_BASE_PATH . '">LDAP Servers Admin</a>');

    if (! $this->LdapServer || !is_object($this->LdapServer) ) {
      throw new Exception(t('LDAP Server !sid not found, please check ldap server admin page: !server_admin', $tokens));
    }

    if ((bool)$this->LdapServer->status == FALSE) {
      throw new Exception(t('LDAP Server !sid not enabled, please check ldap server admin page: !server_admin', $tokens));
    }

    if (!$this->basedn || !is_array($this->basedn) || count($this->basedn) == 0 ) {
      $this->basedns = $this->LdapServer->basedn;
    }

    if (!$this->filter) {
      throw new Exception(t('LDAP Server query filter empty.', $tokens));
    }

    $connect = $this->LdapServer->connect();
    if ($connect != LDAP_SUCCESS) {
      $tokens['errorMsg'] = $this->LdapServer->errorMsg();
      throw new Exception(t('LDAP Server !sid failed to connect, with error message: !errorMsg', $tokens));
    }

    $bind = $this->LdapServer->bind();
    if ($bind != LDAP_SUCCESS) {
      $tokens['errorMsg'] = $this->LdapServer->errorMsg();
      throw new Exception(t('LDAP Server !sid failed to bind, with error message: !errorMsg', $tokens));
    }

    $results = array();
    foreach ($this->basedn as $i => $basedn) {
      $result = $this->LdapServer->search($basedn, $this->filter, $this->attributes, 0, $this->sizelimit);
      $results = array_merge($results, $result);
    }

    return $results;
  }



}

/**
 * Fetches data via LDAP Query.
 */
class FeedsLdapQueryFetcher extends FeedsFetcher {

  /**
   * Implements FeedsFetcher::fetch().
   */
  public function fetch(FeedsSource $source) {
    $source_config = $source->getConfigFor($this);
    $result = new FeedsLdapQueryFetcherResult($source_config);
    return $result;
  }



  /**
   * Override parent::configForm().
   */
  public function sourceForm($source_config) {
    $servers = ldap_servers_get_servers(NULL, 'enabled');
    foreach ($servers as $sid => $server) {
      $server_options[$sid] = $server->name;
    }
    $sid = isset($source_config['sid']) ? $source_config['sid'] : NULL;
    $basedn = isset($source_config['basedn']) ? $source_config['basedn'] : NULL;
    $filter = isset($source_config['filter']) ? $source_config['filter'] : NULL;
    $attributes = isset($source_config['attributes']) ? $source_config['attributes'] : explode(',', LDAP_FEEDS_FETCHER_ATTRIBUTES);
    $sizelimit = isset($source_config['sizelimit']) ? $source_config['sizelimit'] : LDAP_FEEDS_FETCHER_SIZELIMIT;

    $form = array();

    $form['sid'] = array(
      '#type' => 'select',
      '#title' => t('LDAP Server'),
      '#required' => TRUE,
      '#default_value' =>  $sid,
      '#options' => $server_options,
    );


    $form['overview'] = array(
      '#markup' => t("This fetcher will perform one search for each base dn entered below.  The
    basedn, filter, and attributes below are passed directly into the ldap_search() function
    (see http://php.net/ldap_search).  Use its documentation for syntax details."),
      );


    $form['basedn'] = array(
      '#type' => 'textarea',
      '#title' => t('Base DNs'),
      '#description' => t('Base DN for Query.  Leave empty to use base dns associated with server. Separate multiple base dns with a return.'),
      '#default_value' => $basedn,
      '#maxlength' => NULL,
      '#required' => FALSE,
    );


    $resources[] = l(t('centos.org ldap'), 'http://www.centos.org/docs/5/html/CDS/ag/8.0/Finding_Directory_Entries-LDAP_Search_Filters.html');
    $resources[] = l(t('postini.com'), 'http://www.postini.com/webdocs/gads/admin/prep_ldap.html');
    $resources = theme_item_list(array('items' => $resources, 'title' => 'Filter Syntax Resources', 'type' => 'ul', 'attributes' => array()));

    $form['filter'] = array(
      '#type' => 'textfield',
      '#title' => t('Filter'),
      '#description' => t('Filter for Query.') . $resources,
      '#default_value' => $filter,
      '#maxlength' => NULL,
      '#required' => TRUE,
    );

    if ($sid) {
      $server_basedn = $basedn;
      $form['basedn']['#description'] .= '<br/>' . t('Current!sid server base dns are', array('!sid' => $sid)) . '<br/><code><pre>' .
        $server_basedn . '</pre></code>';
    }

    $form['attributes'] = array(
      '#type' => 'textfield',
      '#title' => t('Attributes'),
      '#description' => t('Attributes to return from Query.  Comma separated attribute names such as <code>"mail", "sn", "cn"</code>. Leave empty to return all attributes.'),
      '#default_value' => $attributes,
      '#maxlength' => NULL,
      '#required' => FALSE,
    );

    $form['sizelimit'] = array(
      '#type' => 'textfield',
      '#title' => t('Size Limit'),
      '#description' => t('Max number of entries to return.  Set to 0 for no maximum.'),
      '#default_value' => $sizelimit,
      '#maxlength' => NULL,
      '#required' => FALSE,
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
