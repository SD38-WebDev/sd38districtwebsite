<?php

namespace Drupal\sd38_content_sync\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\QueueWorkerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\node\Entity\Node;
use Drupal\sd38_content_sync\ImporterPreprocessManager;
use Drupal\taxonomy\Entity\Term;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Drupal\Core\File\FileSystemInterface;
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

  protected $em;

  /**
   * Creates a new NodePublishBase object.
   */
  public function __construct(
    EntityTypeManagerInterface $em,
  ) {
    $this->em = $em;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $client = \Drupal::httpClient();
    foreach ($data['school'] as $school) {
      try {
        $url = $school['value'] . '/api/district-import';
        $response = $client->request('POST', $url, [
          'auth' => ['rest', '671597Xx6802!'], // Basic Authentication
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
        \Drupal::logger('sd38_content_sync')
          ->error('Failed to execute POST request: @code. Error: @error', [
            '@code' => $e->getCode(),
            '@error' => $e->getMessage(),
          ]);
        throw new SuspendQueueException('Failed to execute POST request.');
      }
    }
  }
}
