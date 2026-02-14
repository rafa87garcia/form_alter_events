<?php

declare(strict_types=1);

namespace Drupal\form_alter_events\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\form_alter_events\Event\FormAlterEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Servicio que dispara el evento de form alter.
 */
final class FormAlterDispatcher {

  public function __construct(
    private readonly EventDispatcherInterface $eventDispatcher,
  ) {}

  /**
   * Dispara el evento de form alter.
   */
  public function dispatch(array &$form, FormStateInterface $form_state, string $form_id): void {
    $base_form_id = $form_state->getBuildInfo()['base_form_id'] ?? NULL;

    $event = new FormAlterEvent($form, $form_state, $form_id, is_string($base_form_id) ? $base_form_id : NULL);
    $this->eventDispatcher->dispatch($event, FormAlterEvent::EVENT_NAME);
  }

}
