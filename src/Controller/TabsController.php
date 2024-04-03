<?php

namespace Drupal\mercury_editor_tabs\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RemoveCommand;
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
 * Class ReorderController.
 *
 * Reorders the components of a Layout Paragraphs Layout.
 */
class TabsController extends ControllerBase {

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
   * Edits a tab title.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object containing a "tab_uuid" and "tab_title" POST parameters.
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   The Layout Paragraphs Layout object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function editTabTitle(Request $request, LayoutParagraphsLayout $layout_paragraphs_layout, string $component_uuid, string $tab_id) {
    $this->setLayoutParagraphsLayout($layout_paragraphs_layout);
    $component = $layout_paragraphs_layout->getComponentByUuid($component_uuid);
    $paragraph = $component->getEntity();
    $behavior_settings = $paragraph->getAllBehaviorSettings();
    if (!empty($behavior_settings['layout_paragraphs']['config']['tabs'][$tab_id]) && $request->request->get('value')) {
      $behavior_settings['layout_paragraphs']['config']['tabs'][$tab_id]['detail']['label'] = $request->request->get('value');
      $paragraph->setAllBehaviorSettings($behavior_settings);
      $paragraph->setNeedsSave(TRUE);
      $layout_paragraphs_layout->setComponent($paragraph);
      $this->tempstore->set($layout_paragraphs_layout);
    }
    return new AjaxResponse();
  }

  public function removeTab(Request $request, LayoutParagraphsLayout $layout_paragraphs_layout, string $component_uuid, string $tab_id) {
    $component = $layout_paragraphs_layout->getComponentByUuid($component_uuid);
    $paragraph = $component->getEntity();
    $behavior_settings = $paragraph->getAllBehaviorSettings();
    if (!empty($behavior_settings['layout_paragraphs']['config']['tabs'][$tab_id]) && $request->request->get('delete') == $component_uuid) {
      $section = $layout_paragraphs_layout->getLayoutSection($paragraph);
      $components = $section->getComponentsForRegion($tab_id) ?? [];
      foreach ($components as $component) {
        $layout_paragraphs_layout->deleteComponent($component->getEntity()->uuid(), TRUE);
      }
      unset($behavior_settings['layout_paragraphs']['config']['tabs'][$tab_id]);
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

  public function addTab(LayoutParagraphsLayout $layout_paragraphs_layout, string $component_uuid) {
    $component = $layout_paragraphs_layout->getComponentByUuid($component_uuid);
    $paragraph = $component->getEntity();
    $behavior_settings = $paragraph->getAllBehaviorSettings();
    if (!empty($behavior_settings['layout_paragraphs']['config']['tabs'])) {
      $tab_id = 1;
      $label = 'Tab ' . count($behavior_settings['layout_paragraphs']['config']['tabs']) + 1;
      while (!empty($behavior_settings['layout_paragraphs']['config']['tabs']['tab_' . $tab_id])) {
        $tab_id++;
      }
      $behavior_settings['layout_paragraphs']['config']['tabs']['tab_' . $tab_id] = [
        'detail' => [
          'label' => $label,
        ],
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

  public function reorderTabs(Request $request, LayoutParagraphsLayout $layout_paragraphs_layout, string $component_uuid) {
    $component = $layout_paragraphs_layout->getComponentByUuid($component_uuid);
    $paragraph = $component->getEntity();
    $behavior_settings = $paragraph->getAllBehaviorSettings();
    $order = Json::decode($request->request->get('order'));
    if (!empty($behavior_settings['layout_paragraphs']['config']['tabs']) && !empty($order)) {
      $tabs = $behavior_settings['layout_paragraphs']['config']['tabs'];
      $new_tabs = [];
      foreach ($order as $tab_id) {
        if (!empty($tabs[$tab_id])) {
          $new_tabs[$tab_id] = $tabs[$tab_id];
        }
      }
      $behavior_settings['layout_paragraphs']['config']['tabs'] = $new_tabs;
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
   * Reorders a Layout Paragraphs Layout's components.
   *
   * Expects an two-dimmensional array of components in the "components" POST
   * parameter with key/value pairs for "uuid", "parent_uuid", and "region".
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object containing a "components" POST parameter.
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   The Layout Paragraphs Layout object.
   */
  public function build(Request $request, LayoutParagraphsLayout $layout_paragraphs_layout) {
    if ($ordered_components = Json::decode($request->request->get("components"))) {
      $layout_paragraphs_layout->reorderComponents($ordered_components);
      $this->tempstore->set($layout_paragraphs_layout);
    }
    // If invoked via ajax, no need to re-render the builder UI.
    if ($this->isAjax()) {
      return new AjaxResponse();
    }
    return [
      '#type' => 'layout_paragraphs_builder',
      '#layout_paragraphs_layout' => $layout_paragraphs_layout,
    ];
  }

}
