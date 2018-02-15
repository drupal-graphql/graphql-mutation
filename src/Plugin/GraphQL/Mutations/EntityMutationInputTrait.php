<?php

namespace Drupal\graphql_mutation\Plugin\GraphQL\Mutations;

use Drupal\graphql\GraphQL\Schema\Schema;
use Drupal\graphql\GraphQL\Type\InputObjectType;
use Drupal\graphql\Plugin\GraphQL\PluggableSchemaPluginInterface;
use Youshido\GraphQL\Execution\ResolveInfo;
use Youshido\GraphQL\Type\ListType\ListType;
use Youshido\GraphQL\Type\Scalar\AbstractScalarType;

trait EntityMutationInputTrait {

  /**
   * Extract entity values from the resolver args.
   *
   * Loops over all input values and assigns them to their original field names.
   *
   * @param array $args
   *   The entity values provided through the resolver args.
   * @param \Youshido\GraphQL\Execution\ResolveInfo $info
   *   The resolve info object.
   *
   * @return array
   *   The extracted entity values with their proper, internal field names.
   */
  protected function extractEntityInput(array $args, ResolveInfo $info) {
    /** @var \Drupal\graphql\GraphQL\Type\InputObjectType $inputType */
    $inputType = $info->getField()->getArgument('input')->getType()->getNamedType();
    $fields = $inputType->getPlugin()->getPluginDefinition()['fields'];

    return array_reduce(array_keys($args['input']), function($carry, $current) use ($fields, $args, $info, $inputType) {
      $nullableType = $inputType->getField($current)->getType()->getNullableType();
      $isMulti = $nullableType instanceof ListType;
      $fieldName = $fields[$current]['field_name'];
      $fieldValue = $args['input'][$current];

      /** @var \Drupal\graphql\GraphQL\Type\InputObjectType $namedType */
      $namedType = $nullableType->getNamedType();
      if ($namedType instanceof AbstractScalarType) {
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
   * @param \Drupal\graphql\GraphQL\Type\InputObjectType $fieldType
   *   The field type.
   * @param \Youshido\GraphQL\Execution\ResolveInfo $info
   *   The resolve info object.
   *
   * @return array
   *   The extracted field values with their proper, internal property names.
   */
  protected function extractEntityFieldInput(array $fieldValue, InputObjectType $fieldType, ResolveInfo $info) {
    $properties = $fieldType->getPlugin()->getPluginDefinition()['fields'];
    return array_reduce(array_keys($fieldValue), function($carry, $current) use ($properties, $fieldValue) {
      $key = $properties[$current]['property_name'];
      $value = $fieldValue[$current];

      return $carry + [$key => $value];
    }, []);
  }

}