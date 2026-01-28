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
 * Plugin that checks the presence of schema on a node.
 */
#[NodeMeasure(
  id: 'schema_presence',
  label: new TranslatableMarkup('Schema.org'),
  description: new TranslatableMarkup('Check for the presence of schema tag.'),
)]
final class SchemaPresence extends NodeMeasurePluginBase {

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
      foreach (['schema_article_type', 'schema_article_headline', 'schema_article_date_published'] as $metatag) {
        if (isset($json_value[$metatag])) {
          $trimmed = trim($json_value[$metatag]);
          if (empty($trimmed)) {
            $empty_scores[] = $metatag;
          }
        } else {
          $empty_scores[] = $metatag;
        }
      }
    }

    $build = [];
    $metadata->applyTo($build);
    if ($empty_scores) {
      $build = [
        '#markup' => $this->t('<p><strong>Warning:</strong> Some schema.org are missing for this node: %metatags. Please add schema.org to improve SEO.</p>', ['%metatags' => implode(', ', $empty_scores)]),
      ];
      $score = (3 - count($empty_scores)) / 3;
    }
    else {
      $build = [
        '#markup' => $this->t('<p><strong>Success:</strong> Schema.org tags are properly configured for this node.</p>'),
      ];
      $score = 1;
    }

    return [
      'score' => $score,
      'details' => $build,
    ];
  }

}
