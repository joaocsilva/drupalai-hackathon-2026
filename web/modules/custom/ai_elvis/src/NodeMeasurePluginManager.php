<?php

declare(strict_types=1);

namespace Drupal\ai_elvis;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\ai_elvis\Attribute\NodeMeasure;

/**
 * NodeMeasure plugin manager.
 */
final class NodeMeasurePluginManager extends DefaultPluginManager {

  /**
   * Constructs the object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/NodeMeasure', $namespaces, $module_handler, NodeMeasureInterface::class, NodeMeasure::class);
    $this->alterInfo('node_measure_info');
    $this->setCacheBackend($cache_backend, 'node_measure_plugins');
  }

}
