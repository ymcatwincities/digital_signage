<?php

namespace Drupal\openy_digital_signage_playlist\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\user\UserInterface;

/**
 * Defines the Digital Signage Playlist Item entity.
 *
 * @ingroup openy_digital_signage_playlist
 *
 * @ContentEntityType(
 *   id = "openy_ds_playlist_item",
 *   label = @Translation("Playlist Item"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylistItemViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\openy_digital_signage_playlist\Form\OpenYPlaylistItemForm",
 *       "add" = "Drupal\openy_digital_signage_playlist\Form\OpenYPlaylistItemForm",
 *       "edit" = "Drupal\openy_digital_signage_playlist\Form\OpenYPlaylistItemForm",
 *       "modal" = "Drupal\openy_digital_signage_playlist\Form\OpenYPlaylistItemModalForm",
 *       "delete" = "Drupal\openy_digital_signage_playlist\Form\OpenYPlaylistItemDeleteForm",
 *     },
 *     "access" = "Drupal\openy_digital_signage_playlist\OpenYPlaylistItemAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "openy_ds_playlist_item",
 *   admin_permission = "administer digital signage playlist item entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/digital-signage/playlist/item/{openy_ds_playlist_item}",
 *     "add-form" = "/admin/digital-signage/playlist/item/add",
 *     "edit-form" = "/admin/digital-signage/playlist/item/{openy_ds_playlist_item}/edit",
 *     "delete-form" = "/admin/digital-signage/playlist/item/{openy_ds_playlist_item}/delete",
 *   },
 *   field_ui_base_route = "openy_ds_playlist_item.settings"
 * )
 */
class OpenYPlaylistItem extends ContentEntityBase implements OpenYPlaylistItemInterface {

  const STATUS_ACTIVE = 'active';
  const STATUS_EXPIRES_SOON = 'expires_soon';
  const STATUS_EXPIRED = 'expired';

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * Get field type value.
   */
  public function getItemType() {
    return $this->get('type')->value;
  }

  /**
   * Get expire status.
   */
  public function getExpireStatus() {
    $date_end = $this->get('date_end')->date;
    $time_end = $this->get('time_end')->date;
    $default_timezone = date_default_timezone_get();
    $current_date = new DrupalDateTime('now', $default_timezone);

    if (!$date_end) {
      // Return STATUS_ACTIVE if end date not set.
      return self::STATUS_ACTIVE;
    }

    $date_end->setTimezone(new \DateTimeZone($default_timezone));
    if (!$time_end) {
      // Set time to end of the day.
      $date_end->setTime(23, 59);
    }
    else {
      $time_parts = explode(':', $time_end->format('H:i:s'));
      // 0 - hours, 1 - day, 2 - seconds.
      $date_end->setTime($time_parts[0], $time_parts[1], $time_parts[2]);
    }

    $interval = $current_date->diff($date_end);
    if ($interval->invert === 1) {
      return self::STATUS_EXPIRED;
    }

    if ($interval->days <= 2) {
      return self::STATUS_EXPIRES_SOON;
    }

    return self::STATUS_ACTIVE;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      //->setRequired(TRUE);

    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Type'))
      ->setDefaultValue('media')
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          'media' => 'Media',
          'playlist' => 'Playlist',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'list_default',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['media'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Media asset'))
      // TODO: this place should refactored to use core media.
      ->setSetting('target_type', 'media')
      ->setSetting('handler', 'default:media')
      ->setSetting('handler_settings', [
        // If we need more media bundles - add them to target_bundles.
        'target_bundles' => [
          'image' => 'image',
        ],
        'auto_create' => FALSE,
      ])
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'entity_reference_entity_view',
        'weight' => 2,
        'settings' => [
          'view_mode' => 'full_without_blazy',
          'link' => FALSE,
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_browser_entity_reference',
        'weight' => 2,
        'settings' => [
          'entity_browser' => 'digital_signage_images_library',
          'field_widget_display' => 'rendered_entity',
          'field_widget_edit' => TRUE,
          'field_widget_remove' => TRUE,
          'open' => FALSE,
          'selection_mode' => 'selection_append',
          'field_widget_display_settings' => [
            'view_mode' => 'playlist_item_teaser',
          ],
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['playlist'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Playlist'))
      ->setDescription(t('Here you can reference an existing playlist entity.'))
      ->setSetting('target_type', 'openy_digital_signage_playlist')
      ->setSetting('handler', 'default:openy_digital_signage_playlist')
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'entity_reference_entity_view',
        'weight' => 2,
        'settings' => [
          'view_mode' => 'default',
          'link' => FALSE,
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['duration'] = BaseFieldDefinition::create('duration')
      ->setLabel(t('Duration'))
      ->setSettings([
        'granularity' => 'h:i:s',
      ])
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'duration_time_display',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'duration_widget',
        'weight' => 3,
      ])
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['date_start'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date start'))
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'datetime_default',
        'weight' => 4,
        'settings' => [
          'format_type' => 'html_date',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 4,
      ])
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['date_end'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date end'))
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'datetime_default',
        'weight' => 5,
        'settings' => [
          'format_type' => 'html_date',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 5,
      ])
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['time_start'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Time start'))
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'datetime_default',
        'weight' => 6,
        'settings' => [
          'format_type' => 'html_time',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_time_only',
        'weight' => 6,
      ])
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATETIME)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['time_end'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Time end'))
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'datetime_default',
        'weight' => 7,
        'settings' => [
          'format_type' => 'html_time',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_time_only',
        'weight' => 7,
      ])
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATETIME)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Digital Signage Playlist Item is published.'))
      ->setSettings(['on_label' => 'Published', 'off_label' => 'Unpublished'])
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
