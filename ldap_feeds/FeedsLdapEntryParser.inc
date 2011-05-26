<?php

/**
 * @file
 *
 * Provides the Parser for an ldap entry array.
 */


class FeedsLdapEntryParser extends FeedsParser {
  public $ldap_result;

   /**
   * Implements FeedsParser::parse().
   */
  public function parse(FeedsSource $source, FeedsFetcherResult $fetcher_result) {
    $this->source_config = $source->getConfigFor($this);
    if (empty($this->source_config)) {
      $this->source_config = $this->config;
    }
    $this->parserResult = new FeedsParserResult();
    $mappings = $source->importer->processor->config['mappings'];
    $this->mappings = $mappings;

    // Set link.
    $fetcher_config = $source->getConfigFor($source->importer->fetcher);
    $this->parserResult->link = $fetcher_config['source'];
    $this->ldap_result = $fetcher_result->ldap_result;
    $result = new FeedsParserResult();

    $items_num = count($fetcher_result->ldap_result); // $this->parserResult['count'];

    for ($i = 0; $i < $items_num - 1; $i++) {
      $item = array();
      $data = $fetcher_result->ldap_result[$i];
      $parsedItem = $variables = array();
      foreach ($this->source_config['sources'] as $source => $query) {
        if (isset($data[$query])) {
          if (!is_array($data[$query])) {
           $variables['$' . $this->mappings[$source]] = $data[$query];
           }
           else {
             $variables['$' . $this->mappings[$source]] = '';
           }
           $parsedItem[$source] = $data[$query];
        }
        $parsedItem['guid'] = $data['dn'];
      }
      $this->parserResult->items[] = $parsedItem;
    }

    // Release parser.
    unset($parser);
    return $result;
  }


  /**
   * Source form.
   */
  public function sourceForm($source_config) {
    $form = array();
    $mappings = feeds_importer($this->id)->processor->config['mappings'];
    if (empty($source_config)) {
      $source_config = $this->config;
    }

    // Add extensions that might get importerd.
    $fetcher = feeds_importer($this->id)->fetcher;

    $variables = array();
    foreach ($mappings as $source => $target) {
      $form['sources'][$source] = array(
        '#type' => 'textfield',
        '#title' => check_plain($target),
        '#description' => t('Ldap Attribute Name'),
        '#default_value' => isset($source_config['sources'][$source]) ? $source_config['sources'][$source] : '',
        '#maxlength' => 1024,
        '#size' => 80,
      );
      if (!empty($variables)) {
        $form['sources'][$source]['#description'] .= '<br>' . t('The variables '. implode(', ', $variables). ' are available for replacement.');
      }
      $variables[] = '$' . $target;
    }
    return $form;
  }


  /**
   * Override parent::sourceFormValidate().
   */
  public function configFormValidate(&$values) {
    $this->setConfig(array('sources' => $values));
    $this->save();
   }


  /**
  * Override parent::getMappingSources().
  */
  public function getMappingSources() {
    $attributes = feeds_importer($this->id)->fetcher->config['attributes'];
    $attributes = explode(',', $attributes);
    foreach ($attributes as $i => $attribute) {
      $attribute = trim($attribute);
      $attribute = trim($attribute, '"');
      $sources[$attribute] = array(
        'name' => $attribute . t(' LDAP Attribute'),
        'description' => t('populate this field with %attr attribute data.', array('%attr' => $attribute)),
        );
    }
    $sources = $sources + parent::getMappingSources();
    return $sources;
  }

  /**
   * Override parent::configForm().
   */
  public function configForm(&$form_state) {
    $form = $this->sourceForm($this->config);
    return $form;
  }
  public function sourceDefaults() {
    return array();
  }


  /**
   * Define defaults.
   */
  public function configDefaults() {
    return array();
  }


}
