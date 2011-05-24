<?php
// $Id: FeedsLdapQueryFetcher.inc,v 1.29.2.2 2010/10/26 19:03:14 yhahn Exp $

/**
 * @file
 * Home of the FeedsLdapQueryFetcher and related classes.
 */


/**
 * Result of FeedsLdapQueryFetcher::fetch().
 */
class FeedsLdapQueryFetcherResult extends FeedsFetcherResult {
  protected $basedn = array();  // should default to servers basedn
  protected $sid;
  protected $filter;
  protected $attributes = array();
  protected $sizelimit = LDAP_FEEDS_FETCHER_SIZELIMIT;
  protected $LdapServer;
  /**
   * Constructor.
   */
  public function __construct($source_config) {
    // @todo, get $query from $source_config
    dpm('source_config');
    dpm($source_config);

    $this->sid = isset($query['sid']) ? $query['sid'] : FALSE;
    $this->filter = isset($query['filter']) ? $query['filter'] : FALSE ;
    $this->attributes = isset($query['attributes']) ? $query['attributes'] : array();
    if ($this->attributes) {
      $temp = $this->attributes;
      $this->attributes = array();
      foreach(explode(",", $temp) as $i => $element) {
        $element = trim($element,'"');
        $element = trim($element);
        $element = trim($element,'"');
        $this->attributes[] = $element;
      }
    }

    $this->basedn = (isset($query['base_dn'])) ? $query['base_dn'] : FALSE;
    if ($this->basedn) {
      $this->basedn = explode("\n", $this->basedn);
    }
   // dpm($this);
    parent::__construct('');
  }

  /**
   * Overrides parent::getRaw();
   */
  public function getRaw() {
   // dpm('getRaw'); dpm((array)$this);
    $this->LdapServer = ldap_servers_get_servers($this->sid, 'all', TRUE);

    $tokens = array('!sid' => $this->sid, '!server_admin' => '<a href="{LDAP_SERVERS_INDEX_BASE_PATH}">LDAP Servers Admin</a>');

    //@todo should the validation of constructor parameters be validated here or in constructor.
    // gives more room to alter/correct object if validated here rather than constructor
    if (! $this->LdapServer) {
       throw new Exception(t('LDAP Server !sid not found, please check ldap server admin page: !server_admin', $tokens));
    }

    if (!$this->LdapServer->status) {
       throw new Exception(t('LDAP Server !sid not enabled, please check ldap server admin page: !server_admin', $tokens));
    }

    if (!$this->basedn || !is_array($this->basedn) || count($this->basedn) == 0 ) {
      $this->base_dns = $this->LdapServer->basedn;
    }

    if (!$this->filter) {
      throw new Exception(t('LDAP Server query filter empty.', $tokens));
    }

    $connect = $this->LdapServer->connect();
    if ($connect != LDAP_SUCCESS) {
      $tokens['errorMsg'] = $this->LdapServer->errorMsg();
      throw new Exception(t('LDAP Server !sid failed to connect, with error message: !errorMsg', $tokens));
    }
    $results = array();
    foreach ($this->basedn as $i => $basedn) {
      $result = $this->LdapServer->search($this->filter, $basedn, NULL, $this->sizelimit);  // , $this->attributes
    }
    $results['count'] = count($results); // just keep with the ldap search results format, even if redundant
    return $results; // @todo ? should sanitizeRaw be applied to this array? $this->sanitizeRaw($results);
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
   * Clear caches.
   */
  public function clear(FeedsSource $source) {

  }

  /**
   * Override parent::configDefaults().
   */
  public function configDefaults() {
    return array(
      'sid' => NULL,
      'base_dn' => NULL,
      'filter' => NULL,
      'attributes' => NULL,
      'sizelimit' => $this->sizelimit,
    );
  }

  /**
   * Override parent::configForm().
   */
  public function configForm(&$form_state) {

    $servers = ldap_servers_get_servers(NULL, 'enabled');
    foreach ($servers as $sid => $server) {
      $server_options[$sid] = $server->name;
    }

    $form = array();

    $form['sid'] = array(
      '#type' => 'select',
      '#title' => t('LDAP Server'),
      '#required' => TRUE,
      '#default_value' => $this->config['sid'],
      '#options' => $server_options,
    );


    $form['overview'] = array(
      '#markup' => t("This fetcher will perform one search for each base dn entered below.  The
    base_dn, filter, and attributes below are passed directly into the ldap_search() function
    (see http://php.net/ldap_search).  Use its documentation for syntax details."),
      );


    $form['basedn'] = array(
      '#type' => 'textarea',
      '#title' => t('Base DNs'),
      '#description' => t('Base DN for Query.  Leave empty to use base dns associated with server. Separate multiple base dns with a return.'),
      '#default_value' => $this->config['basedn'],
      '#maxlength' => NULL,
      '#required' => FALSE,
    );


    $resources[] = l('centos.org ldap', 'http://www.centos.org/docs/5/html/CDS/ag/8.0/Finding_Directory_Entries-LDAP_Search_Filters.html');
    $resources[] = l('postini.com', 'http://www.postini.com/webdocs/gads/admin/prep_ldap.html');
    $resources = theme_item_list(array('items' => $resources, 'title' => 'Filter Syntax Resources', 'type' => 'ul', 'attributes' => array()));

    $form['filter'] = array(
      '#type' => 'textfield',
      '#title' => t('Filter'),
      '#description' => t('Filter for Query.') . $resources,
      '#default_value' => $this->config['filter'],
      '#maxlength' => NULL,
      '#required' => TRUE,
    );

    if ($this->config['sid']) {
      $server_basedn = join('<br/>', $servers[$this->config['sid']]->basedn);
      $form['base_dn']['#description'] .= '<br/>' . t('Current!sid server base dns are', array('!sid' => $this->config['sid'])) . '<br/><code><pre>' .
        $server_basedn . '</pre></code>';
    }

    $form['attributes'] = array(
      '#type' => 'textfield',
      '#title' => t('Attributes'),
      '#description' => t('Attributes to return from Query.  Comma separated attribute names such as <code>"mail", "sn", "cn"</code>. Leave empty to return all attributes.'),
      '#default_value' => $this->config['attributes'],
      '#maxlength' => NULL,
      '#required' => FALSE,
    );

    $form['sizelimit'] = array(
      '#type' => 'textfield',
      '#title' => t('Size Limit'),
      '#description' => t('Max number of entries to return.  Set to 0 for no maximum.'),
      '#default_value' => $this->config['sizelimit'],
      '#maxlength' => NULL,
      '#required' => FALSE,
    );
    return $form;
  }


  /**
   * @todo, what about sourceForm, sourceFormValidate, sourceSave, sourceDelete?
   * If all the configuration data for source is in the config form, are these needed.
   */



}
