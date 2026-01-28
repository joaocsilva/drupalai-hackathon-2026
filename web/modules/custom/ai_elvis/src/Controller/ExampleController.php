<?php

declare(strict_types=1);

namespace Drupal\ai_elvis\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ai_elvis\NodeMeasurePluginManager;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for ai_elvis routes.
 */
final class ExampleController extends ControllerBase {

  /**
   * The controller constructor.
   */
  public function __construct(
    private readonly NodeMeasurePluginManager $pluginManagerNodeMeasure,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('plugin.manager.node_measure'),
    );
  }

  /**
   * Builds the response.
   */
  public function __invoke(): array {
    $plugin = $this->pluginManagerNodeMeasure->createInstance('possible_backlinks');
    $result = $plugin->measureNode(Node::load(3));

    $build['title'] = ['#markup' => '<h2>' . $plugin->label() . '</h2>'];
    $build['score'] = ['#markup' => '<p>Score: ' . $result['score'] . '</p>'];
    $build['content'] = $result['details'];

    return $build;
  }

}
