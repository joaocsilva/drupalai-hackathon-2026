<?php

declare(strict_types=1);

namespace Drupal\ai_elvis\Plugin\NodeMeasure;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai_elvis\Attribute\NodeMeasure;
use Drupal\ai_elvis\NodeMeasurePluginBase;
use Drupal\node\NodeInterface;

/**
 * Plugin that checks the presence of metatags on a node.
 */
#[NodeMeasure(
  id: 'taxonomy_presence',
  label: new TranslatableMarkup('Tags'),
  description: new TranslatableMarkup('Check for the presence of taxonomy.'),
)]
final class TaxonomyPresence extends NodeMeasurePluginBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function measureNode(NodeInterface $node): array {
    $metadata = new CacheableMetadata();
    $metadata->addCacheableDependency($node);
    $build = [];
    $metadata->applyTo($build);

    // Get the metatags field value.
    $taxonomy_field = $node->get('field_tags');

    // Check if the field is empty.
    if ($taxonomy_field->isEmpty()) {
      $build = [
        '#markup' => $this->t('<p><strong>Warning:</strong> No tags are assigned to this node. Please add tags to improve SEO.</p>'),
      ];
      $score = 0.0;
    }
    else {
      $build = [
        '#markup' => $this->t('<p><strong>Success:</strong> The node is properly tagged with taxonomy terms.</p>'),
      ];
      $score = 1.0;
    }

    return [
      'score' => $score,
      'details' => $build,
    ];
  }

}
