<?php

namespace Drupal\graphql_mutation\Plugin\GraphQL\Mutations;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use GraphQL\Language\AST\ListType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;

trait EntityMutationInputTrait {

  /**
   * Extract entity values from the resolver args.
   *
   * Loops over all input values and assigns them to their original field names.
   *
   * @param array $args
   *   The entity values provided through the resolver args.
   * @param \GraphQL\Type\Definition\ResolveInfo $info
   *   The resolve info object.
   *
   * @return array
   *   The extracted entity values with their proper, internal field names.
   */
  protected function extractEntityInput($value, array $args, ResolveContext $context, ResolveInfo $info) {
    $definition = $this->getPluginDefinition();
    $inputType = $info->schema->getType($definition['input_type']);
    $fields = isset($inputType->config['field_map']) ? $inputType->config['field_map'] : [];

    return array_reduce(array_keys($args['input']), function($carry, $current) use ($fields, $args, $info, $inputType) {
      $nullableType = Type::getNullableType($inputType->getField($current)->getType());
      $isMulti = $nullableType instanceof ListType;
      $fieldName = $fields[$current];
      $fieldValue = $args['input'][$current];

      $namedType = Type::getNamedType($nullableType);
      if ($namedType instanceof ScalarType) {
        return $carry + [$fieldName => $fieldValue];
      }

      if ($namedType instanceof InputObjectType) {
        $fieldValue = $isMulti ? array_map(function($value) use ($namedType, $info) {
          return $this->extractEntityFieldInput($value, $namedType, $info);
        }, $fieldValue) : $this->extractEntityFieldInput($fieldValue, $namedType, $info);

        return $carry + [$fieldName => $fieldValue];
      }

      return $carry;
    }, []);
  }

  /**
   * Extract property values from field values from the resolver args.
   *
   * Loops over all field properties and assigns them to their original property
   * names.
   *
   * @param array $fieldValue
   *   The field values keyed by property name.
   * @param \GraphQL\Type\Definition\InputObjectType $fieldType
   *   The field type.
   * @param \GraphQL\Type\Definition\ResolveInfo $info
   *   The resolve info object.
   *
   * @return array
   *   The extracted field values with their proper, internal property names.
   */
  protected function extractEntityFieldInput(array $fieldValue, InputObjectType $fieldType, ResolveInfo $info) {
    $properties = isset($fieldType->config['property_map']) ? $fieldType->config['property_map'] : [];
    return array_reduce(array_keys($fieldValue), function($carry, $current) use ($properties, $fieldValue) {
      $key = $properties[$current];
      $value = $fieldValue[$current];

      return $carry + [$key => $value];
    }, []);
  }

}