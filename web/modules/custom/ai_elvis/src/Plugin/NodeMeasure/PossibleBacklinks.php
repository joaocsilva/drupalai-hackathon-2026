<?php

declare(strict_types=1);

namespace Drupal\ai_elvis\Plugin\NodeMeasure;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai_elvis\Attribute\NodeMeasure;
use Drupal\ai_elvis\NodeMeasurePluginBase;
use Drupal\ai_elvis\Service\LinkOpportunitiesService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the node_measure.
 */
#[NodeMeasure(
  id: 'possible_backlinks',
  label: new TranslatableMarkup('Domain backlinks'),
  description: new TranslatableMarkup('Possible domain backlinks.'),
)]
final class PossibleBacklinks extends NodeMeasurePluginBase implements ContainerFactoryPluginInterface {

  /**
   * The link opportunities service.
   *
   * @var \Drupal\ai_elvis\Service\LinkOpportunitiesService
   */
  protected $linkOpportunitiesService;

  /**
   * Constructs a PossibleBacklinks object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ai_elvis\Service\LinkOpportunitiesService $link_opportunities_service
   *   The link opportunities service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LinkOpportunitiesService $link_opportunities_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->linkOpportunitiesService = $link_opportunities_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ai_elvis.link_opportunities')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function measureNode(\Drupal\node\NodeInterface $node): array {
    // Extract text content from the node
    $text = $this->extractNodeText($node);

    if (empty($text)) {
      return [
        'score' => 0,
        'details' => [
          '#markup' => '<p>No content available for similarity search.</p>',
        ],
      ];
    }

    // Search for similar content (top 5 results)
    $results = $this->linkOpportunitiesService->searchSimilarContent($text, 5);

    // Filter out the current node from results
    $filtered_results = array_filter($results, function($result) use ($node) {
      return $result['nid'] != $node->id();
    });

    // Re-index array after filtering
    $filtered_results = array_values($filtered_results);

    $count = count($filtered_results);

    // Build render array with linked nodes
    if ($count > 0) {
      $items = [];
      foreach ($filtered_results as $result) {
        $items[] = [
          '#type' => 'link',
          '#title' => $result['title'] . ' (Score: ' . round($result['score'], 2) . ')',
          '#url' => \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $result['nid']]),
          '#suffix' => '<br>',
        ];
      }

      $details = [
        '#theme' => 'item_list',
        '#items' => $items,
        '#list_type' => 'ul',
      ];
    } else {
      $details = [
        '#markup' => '<p>No similar content found for backlink opportunities.</p>',
      ];
    }

    return [
      'score' => $count > 0 ? 1 : 0,
      'details' => $details,
    ];
  }

  /**
   * Extracts text content from a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to extract text from.
   *
   * @return string
   *   The extracted text.
   */
  protected function extractNodeText(\Drupal\node\NodeInterface $node): string {
    // Get the entity view builder
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');

    // Render the node in default view mode
    $build = $view_builder->view($node, 'default');
    $rendered = \Drupal::service('renderer')->renderPlain($build);

    // Clean up whitespace
    $text = preg_replace('/\s+/', ' ', $rendered->__toString());
    $text = trim($text);

    return $text;
  }

}
