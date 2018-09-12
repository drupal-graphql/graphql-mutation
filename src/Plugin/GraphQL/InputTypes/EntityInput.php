<?php

namespace Drupal\graphql_mutation\Plugin\GraphQL\InputTypes;

use Drupal\graphql\Plugin\GraphQL\InputTypes\InputTypePluginBase;
use Drupal\graphql\Plugin\SchemaBuilderInterface;
use Drupal\graphql\Plugin\TypePluginManager;

/**
 * Creates input types for entity mutations.
 *
 * @GraphQLInputType(
 *   id = "entity_input",
 *   deriver = "Drupal\graphql_mutation\Plugin\Deriver\InputTypes\EntityInputDeriver"
 * )
 */
class EntityInput extends InputTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public static function createInstance(SchemaBuilderInterface $builder, TypePluginManager $manager, $definition, $id) {
    $instance = parent::createInstance($builder, $manager, $definition, $id);
    $instance->config['field_map'] = $definition['field_map'];

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition() {
    $definition = $this->getPluginDefinition();

    return parent::getDefinition() + [
      'field_map' => array_map(function ($item) {
        return $item['field_name'];
      }, $definition['fields']),
    ];
  }
}
