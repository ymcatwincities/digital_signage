<?php

namespace Drupal\openy_digital_signage_room\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation for openy_ds_room_origin field type.
 *
 * @FieldType(
 *   id = "openy_ds_room_origin",
 *   label = @Translation("Open Y DS Room Origin ID"),
 *   description = @Translation("Stores Open Y DS Room Origin ID info."),
 *   default_widget = "openy_ds_room_origin_default",
 *   default_formatter = "openy_ds_room_origin_default"
 * )
 */
class RoomOriginItem extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['origin'] = DataDefinition::create('string')
      ->setLabel(t('Name of the origin'));

    $properties['id'] = DataDefinition::create('string')
      ->setLabel(t('Origin ID'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema['columns']['origin'] = [
      'description' => 'Name of the origin.',
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    ];

    $schema['columns']['id'] = [
      'description' => 'Origin ID',
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $values = $this->getValue();
    return !($values['origin'] !== '' || $values['id'] !== '');
  }

}
