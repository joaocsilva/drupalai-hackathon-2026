<?php

declare(strict_types=1);

namespace Drupal\ai_elvis;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for node_measure plugins.
 */
abstract class NodeMeasurePluginBase extends PluginBase implements NodeMeasureInterface {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

}
