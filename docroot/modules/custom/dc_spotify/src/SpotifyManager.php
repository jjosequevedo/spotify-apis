<?php

namespace Drupal\dc_spotify;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\node\Entity\Node;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Spotify manager service.
 */
class SpotifyManager {

  use LoggerChannelTrait;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a SpotifyManager object.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache.
   */
  public function __construct(
    ClientInterface            $httpClient,
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface     $configFactory,
    CacheBackendInterface      $cache
  ) {
    $this->httpClient = $httpClient;
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->cache = $cache;
    $this->logger = $this->getLogger('dc_spotify');
  }

  /**
   * @return false|string
   */
  protected function getToken() {
    $settings = $this->configFactory->get('dc_spotify.settings');
    try {
      $response = $this->httpClient->request('POST', 'https://accounts.spotify.com/api/token', [
        'form_params' => [
          'grant_type' => 'client_credentials',
          'client_id' => $settings->get('client_id'),
          'client_secret' => $settings->get('client_secret'),
        ],
      ]);
      if ($response && $response->getStatusCode() === 200) {
        $content = json_decode($response->getBody()->getContents(), TRUE);
        return implode(' ', [
          $content['token_type'],
          $content['access_token'],
        ]);
      }
    }
    catch (GuzzleException $e) {
      $this->logger->error($e->getTraceAsString());
    }
    return FALSE;
  }

  /**
   * @param array $term_names
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function vocabularyGenres(array $term_names) {
    $result = [];
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    foreach ($term_names as $term_name) {
      $values = [
        'vid' => 'genres',
        'name' => $term_name,
      ];
      /** @var \Drupal\taxonomy\Entity\Term[] $term */
      $term = $term_storage->loadByProperties($values);
      if ($term) {
        $term = reset($term);
      }
      else {
        $term = $term_storage->create($values);
        $term->save();
      }
      $result[] = $term->id();
    }
    return $result;
  }

  /**
   * @return bool
   */
  public function loadArtists() {
    try {
      $token = $this->getToken();
      if ($token) {
        $response = $this->httpClient->request('GET', 'https://api.spotify.com/v1/artists/0TnOYISbd1XYRBk9myaseg/related-artists', [
          'headers' => [
            'Authorization' => $token,
          ],
        ]);
        if ($response && $response->getStatusCode() === 200) {
          $content = json_decode($response->getBody()->getContents(), TRUE);
          $node = $this->entityTypeManager->getStorage('node');
          foreach ($content['artists'] as $artist) {
            $exist_artist = $node->loadByProperties([
              'type' => 'artist',
              'field_spotify_id' => $artist['id'],
            ]);
            if ($exist_artist) {
              $artist_node = reset($exist_artist);
            }
            else {
              $data = file_get_contents($artist['images'][0]['url']);
              $file = file_save_data($data, 'public://' . $artist['id'] . '.jpeg');
              /** @var \Drupal\node\Entity\Node $artist_node */
              $artist_node = $node->create([
                'type' => 'artist',
                'title' => $artist['name'],
                'field_spotify_id' => $artist['id'],
                'field_amount_of_followers' => $artist['followers']['total'],
                'field_artist_image' => [
                  'target_id' => $file->id(),
                ],
                'field_list_genres' => $this->vocabularyGenres($artist['genres']),
                'field_spotify_artist_link' => [
                  'title' => 'Spotify detail',
                  'uri' => $artist['external_urls']['spotify'],
                ],
                'status' => 1,
              ]);
              $artist_node->save();
            }
            $this->loadSongs($token, $artist_node);
          }
          return TRUE;
        }
      }
    }
    catch (GuzzleException|InvalidPluginDefinitionException|PluginNotFoundException|EntityStorageException $e) {
      $this->logger->error($e->getTraceAsString());
    }
    return FALSE;
  }

  /**
   * @param $token
   * @param \Drupal\node\Entity\Node $artist_node
   *
   * @return void
   */
  protected function loadSongs($token, Node $artist_node) {
    try {
      $spotify_artist_id = $artist_node->get('field_spotify_id')->getString();
      $response = $this->httpClient->request(
        'GET',
        'https://api.spotify.com/v1/artists/' . $spotify_artist_id . '/top-tracks?market=ES',
        [
          'headers' => [
            'Authorization' => $token,
          ],
        ]
      );
      if ($response && $response->getStatusCode() === 200) {
        $content = json_decode($response->getBody()->getContents(), TRUE);
        $node = $this->entityTypeManager->getStorage('node');
        foreach ($content['tracks'] as $track) {
          $exist_song = $node->loadByProperties([
            'type' => 'song',
            'field_spotify_id' => $track['id'],
          ]);
          if (!$exist_song) {
            $data = file_get_contents($track['album']['images'][0]['url']);
            $file = file_save_data($data, 'public://' . $track['id'] . '.jpeg');
            $date_time = new DrupalDateTime($track['album']['release_date']);
            /** @var \Drupal\node\Entity\Node $song_node */
            $song_node = $node->create([
              'type' => 'song',
              'title' => $track['name'],
              'field_spotify_id' => $track['id'],
              'field_album' => [
                'target_id' => $file->id(),
                'alt' => $track['album']['name'],
              ],
              'field_artist' => $artist_node->id(),
              'field_list_genres' => explode(
                ', ', $artist_node->get('field_list_genres')->getString()
              ),
              'field_popularity' => $track['popularity'],
              'field_release_date' => $date_time->format('m/d/Y'),
              'field_spotify_song_link' => [
                'title' => 'Spotify detail',
                'uri' => $track['external_urls']['spotify'],
              ],
              'status' => 1,
            ]);
            $song_node->save();
          }
        }
      }
    }
    catch (GuzzleException|InvalidPluginDefinitionException|PluginNotFoundException|EntityStorageException $e) {
      $this->logger->error($e->getTraceAsString());
    }
  }

}
