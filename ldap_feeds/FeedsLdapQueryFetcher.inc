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
  public $basedn = array();  // should default to servers basedn
  public $sid;
  public $filter;
  public $attributes = array();
  public $sizelimit = LDAP_FEEDS_FETCHER_SIZELIMIT;
  public $LdapServer;
  public $ldap_result;

  /**
   * Constructor.
   */
  public function __construct($source_config) {

    $this->sid = isset($source_config['sid']) ? $source_config['sid'] : FALSE;
    $this->filter = isset($source_config['filter']) ? $source_config['filter'] : FALSE ;
    $this->attributes = isset($source_config['attributes']) ? $source_config['attributes'] : array();
    $this->sizelimit = isset($source_config['sid']) ? $source_config['sizelimit'] : 0;

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

    $this->basedn = (isset($source_config['basedn'])) ? $source_config['basedn'] : FALSE;
    if ($this->basedn) {
      $this->basedn = explode("\n", $this->basedn);
    }
    parent::__construct('');
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
      $result = $this->LdapServer->search($basedn, $this->filter, $this->attributes, 0, $this->sizelimit);  // , $this->attributes
      $results = array_merge($results, $result);
    }

    return $this->sanitizeRaw($results); // @todo ? should sanitizeRaw be applied to this array? $this->sanitizeRaw($results);
  }

  /**
   * Override parent::configDefaults().
   */
  public function configDefaults() {
    return array(
      'sid' => NULL,
      'basedn' => NULL,
      'attributes' => '"cn", "dn", "mail"',
      'filter' => NULL,
      'sizelimit' => 100,
      );
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
    $result = new FeedsLdapQueryFetcherResult($this->config);
    return $result;
  }

  /**
   * Clear caches.
   */
  public function clear(FeedsSource $source) {

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
    basedn, filter, and attributes below are passed directly into the ldap_search() function
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
      $form['basedn']['#description'] .= '<br/>' . t('Current!sid server base dns are', array('!sid' => $this->config['sid'])) . '<br/><code><pre>' .
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


}
