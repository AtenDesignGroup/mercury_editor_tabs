<?php

namespace Drupal\mercury_editor_tabs\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\style_options\Plugin\Layout\StyleOptionLayoutPlugin;

/**
 * Defines a Tabs layout plugin.
 */
class Tabs extends StyleOptionLayoutPlugin {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pluginDefinition->setRegions($this->buildRegions($this->getConfiguration()['tabs']));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $parent = parent::defaultConfiguration();
    if (empty($this->configuration['tabs'])) {
      return $parent + [
        'tabs' => [
          'tab_1' => ['detail' => ['label' => 'Tab 1'], 'weight' => 1],
          'tab_2' => ['detail' => ['label' => 'Tab 2'], 'weight' => 2],
        ],
      ];
    }
    return $parent;
  }

  /**
   * {@inheritDoc}
   */
  public function build(array $regions) {
    $this->pluginDefinition->setRegions($this->buildRegions($this->getConfiguration()['tabs']));
    $build = parent::build($regions);
    return $build;
  }

  /**
   * Builds regions from tab configuration.
   *
   * @param array $tabs
   *   Tabs.
   *
   * @return array
   *   Regions.
   */
  protected function buildRegions(array $tabs) : array {
    $regions = [];
    foreach ($tabs as $tab_id => $tab) {
      $regions[$tab_id] = [
        'label' => $tab['detail']['label'],
      ];
    }
    return $regions;
  }

}
