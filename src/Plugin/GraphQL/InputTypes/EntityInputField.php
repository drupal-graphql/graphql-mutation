<?php

namespace Drupal\graphql_mutation\Plugin\GraphQL\InputTypes;

use Drupal\graphql\Plugin\GraphQL\InputTypes\InputTypePluginBase;
use Drupal\graphql\Plugin\SchemaBuilderInterface;
use Drupal\graphql\Plugin\TypePluginManager;

/**
 * Creates input types for entity fields and their properties.
 *
 * @GraphQLInputType(
 *   id = "entity_input_field",
 *   deriver = "Drupal\graphql_mutation\Plugin\Deriver\InputTypes\EntityInputFieldDeriver"
 * )
 */
class EntityInputField extends InputTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public static function createInstance(SchemaBuilderInterface $builder, TypePluginManager $manager, $definition, $id) {
    $instance = parent::createInstance($builder, $manager, $definition, $id);
    $instance->config['property_map'] = $definition['property_map'];

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition() {
    $definition = $this->getPluginDefinition();
    $properties = array_map(function ($item) {
      return $item['property_name'];
    }, $definition['fields']);

    return parent::getDefinition() + [
      'property_map' => $properties,
    ];
  }
}
