<?php

namespace Drupal\ai_elvis\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\ai_elvis\Service\LinkOpportunitiesService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for finding similar content for linking opportunities.
 */
class LinkOpportunitiesController extends ControllerBase {

  /**
   * The link opportunities service.
   *
   * @var \Drupal\ai_elvis\Service\LinkOpportunitiesService
   */
  protected $linkOpportunitiesService;

  /**
   * Constructs a LinkOpportunitiesController object.
   *
   * @param \Drupal\ai_elvis\Service\LinkOpportunitiesService $link_opportunities_service
   *   The link opportunities service.
   */
  public function __construct(LinkOpportunitiesService $link_opportunities_service) {
    $this->linkOpportunitiesService = $link_opportunities_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ai_elvis.link_opportunities')
    );
  }

  /**
   * Finds and displays similar content for linking opportunities.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The current node.
   *
   * @return array
   *   A render array.
   */
  public function findLinkingOpportunities(NodeInterface $node) {
    // Get the node's body text for similarity search
    $text = $this->extractNodeText($node);

    if (empty($text)) {
      return [
        '#markup' => $this->t('No content available for similarity search.'),
      ];
    }

    // Search for similar content (excluding current node)
    $results = $this->linkOpportunitiesService->searchSimilarContent($text, 10);
    $formatted_results = $this->linkOpportunitiesService->formatSearchResults($results);

    // Filter out the current node
    $filtered_results = array_filter($formatted_results, function($result) use ($node) {
      return $result['nid'] != $node->id();
    });

    return [
      '#theme' => 'similar_content_linking',
      '#current_node' => $node,
      '#similar_nodes' => $filtered_results,
      '#cache' => [
        'contexts' => ['url.path'],
        'tags' => $node->getCacheTags(),
        'max-age' => 3600,
      ],
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
  protected function extractNodeText(NodeInterface $node) {
    // Get the entity view builder
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');

    // Render the node in default view mode
    $build = $view_builder->view($node, 'default');
    $rendered = \Drupal::service('renderer')->renderPlain($build);

    // Strip HTML tags to get plain text
    $text = strip_tags($rendered);

    // Clean up whitespace
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);

    return $text;
  }
}
