<?php

namespace Drupal\graphql_mutation\Plugin\GraphQL\Mutations;

use Drupal\graphql\GraphQL\Schema\Schema;
use Drupal\graphql\GraphQL\Type\InputObjectType;
use Drupal\graphql\Plugin\GraphQL\PluggableSchemaPluginInterface;
use Youshido\GraphQL\Execution\ResolveInfo;
use Youshido\GraphQL\Type\Scalar\AbstractScalarType;

trait EntityMutationInputTrait {

  /**
   * Loads the schema builder from the resolve info.
   *
   * @param \Youshido\GraphQL\Execution\ResolveInfo $info
   *   The resolve info object.
   *
   * @return \Drupal\graphql\Plugin\GraphQL\PluggableSchemaBuilderInterface
   *   The schema builder.
   */
  protected function getSchemaBuilderFromResolveInfo(ResolveInfo $info) {
    $schema = isset($info) ? $info->getExecutionContext()->getSchema() : NULL;
    if (!$schema instanceof Schema) {
      throw new \LogicException('Could not load schema from execution context.');
    }

    $schemaPlugin = $schema->getSchemaPlugin();
    if (!$schemaPlugin instanceof PluggableSchemaPluginInterface) {
      throw new \LogicException('Could not load schema plugin from schema.');
    }

    return $schemaPlugin->getSchemaBuilder();
  }

  /**
   * Extract entity values from the resolver args.
   *
   * Loops over all input values and assigns them to their original field names.
   *
   * @param array $inputArgs
   *   The entity values provided through the resolver args.
   * @param \Drupal\graphql\GraphQL\Type\InputObjectType $inputType
   *   The input type.
   * @param \Youshido\GraphQL\Execution\ResolveInfo $info
   *   The resolve info object.
   *
   * @return array
   *   The extracted entity values with their proper, internal field names.
   */
  protected function extractEntityInput(array $inputArgs, InputObjectType $inputType, ResolveInfo $info) {
    $builder = $this->getSchemaBuilderFromResolveInfo($info);
    $fields = $inputType->getPlugin($builder)->getPluginDefinition()['fields'];
    return array_reduce(array_keys($inputArgs), function($carry, $current) use ($fields, $inputArgs, $inputType, $info) {
      $isMulti = $fields[$current]['multi'];
      $fieldName = $fields[$current]['field_name'];
      $fieldValue = $inputArgs[$current];

      /** @var \Drupal\graphql\GraphQL\Type\InputObjectType $fieldType */
      $fieldType = $inputType->getField($current)->getType()->getNamedType();

      if ($fieldType instanceof AbstractScalarType) {
        return $carry + [$fieldName => $fieldValue];
      }

      if ($fieldType instanceof InputObjectType) {
        $fieldValue = $isMulti ? array_map(function($value) use ($fieldType, $info) {
          return $this->extractEntityFieldInput($value, $fieldType, $info);
        }, $fieldValue) : $this->extractEntityFieldInput($fieldValue, $fieldType, $info);

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
    $builder = $this->getSchemaBuilderFromResolveInfo($info);
    $properties = $fieldType->getPlugin($builder)->getPluginDefinition()['fields'];
    return array_reduce(array_keys($fieldValue), function($carry, $current) use ($properties, $fieldValue) {
      $key = $properties[$current]['property_name'];
      $value = $fieldValue[$current];

      return $carry + [$key => $value];
    }, []);
  }

}