<?php

namespace Drupal\webhook_logger\Logger;

use Psr\Log\LoggerInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Logger\LogMessageParserInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * A logger that posts to Slack.
 */
class SlackLogger implements LoggerInterface {

  use RfcLoggerTrait;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * A configuration object containing webhook_logger settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a SlackLogger.
   *
  * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration factory object.
   */
  public function __construct(LogMessageParserInterface $parser, ClientInterface $client, ConfigFactory $config_factory) {
    $this->parser = $parser;
    $this->httpClient = $client;
    $this->config = $config_factory->get('webhook_logger.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    $channel_blocklist = $this->config->get('channel_blocklist');
    $channel = $context['channel'] ?? '';

    // Ignore if the channel (e.g. cron, page not found) is in the blocklist.
    if (in_array($channel, $channel_blocklist)) {
      return;
    }

    // Populate the message placeholders and then replace them in the message.
    $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);
    $message = empty($message_placeholders) ? $message : strtr($message, $message_placeholders);

    $icons = [
      RfcLogLevel::EMERGENCY => ':rotating_light:',
      RfcLogLevel::ALERT => ':rotating_light:',
      RfcLogLevel::CRITICAL => ':rotating_light:',
      RfcLogLevel::ERROR => ':warning:',
      RfcLogLevel::WARNING => ':warning:',
      RfcLogLevel::NOTICE => ':memo:',
      RfcLogLevel::INFO => ':information_source:',
      RfcLogLevel::DEBUG => ':ladybug:',
    ];

    $text = [
      "blocks" => [
        [
          "type" => "context",
          "elements" => [
            [
              "type" => "plain_text",
              "text" => $icons[$level],
              "emoji" => true,
            ],
            [
              "type" => "plain_text",
              "text" => $channel,
              "emoji" => true,
            ],
            [
              "type" => "plain_text",
              "text" => $context['request_uri'] ?? '',
              "emoji" => true,
            ],
            [
              "type" => "plain_text",
              "text" => $context['ip'] ?? '',
              "emoji" => true,
            ]
          ]
        ],
        [
          "type" => "section",
          "text" => [
            "type" => "mrkdwn",
            "text" => "```$message```",
          ]
        ]
      ]
    ];

    try {
      $response = $this->httpClient->post($this->config->get('slack_webhook_url'), ['json' => $text]);
    }
    catch (\Exception $e) {
      // Do nothing.
    }
  }

}
