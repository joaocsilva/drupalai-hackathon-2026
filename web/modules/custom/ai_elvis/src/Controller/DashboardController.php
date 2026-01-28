<?php

namespace Drupal\ai_elvis\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

/**
 * Dashboard controller
 */
class DashboardController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function content() {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'publication')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->accessCheck(TRUE);

    $nids = $query->execute();
    $nodes = Node::loadMultiple($nids);

    $publications = [];
    foreach ($nodes as $node) {
      $publications[] = [
        'title' => $node->getTitle(),
        'summary' => $node->hasField('field_description') ? $node->get('field_description')->value : '',
        'author' => $node->getOwner()->getDisplayName(),
        'date' => date('M d, Y', $node->getCreatedTime()),
        'image_url' => $node->hasField('field_featured_image') && !$node->get('field_featured_image')->isEmpty()
          ? \Drupal::service('file_url_generator')->generateAbsoluteString($node->get('field_featured_image')->entity->getFileUri())
          : '',
      ];
    }

    return [
      '#theme' => 'dashboard',
      '#publications' => $publications,
      '#attached' => [
        'library' => [
          'ai_elvis/dashboard',
        ],
      ],
    ];
  }

}
