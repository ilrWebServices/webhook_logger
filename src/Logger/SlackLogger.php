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
    // $channel_blocklist = $this->config->get('channel_blocklist');
    // $channel = $context['channel'] ?? '';

    // Ignore if the channel (e.g. cron, page not found) is in the blocklist.
    // if (in_array($channel, $channel_blocklist)) {
    //   return;
    // }

    // Populate the message placeholders and then replace them in the message.
    $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);
    $message = empty($message_placeholders) ? $message : strtr($message, $message_placeholders);

    $icons = [
      RfcLogLevel::EMERGENCY => '💀',
      RfcLogLevel::ALERT => '🆘',
      RfcLogLevel::CRITICAL => '💥',
      RfcLogLevel::ERROR => '😱',
      RfcLogLevel::WARNING => '⚠️',
      RfcLogLevel::NOTICE => '👉',
      RfcLogLevel::INFO => '💬',
      RfcLogLevel::DEBUG => '👁️',
    ];

    $test = <<<END
    {
      "blocks": [
        {
          "type": "context",
          "elements": [
            {
              "type": "plain_text",
              "text": "!severity_icon",
              "emoji": true
            },
            {
              "type": "plain_text",
              "text": "!channel",
              "emoji": true
            }
          ]
        },
        {
          "type": "section",
          "text": {
            "type": "mrkdwn",
            "text": "```!message```"
          }
        },
        {
          "type": "context",
          "elements": [
            {
              "type": "plain_text",
              "text": "!request_uri"
            },
            {
              "type": "plain_text",
              "text": "!ip"
            }
          ]
        }
      ]
    }
    END;

    $body = strtr($test, [
      // '!base_url' => $base_url,
      '!timestamp' => $context['timestamp'],
      '!channel' => $context['channel'],
      '!ip' => $context['ip'],
      '!request_uri' => $context['request_uri'],
      '!referer' => $context['referer'],
      '!severity' => $level,
      '!severity_icon' => $icons[$level],
      '!uid' => $context['uid'],
      '!link' => strip_tags($context['link']),
      '!message' => addslashes($message),
    ]);


    // dump($body);
    // dump(json_encode($body));

    // $text = [
    //   "blocks" => [
    //     [
    //       "type" => "context",
    //       "elements" => [
    //         [
    //           "type" => "plain_text",
    //           "text" => $icons[$level],
    //           "emoji" => true,
    //         ],
    //         [
    //           "type" => "plain_text",
    //           "text" => $channel,
    //           "emoji" => true,
    //         ]
    //       ]
    //     ],
    //     [
    //       "type" => "section",
    //       "text" => [
    //         "type" => "mrkdwn",
    //         "text" => "```$message```",
    //       ]
    //     ],
    //     [
    //       "type" => "context",
    //       "elements" => [
    //         [
    //           "type" => "plain_text",
    //           "text" => $context['request_uri'] ?? '',
    //           "emoji" => true,
    //         ],
    //         [
    //           "type" => "plain_text",
    //           "text" => $context['ip'] ?? '',
    //           "emoji" => true,
    //         ]
    //       ]
    //     ],
    //   ]
    // ];

    try {
      // $response = $this->httpClient->post($this->config->get('slack_webhook_url'), ['json' => $text]);
      $response = $this->httpClient->request('POST', 'https://hooks.slack.com/services/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', [
        'headers' => [
          'Content-Type' => 'application/json',
        ],
        'body' => $body,
      ]);
    }
    catch (\Exception $e) {
      // Do nothing.
    }
  }

}
