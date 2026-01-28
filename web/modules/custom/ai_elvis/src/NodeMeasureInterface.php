<?php

declare(strict_types=1);

namespace Drupal\ai_elvis;

/**
 * Interface for node_measure plugins.
 */
interface NodeMeasureInterface {

  /**
   * Returns the translated plugin label.
   */
  public function label(): string;

  /**
   * Measure the node and return the result.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return array
   *   The array containing "score" and "details".
   */
  public function measureNode(\Drupal\node\NodeInterface $node): array;

}
