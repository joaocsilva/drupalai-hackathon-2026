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
  id: 'metatags_presence',
  label: new TranslatableMarkup('Meta Tags'),
  description: new TranslatableMarkup('Check for the presence of metatags.'),
)]
final class MetatagsPresence extends NodeMeasurePluginBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function measureNode(NodeInterface $node): array {
    $metadata = new CacheableMetadata();
    $metadata->addCacheableDependency($node);

    // Get the metatags field value.
    $metatags_field = $node->get('field_metatags');

    // Check if the field is empty.
    $empty_scores = [];
    if (!$metatags_field->isEmpty()) {
      // Get the metatags value and check if it contains any actual tags.
      $metatags_value = $metatags_field->getValue();
      $json_value = isset($metatags_value[0]['value']) ? json_decode($metatags_value[0]['value'], TRUE) : '{}';
      foreach (['description', 'title'] as $metatag) {
        if (isset($json_value[$metatag])) {
          $trimmed = trim($json_value[$metatag]);
          if (empty($trimmed)) {
            $empty_scores[] = $metatag;
          }
        }
      }
    }

    $build = [];
    $metadata->applyTo($build);
    if ($empty_scores) {
      $build = [
        '#markup' => $this->t('<p><strong>Warning:</strong> Some metatags are missing for this node: %metatags. Please add metatags to improve SEO.</p>', ['%metatags' => implode(', ', $empty_scores)]),
      ];
      $score = (2 - count($empty_scores)) / 2;
    }
    else {
      $build = [
        '#markup' => $this->t('<p><strong>Success:</strong> Metatags are properly configured for this node.</p>'),
      ];
      $score = 1;
    }

    return [
      'score' => $score,
      'details' => $build,
    ];
  }

}
