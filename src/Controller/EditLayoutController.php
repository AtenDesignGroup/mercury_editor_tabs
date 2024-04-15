<?php

namespace Drupal\mercury_editor_tabs\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\layout_paragraphs\Ajax\LayoutParagraphsEventCommand;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutRefreshTrait;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository;

/**
 * Defines a EditLayoutController for editing tabs and accordions.
 */
class EditLayoutController extends ControllerBase {

  use AjaxHelperTrait;
  use LayoutParagraphsLayoutRefreshTrait;

  /**
   * The tempstore service.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository
   */
  protected $tempstore;

  /**
   * {@inheritDoc}
   */
  public function __construct(LayoutParagraphsLayoutTempstoreRepository $tempstore) {
    $this->tempstore = $tempstore;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_paragraphs.tempstore_repository')
    );
  }

  /**
   * Edits a group title.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object containing a "uuid" and "title" POST parameters.
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   The Layout Paragraphs Layout object.
   * @param string $component_uuid
   *   The component UUID.
   * @param string $region_id
   *   The group ID.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function editLabel(
    Request $request,
    LayoutParagraphsLayout $layout_paragraphs_layout,
    string $component_uuid,
    string $region_id
  ) {
    $this->setLayoutParagraphsLayout($layout_paragraphs_layout);
    $component = $layout_paragraphs_layout->getComponentByUuid($component_uuid);
    $paragraph = $component->getEntity();
    $behavior_settings = $paragraph->getAllBehaviorSettings();
    if (!empty($behavior_settings['layout_paragraphs']['config']['layout_regions'][$region_id]) && $request->request->get('label')) {
      $behavior_settings['layout_paragraphs']['config']['layout_regions'][$region_id] = [
        'label' => $request->request->get('label'),
      ];
      $paragraph->setAllBehaviorSettings($behavior_settings);
      $paragraph->setNeedsSave(TRUE);
      $layout_paragraphs_layout->setComponent($paragraph);
      $this->tempstore->set($layout_paragraphs_layout);
    }
    return new AjaxResponse();
  }

  /**
   * Removes a region from a the layout.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   The Layout Paragraphs Layout object.
   * @param string $component_uuid
   *   The component UUID.
   * @param string $region_id
   *   The region ID.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response.
   */
  public function removeRegion(Request $request, LayoutParagraphsLayout $layout_paragraphs_layout, string $component_uuid, string $region_id) {
    $component = $layout_paragraphs_layout->getComponentByUuid($component_uuid);
    $paragraph = $component->getEntity();
    $behavior_settings = $paragraph->getAllBehaviorSettings();
    if (!empty($behavior_settings['layout_paragraphs']['config']['layout_regions'][$region_id]) && $request->request->get('delete') == $component_uuid) {
      $section = $layout_paragraphs_layout->getLayoutSection($paragraph);
      $components = $section->getComponentsForRegion($region_id) ?? [];
      foreach ($components as $component) {
        $layout_paragraphs_layout->deleteComponent($component->getEntity()->uuid(), TRUE);
      }
      unset($behavior_settings['layout_paragraphs']['config']['layout_regions'][$region_id]);
      $paragraph->setAllBehaviorSettings($behavior_settings);
      $paragraph->setNeedsSave(TRUE);
      $layout_paragraphs_layout->setComponent($paragraph);
      $this->tempstore->set($layout_paragraphs_layout);

      $this->setLayoutParagraphsLayout($layout_paragraphs_layout);
      $response = new AjaxResponse();
      if ($this->needsRefresh()) {
        return $this->refreshLayout($response);
      }
      $rendered_item = [
        '#type' => 'layout_paragraphs_builder',
        '#layout_paragraphs_layout' => $this->layoutParagraphsLayout,
        '#uuid' => $component_uuid,
      ];
      $response->addCommand(new ReplaceCommand("[data-uuid={$component_uuid}]", $rendered_item));
      $response->addCommand(new LayoutParagraphsEventCommand($this->layoutParagraphsLayout, $component_uuid, 'component:update'));
      return $response;

    }
    return new AjaxResponse();
  }

  /**
   * Adds a region to the layout.
   *
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   The Layout Paragraphs Layout object.
   * @param string $component_uuid
   *   The component UUID.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response.
   */
  public function addRegion(LayoutParagraphsLayout $layout_paragraphs_layout, string $component_uuid) {
    $component = $layout_paragraphs_layout->getComponentByUuid($component_uuid);
    $paragraph = $component->getEntity();
    $behavior_settings = $paragraph->getAllBehaviorSettings();
    if (!empty($behavior_settings['layout_paragraphs']['config']['layout_regions'])) {
      $region_id = 1;
      $label_prefix = $behavior_settings['layout_paragraphs']['layout'] == 'me_tabs'
        ? 'Tab '
        : 'Accordion Item ';
      $label = $label_prefix . count($behavior_settings['layout_paragraphs']['config']['layout_regions']) + 1;
      while (!empty($behavior_settings['layout_paragraphs']['config']['layout_regions']['region_' . $region_id])) {
        $region_id++;
      }
      $behavior_settings['layout_paragraphs']['config']['layout_regions']['region_' . $region_id] = [
        'label' => $label,
      ];
      $paragraph->setAllBehaviorSettings($behavior_settings);
      $paragraph->setNeedsSave(TRUE);
      $layout_paragraphs_layout->setComponent($paragraph);
      $this->tempstore->set($layout_paragraphs_layout);

      $this->setLayoutParagraphsLayout($layout_paragraphs_layout);
      $response = new AjaxResponse();
      if ($this->needsRefresh()) {
        return $this->refreshLayout($response);
      }
      $rendered_item = [
        '#type' => 'layout_paragraphs_builder',
        '#layout_paragraphs_layout' => $this->layoutParagraphsLayout,
        '#uuid' => $component_uuid,
      ];
      $response->addCommand(new ReplaceCommand("[data-uuid={$component_uuid}]", $rendered_item));
      $response->addCommand(new LayoutParagraphsEventCommand($this->layoutParagraphsLayout, $component_uuid, 'component:update'));
      return $response;

    }
    return new AjaxResponse();
  }

  /**
   * Reorders regions in a layout.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   The Layout Paragraphs Layout object.
   * @param string $component_uuid
   *   The component UUID.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response.
   */
  public function reorderRegions(Request $request, LayoutParagraphsLayout $layout_paragraphs_layout, string $component_uuid) {
    $component = $layout_paragraphs_layout->getComponentByUuid($component_uuid);
    $paragraph = $component->getEntity();
    $behavior_settings = $paragraph->getAllBehaviorSettings();
    $order = Json::decode($request->request->get('order'));
    if (!empty($behavior_settings['layout_paragraphs']['config']['layout_regions']) && !empty($order)) {
      $layout_regions = $behavior_settings['layout_paragraphs']['config']['layout_regions'];
      $reordered_regions = [];
      foreach ($order as $region_id) {
        if (!empty($layout_regions[$region_id])) {
          $reordered_regions[$region_id] = $layout_regions[$region_id];
        }
      }
      $behavior_settings['layout_paragraphs']['config']['layout_regions'] = $reordered_regions;
      $paragraph->setAllBehaviorSettings($behavior_settings);
      $paragraph->setNeedsSave(TRUE);
      $layout_paragraphs_layout->setComponent($paragraph);
      $this->tempstore->set($layout_paragraphs_layout);

      $this->setLayoutParagraphsLayout($layout_paragraphs_layout);
      $response = new AjaxResponse();
      if ($this->needsRefresh()) {
        return $this->refreshLayout($response);
      }
      $rendered_item = [
        '#type' => 'layout_paragraphs_builder',
        '#layout_paragraphs_layout' => $this->layoutParagraphsLayout,
        '#uuid' => $component_uuid,
      ];
      $response->addCommand(new ReplaceCommand("[data-uuid={$component_uuid}]", $rendered_item));
      $response->addCommand(new LayoutParagraphsEventCommand($this->layoutParagraphsLayout, $component_uuid, 'component:update'));
      return $response;
    }
    return new AjaxResponse();
  }

}
