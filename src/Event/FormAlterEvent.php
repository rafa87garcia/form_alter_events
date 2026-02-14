<?php

declare(strict_types=1);

namespace Drupal\form_alter_events\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Drupal\Core\Form\FormStateInterface;

/**
 * Evento que envuelve el alter de un formulario.
 */
final class FormAlterEvent extends Event {

  public const EVENT_NAME = 'form_alter_events.form_alter';

  public function __construct(
    private array &$form,
    private readonly FormStateInterface $formState,
    private readonly string $formId,
    private readonly ?string $baseFormId = NULL,
  ) {}

  /**
   * Ojo: devuelve por referencia.
   */
  public function &getForm(): array {
    return $this->form;
  }

  /**
   * Devuelve el estado del formulario.
   */
  public function getFormState(): FormStateInterface {
    return $this->formState;
  }

  /**
   * Devuelve el form_id del formulario.
   */
  public function getFormId(): string {
    return $this->formId;
  }

  /**
   * Devuelve el base_form_id del formulario, o NULL si no tiene.
   * Puede ser útil para alterar varios formularios que compartan base_form_id.
   */
  public function getBaseFormId(): ?string {
    return $this->baseFormId;
  }

}
