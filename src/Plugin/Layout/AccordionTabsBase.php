<?php

namespace Drupal\mercury_editor_tabs\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\style_options\Plugin\Layout\StyleOptionLayoutPlugin;

/**
 * Defines an Accordion / Tabs base layout plugin.
 */
class AccordionTabsBase extends StyleOptionLayoutPlugin {

  /**
   * The item label (i.e. Tab or Accordion Item).
   *
   * @var string
   */
  protected $itemLabel = 'Group';

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pluginDefinition->setRegions($this->getConfiguration()['layout_regions']);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['layout_regions'] = $this->getConfiguration()['layout_regions'];
    foreach ($this->getConfiguration()['layout_regions'] as $region_name => $region_data) {
      $form['layout_regions'][$region_name]['label'] = [
        '#type' => 'hidden',
        '#default_value' => $region_data['label'],
      ];
    }
    return parent::buildConfigurationForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $parent = parent::defaultConfiguration();
    if (empty($this->configuration['layout_regions'])) {
      return $parent + [
        'layout_regions' => [
          'region_1' => ['label' => $this->itemLabel . ' 1'],
          'region_2' => ['label' => $this->itemLabel . ' 2'],
        ],
      ];
    }
    return $parent;
  }

  /**
   * {@inheritDoc}
   */
  public function build(array $regions) {
    $this->pluginDefinition->setRegions($this->getConfiguration()['layout_regions']);
    $build = parent::build($regions);
    return $build;
  }

  /**
   * {@inheritDoc}
   */
  public function processForm(array $element, FormStateInterface $form_state) {
    $this->pluginDefinition->setRegions($this->getConfiguration()['layout_regions']);
    return parent::processForm($element, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->pluginDefinition->setRegions($this->getConfiguration()['layout_regions']);
    return parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  protected function getRegionContextDefinitions() {
    $this->pluginDefinition->setRegions($this->getConfiguration()['layout_regions']);
    return parent::getRegionContextDefinitions();
  }

}
