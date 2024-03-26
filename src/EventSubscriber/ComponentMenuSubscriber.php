<?php

namespace Drupal\mercury_editor_tabs\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\layout_paragraphs\Event\LayoutParagraphsAllowedTypesEvent;

/**
 * Class definition for LayoutParagraphsAllowedTypesSubcriber.
 */
class ComponentMenuSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      LayoutParagraphsAllowedTypesEvent::EVENT_NAME => 'typeRestrictions',
    ];
  }

  /**
   * Restricts available types based on settings in layout.
   *
   * @param \Drupal\layout_paragraphs\Event\LayoutParagraphsAllowedTypesEvent $event
   *   The allowed types event.
   */
  public function typeRestrictions(LayoutParagraphsAllowedTypesEvent $event) {

    $layout = $event->getLayout();
    $parent_uuid = $event->getParentUuid();
    $types = $event->getTypes();

    if (!empty($parent_uuid)) {
      $parent_component = $layout->getComponentByUuid($parent_uuid);
      // Only a "tab" can go inside a "tabs" layout.
      if (!empty($parent_component) && $parent_component->getEntity()->bundle() == 'tabs') {
        $types = array_filter($types, function ($type) {
          return $type == 'tab';
        }, ARRAY_FILTER_USE_KEY);
        $event->setTypes($types);
        return;
      }
      // "Tabs" cannot go inside a "tab" layout.
      if (!empty($parent_component) && !empty($types['tabs']) && $parent_component->getEntity()->bundle() == 'tab') {
        unset($types['tabs']);
        $event->setTypes($types);
      }
    }
    if (!empty($types['tab'])) {
      unset($types['tab']);
    }
    $event->setTypes($types);
  }

}
