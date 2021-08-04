<?php

namespace Drupal\webhook_logger\Logger;

use Psr\Log\LoggerInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLogLevel;
use GuzzleHttp\ClientInterface;

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
   * Constructs a SlackLogger.
   *
  * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client.
   */
  public function __construct(LogMessageParserInterface $parser, ClientInterface $client) {
    $this->parser = $parser;
    $this->httpClient = $client;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
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

    $channel = $context['channel'] ?? '';

    // @todo
    if ($channel === 'page not found') {
      return;
    }

    try {
      $response = $this->httpClient->post(getenv('SLACK_WEBHOOK_URL'), ['json' => [
        'text' => $icons[$level] . ' ' . $channel . ': ' . $message,
      ]]);
    }
    catch (\Exception $e) {
      // Do nothing.
    }
  }

}
