<?php

namespace Drupal\ai_elvis\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Cache\Cache;

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

    $service = \Drupal::service('plugin.manager.node_measure');
    /** @var \Drupal\ai_elvis\Plugin\NodeMeasure\PossibleBacklinks $backLinks */
    $backLinks = $service->createInstance('possible_backlinks');
    /** @var \Drupal\ai_elvis\Plugin\NodeMeasure\MetatagsPresence $metatags */
    $metatags = $service->createInstance('metatags_presence');
    /** @var \Drupal\ai_elvis\Plugin\NodeMeasure\SchemaPresence $schema */
    $schema = $service->createInstance('schema_presence');
    /** @var \Drupal\ai_elvis\Plugin\NodeMeasure\TaxonomyPresence $taxonomy */
    $taxonomy = $service->createInstance('taxonomy_presence');

    $publications = [];
    foreach ($nodes as $node) {
      $image_url = '';
      if (!$node->get('field_featured_image')->isEmpty()) {
        $media = $node->get('field_featured_image')->entity;
        if ($media && !$media->get('field_media_image')->isEmpty()) {
          $file = $media->get('field_media_image')->entity;
          if ($file) {
            $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
          }
        }
      }
      $nodeMetatags = $metatags->measureNode($node);
      $nodeBackLinks = $backLinks->measureNode($node);
      $nodeSchema = $schema->measureNode($node);
      $nodeTaxonomy = $taxonomy->measureNode($node);
      $finalScore = (($nodeMetatags['score'] + $nodeBackLinks['score'] + $nodeSchema['score'] + $nodeTaxonomy['score']) / 4) * 100;
      $publications[] = [
        'title' => $node->getTitle(),
        'summary' => $node->hasField('field_description') ? $node->get('field_description')->value : '',
        'author' => $node->getOwner()->getDisplayName(),
        'date' => date('M d, Y', $node->getCreatedTime()),
        'score' => (int) $finalScore,
        'image_url' => $image_url,
        'metatags' => $nodeMetatags['details'],
        'backlinks' => $nodeBackLinks['details'],
        'schema' => $nodeSchema['details'],
        'taxonomy' => $nodeTaxonomy['details'],
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
      '#cache' => [
        'tags' => Cache::mergeTags(['node_list:publication'], []),
      ],
    ];
  }

}
