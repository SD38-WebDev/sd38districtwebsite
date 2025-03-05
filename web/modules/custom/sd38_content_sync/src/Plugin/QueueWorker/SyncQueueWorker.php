<?php

namespace Drupal\sd38_content_sync\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\SuspendQueueException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sync Queue Worker.
 *
 * @QueueWorker(
 *   id = "sync_queue_worker",
 *   title = @Translation("Sync Queue Worker"),
 *   cron = {"time" = 60}
 * )
 */
class SyncQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   *
   * The Entity Type Manager.
   *
   */
  protected $em;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   *
   * The Config Factory.
   *
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   *
   * The Logger Factory.
   */
  protected $loggerFactory;

  /**
   * @var \GuzzleHttp\ClientInterface
   *
   * The HTTP Client.
   *
   */
  protected $httpClient;

  /**
   * Creates a new SyncQueueWorker object.
   */
  public function __construct(
    EntityTypeManagerInterface $em,
    ConfigFactoryInterface $configFactory,
    LoggerChannelFactoryInterface $loggerFactory,
    ClientInterface $httpClient
  ) {
    $this->em = $em;
    $this->configFactory = $configFactory;
    $this->loggerFactory = $loggerFactory;
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('http_client'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $schools = sd38_content_sync_get_schools_list_domains();
    $client = $this->httpClient;
    $config = $this->configFactory->get('sd38_content_sync.settings');
    $username = $config->get('d38_rest_username') ?? '';
    $password = $config->get('d38_rest_password') ?? '';

    foreach ($data['schools'] as $school) {
      try {
        $url = 'https://' . $schools[$school] . '/api/district-import';

        $response = $client->request('POST', $url, [
          'auth' => [$username, $password], // Basic Authentication
          'verify' => FALSE, // Disable SSL verification
          'json' => [
            'bundle' => $data['bundle'],
            'nid' => $data['nid']
          ], // Send JSON data
          'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
          ],
        ]);
        if ($response->getStatusCode() !== 200) {
          throw new SuspendQueueException('The school website is not responding.');
        }
      }
      catch (RequestException $e) {
        $this->loggerFactory->get('sd38_content_sync')
          ->error('Failed to execute POST request: @code. Error: @error', [
            '@code' => $e->getCode(),
            '@error' => $e->getMessage(),
          ]);
        throw new SuspendQueueException('Failed to execute POST request.');
      }
    }
  }
}
