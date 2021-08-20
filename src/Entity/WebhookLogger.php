<?php

namespace Drupal\webhook_logger\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Webhook Logger entity.
 *
 * @ConfigEntityType(
 *   id = "webhook_logger",
 *   label = @Translation("Webhook Logger"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\webhook_logger\WebhookLoggerListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webhook_logger\Form\WebhookLoggerForm",
 *       "edit" = "Drupal\webhook_logger\Form\WebhookLoggerForm",
 *       "delete" = "Drupal\webhook_logger\Form\WebhookLoggerDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\webhook_logger\WebhookLoggerHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "webhook_logger",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *     "url",
 *     "message_level_whitelist"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/development/logging/webhook_logger/{webhook_logger}",
 *     "add-form" = "/admin/config/development/logging/webhook_logger/add",
 *     "edit-form" = "/admin/config/development/logging/webhook_logger/{webhook_logger}/edit",
 *     "delete-form" = "/admin/config/development/logging/webhook_logger/{webhook_logger}/delete",
 *     "collection" = "/admin/config/development/logging/webhook_logger"
 *   }
 * )
 */
class WebhookLogger extends ConfigEntityBase implements WebhookLoggerInterface {

  /**
   * The Webhook Logger ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Webhook Logger label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Webhook Logger webhook URL.
   *
   * @var string
   */
  protected $url;

  /**
   * The Webhook Logger message and log level whitelist.
   *
   * @var array
   */
  protected $message_level_whitelist;

}
