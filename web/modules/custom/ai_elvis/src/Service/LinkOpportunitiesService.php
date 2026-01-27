<?php

namespace Drupal\ai_elvis\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\ParseMode\ParseModePluginManager;

/**
 * Service for finding link opportunities between similar content.
 */
class LinkOpportunitiesService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The parse mode plugin manager.
   *
   * @var \Drupal\search_api\ParseMode\ParseModePluginManager
   */
  protected $parseModeManager;

  /**
   * Constructs a LinkOpportunitiesService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\search_api\ParseMode\ParseModePluginManager $parse_mode_manager
   *   The parse mode plugin manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
    ParseModePluginManager $parse_mode_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
    $this->parseModeManager = $parse_mode_manager;
  }

  /**
   * Search for similar content based on text similarity.
   *
   * @param string $text
   *   The text to search for similar content.
   * @param int $limit
   *   The maximum number of results to return.
   *
   * @return array
   *   An array of similar content results.
   */
  public function searchSimilarContent($text, $limit = 10) {
    $results = [];

    try {
      // Load the Search API index configured with AI search.
      $index = Index::load('nodes');

      if (!$index) {
        $this->loggerFactory->get('ai_elvis')->error('Search API index "nodes" not found.');
        return $results;
      }

      // Create a search query using the AI-powered index.
      $query = $index->query();

      // Set the search keys - this will be converted to embeddings by ai_search.
      $query->keys("Search for pages that could be linked to the page containing the text: " . $text);

      // Limit the number of results.
      $query->range(0, $limit);

      // Execute the search.
      $search_results = $query->execute();

      // Process the results.
      foreach ($search_results->getResultItems() as $item) {
        $entity = $item->getOriginalObject()->getValue();

        if ($entity instanceof NodeInterface) {
          $results[] = [
            'nid' => $entity->id(),
            'title' => $entity->getTitle(),
            'type' => $entity->bundle(),
            'score' => $item->getScore(),
            'entity' => $entity,
          ];
        }
      }
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('ai_elvis')->error('Error searching for similar content: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return $results;
  }

  /**
   * Format search results for display.
   *
   * @param array $results
   *   The raw search results.
   *
   * @return array
   *   The formatted search results.
   */
  public function formatSearchResults(array $results) {
    $formatted = [];

    foreach ($results as $result) {
      $formatted[] = [
        'nid' => $result['nid'] ?? 0,
        'title' => $result['title'] ?? '',
        'type' => $result['type'] ?? '',
        'score' => $result['score'] ?? 0,
        'url' => '/node/' . ($result['nid'] ?? 0),
      ];
    }

    return $formatted;
  }

}
