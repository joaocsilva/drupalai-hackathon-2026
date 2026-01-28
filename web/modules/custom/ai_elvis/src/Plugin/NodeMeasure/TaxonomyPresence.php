<?php
declare(strict_types=1);
namespace Drupal\ai_elvis\Plugin\NodeMeasure;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai_elvis\Attribute\NodeMeasure;
use Drupal\ai_elvis\NodeMeasurePluginBase;
use Drupal\node\NodeInterface;
use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * Plugin that checks the presence of taxonomy tags on a node.
 */
#[NodeMeasure(
  id: 'taxonomy_presence',
  label: new TranslatableMarkup('Tags'),
  description: new TranslatableMarkup('Check for the presence of taxonomy.'),
)]
final class TaxonomyPresence extends NodeMeasurePluginBase implements ContainerFactoryPluginInterface {
  use StringTranslationTrait;
  /**
   * The AI provider plugin manager.
   *
   * @var \Drupal\ai\AiProviderPluginManager
   */
  protected $aiProvider;
  /**
   * Constructs a TaxonomyPresence object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ai\AiProviderPluginManager $ai_provider
   *   The AI provider plugin manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AiProviderPluginManager $ai_provider
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->aiProvider = $ai_provider;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ai.provider')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function measureNode(NodeInterface $node): array {
    $metadata = new CacheableMetadata();
    $metadata->addCacheableDependency($node);
    // Get the taxonomy field value.
    $taxonomy_field = $node->get('field_tags');
    // Check if the field is empty.
    if ($taxonomy_field->isEmpty()) {
      // Get AI suggestions for tags.
      $suggestions = $this->getTagSuggestions($node);
      $build = [
        '#markup' => $this->t('<p><strong>Warning:</strong> No tags are assigned to this node. Please add tags to improve SEO.</p>'),
      ];
      if (!empty($suggestions)) {
        $build['suggestions'] = [
          '#theme' => 'item_list',
          '#items' => $suggestions,
          '#list_type' => 'ul',
          '#attributes' => ['class' => ['measure-details']],
          '#prefix' => '<p><strong>AI Suggested Tags:</strong></p>',
        ];
      }
      $score = 0.0;
    }
    else {
      $build = [
        '#markup' => $this->t('<p><strong>Success:</strong> The node is properly tagged with taxonomy terms.</p>'),
      ];
      $score = 1.0;
    }
    $metadata->applyTo($build);
    return [
      'score' => $score,
      'details' => $build,
    ];
  }
  /**
   * Get AI-powered tag suggestions for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to get suggestions for.
   *
   * @return array
   *   An array of suggested tag names.
   */
  protected function getTagSuggestions(NodeInterface $node): array {
    try {
      // Get all available tags from the vocabulary.
      $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $terms = $term_storage->loadByProperties(['vid' => 'tags']);
      $available_tags = [];
      foreach ($terms as $term) {
        $available_tags[] = $term->getName();
      }
      if (empty($available_tags)) {
        return [];
      }
      // Render the node as plain text.
      $node_text = $this->extractNodeText($node);
      if (empty($node_text)) {
        return [];
      }
      // Build the prompt for AI.
      $prompt = $this->buildPrompt($node_text, $available_tags);
      // Get the AI provider.
      $provider = $this->aiProvider->createInstance('amazeeio');
      // Create chat input.
      $message = new ChatMessage('user', $prompt);
      $input = new ChatInput([$message]);
      // Call the AI with Mistral medium model.
      $response = $provider->chat($input, 'mistral-medium-latest');
      // Parse the response to extract suggested tags.
      $suggested_tags = $this->parseAiResponse($response->getNormalized(), $available_tags);
      return $suggested_tags;
    }
    catch (\Exception $e) {
      \Drupal::logger('ai_elvis')->error('Error getting AI tag suggestions: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }
  /**
   * Extract plain text content from a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to extract text from.
   *
   * @return string
   *   The extracted text.
   */
  protected function extractNodeText(NodeInterface $node): string {
    // Get the entity view builder.
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    // Render the node in default view mode.
    $build = $view_builder->view($node, 'default');
    $rendered = \Drupal::service('renderer')->renderPlain($build);
    // Strip HTML tags and clean up whitespace.
    $text = strip_tags($rendered->__toString());
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    return $text;
  }
  /**
   * Build the AI prompt for tag suggestions.
   *
   * @param string $node_text
   *   The node content as plain text.
   * @param array $available_tags
   *   The list of available taxonomy terms.
   *
   * @return string
   *   The formatted prompt.
   */
  protected function buildPrompt(string $node_text, array $available_tags): string {
    $tags_list = implode(', ', $available_tags);
    $prompt = <<<EOT
Based on the following content, suggest up to 5 relevant tags from the available tags list.
Content:
{$node_text}
Available Tags:
{$tags_list}
Please respond with ONLY a comma-separated list of the most relevant tag names from the available tags. Do not include any explanations or additional text.
EOT;
    return $prompt;
  }
  /**
   * Parse the AI response to extract suggested tags.
   *
   * @param string $response
   *   The AI response text.
   * @param array $available_tags
   *   The list of available taxonomy terms.
   *
   * @return array
   *   An array of suggested tag names.
   */
  protected function parseAiResponse(ChatMessage $response, array $available_tags): array {
    // Clean up the response.
    $response = trim($response->getText());
    // Split by comma.
    $suggested = array_map('trim', explode(',', $response));
    // Filter to only include valid tags that exist in available_tags.
    $valid_suggestions = [];
    foreach ($suggested as $tag) {
      // Case-insensitive matching.
      foreach ($available_tags as $available_tag) {
        if (strcasecmp($tag, $available_tag) === 0) {
          $valid_suggestions[] = $available_tag;
          break;
        }
      }
    }
    // Limit to 5 suggestions.
    return array_slice($valid_suggestions, 0, 5);
  }
}
