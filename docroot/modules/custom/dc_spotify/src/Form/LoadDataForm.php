<?php

namespace Drupal\dc_spotify\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dc_spotify\SpotifyManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a dc_spotify form.
 */
class LoadDataForm extends FormBase {

  /**
   * The spotify manager.
   *
   * @var \Drupal\dc_spotify\SpotifyManager
   */
  protected $spotifyManager;

  /**
   * Constructs a new LoadDataForm object.
   *
   * @param \Drupal\dc_spotify\SpotifyManager $spotifyManager
   *   The spotify manager.
   */
  public function __construct(SpotifyManager $spotifyManager) {
    $this->spotifyManager = $spotifyManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('dc_spotify.spotify_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dc_spotify_load_data';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Load data'),
      '#name' => 'load_data',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->spotifyManager->loadArtists()) {
      $this->messenger()->addStatus($this->t('All information was loaded successfully from Spotify.'));
      return;
    }
    $this->messenger()->addStatus($this->t('Sorry, there was an error. Please, check your logs.'));
  }

}
