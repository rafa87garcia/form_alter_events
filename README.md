# Form Alter Events

Módulo de Drupal que permite alterar formularios mediante Event Subscribers en lugar de usar hooks tradicionales.

## Descripción

Este módulo proporciona un sistema basado en eventos para alterar formularios en Drupal, ofreciendo una alternativa orientada a objetos a los hooks `hook_form_alter()`, `hook_form_FORM_ID_alter()` y `hook_form_BASE_FORM_ID_alter()`.

## Ventajas sobre hooks tradicionales

- **Separación de responsabilidades**: Cada subscriber puede enfocarse en un formulario específico
- **Inyección de dependencias**: Facilita el uso de servicios sin `\Drupal::service()`
- **Testabilidad**: Los subscribers son más fáciles de testear unitariamente
- **Organización**: Código más mantenible y escalable
- **Prioridades**: Control sobre el orden de ejecución mediante prioridades de eventos

## Instalación

1. Copia el módulo en `web/modules/custom/form_alter_events`
2. Habilita el módulo:
   ```bash
   drush en form_alter_events
   ```

## Uso

### 1. Crear un Event Subscriber

Crea una clase que implemente `EventSubscriberInterface`:

```php
<?php

declare(strict_types=1);

namespace Drupal\mi_modulo\FormAlter;

use Drupal\form_alter_events\Event\FormAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber para alterar el formulario de usuario.
 */
final class UserFormSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      FormAlterEvent::EVENT_NAME => 'onFormAlter',
    ];
  }

  /**
   * Altera el formulario de usuario.
   */
  public function onFormAlter(FormAlterEvent $event): void {
    // Filtra por form_id específico
    if ($event->getFormId() !== 'user_form') {
      return;
    }

    // Obtén el formulario por referencia
    $form = &$event->getForm();

    // Realiza tus modificaciones
    $form['mi_campo_custom'] = [
      '#type' => 'textfield',
      '#title' => t('Campo personalizado'),
      '#weight' => 10,
    ];
  }

}
```

### 2. Registrar el Subscriber como servicio

En tu archivo `mi_modulo.services.yml`:

```yaml
services:
  mi_modulo.user_form_subscriber:
    class: Drupal\mi_modulo\FormAlter\UserFormSubscriber
    tags:
      - { name: event_subscriber }
```

### 3. Limpiar caché

```bash
drush cr
```

## Ejemplos avanzados

### Ejemplo 1: Filtrar por base_form_id

```php
public function onFormAlter(FormAlterEvent $event): void {
  // Alterar todos los formularios de nodos
  if ($event->getBaseFormId() !== 'node_form') {
    return;
  }

  $form = &$event->getForm();
  // Tus modificaciones...
}
```

### Ejemplo 2: Usar inyección de dependencias

```php
<?php

declare(strict_types=1);

namespace Drupal\mi_modulo\FormAlter;

use Drupal\Core\Session\AccountInterface;
use Drupal\form_alter_events\Event\FormAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ConditionalFormSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly AccountInterface $currentUser,
  ) {}

  public static function getSubscribedEvents(): array {
    return [
      FormAlterEvent::EVENT_NAME => 'onFormAlter',
    ];
  }

  public function onFormAlter(FormAlterEvent $event): void {
    if ($event->getFormId() !== 'contact_message_feedback_form') {
      return;
    }

    // Usar servicios inyectados
    if ($this->currentUser->isAuthenticated()) {
      $form = &$event->getForm();
      $form['authenticated_message'] = [
        '#markup' => t('Gracias por estar autenticado'),
      ];
    }
  }

}
```

```yaml
services:
  mi_modulo.conditional_form_subscriber:
    class: Drupal\mi_modulo\FormAlter\ConditionalFormSubscriber
    arguments: ['@current_user']
    tags:
      - { name: event_subscriber }
```

### Ejemplo 3: Establecer prioridades

Si necesitas que un subscriber se ejecute antes que otro:

```php
public static function getSubscribedEvents(): array {
  return [
    // Prioridad más alta (se ejecuta primero)
    FormAlterEvent::EVENT_NAME => ['onFormAlter', 100],
  ];
}
```

### Ejemplo 4: Acceder al FormState

```php
public function onFormAlter(FormAlterEvent $event): void {
  $formState = $event->getFormState();
  $buildInfo = $formState->getBuildInfo();
  
  // Ejemplo: añadir validador
  $form = &$event->getForm();
  $form['#validate'][] = [$this, 'customValidate'];
}

public function customValidate(array &$form, FormStateInterface $form_state): void {
  // Tu lógica de validación
}
```

### Ejemplo 5: Múltiples funciones modificando el mismo formulario

Puedes tener un subscriber con múltiples métodos que modifiquen el mismo formulario en diferentes prioridades. Esto es útil cuando necesitas hacer modificaciones en diferentes etapas:

```php
<?php

declare(strict_types=1);

namespace Drupal\mi_modulo\FormAlter;

use Drupal\form_alter_events\Event\FormAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber con múltiples alteraciones al mismo formulario.
 */
final class MultiStageFormSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // Primera función con prioridad alta (se ejecuta primero)
      FormAlterEvent::EVENT_NAME => [
        ['addFieldsToForm', 100],
        ['modifyExistingFields', 50],
      ],
    ];
  }

  /**
   * Primera alteración: Añade nuevos campos al formulario.
   * 
   * Esta función se ejecuta primero (prioridad 100).
   */
  public function addFieldsToForm(FormAlterEvent $event): void {
    if ($event->getFormId() !== 'node_article_form') {
      return;
    }

    $form = &$event->getForm();

    // Añadir un grupo de campos personalizados
    $form['custom_fields'] = [
      '#type' => 'details',
      '#title' => t('Campos personalizados'),
      '#open' => TRUE,
      '#weight' => 99,
    ];

    $form['custom_fields']['referencia'] = [
      '#type' => 'textfield',
      '#title' => t('Referencia'),
      '#description' => t('Código de referencia del artículo'),
    ];

    $form['custom_fields']['prioridad'] = [
      '#type' => 'select',
      '#title' => t('Prioridad'),
      '#options' => [
        'baja' => t('Baja'),
        'media' => t('Media'),
        'alta' => t('Alta'),
      ],
      '#default_value' => 'media',
    ];
  }

  /**
   * Segunda alteración: Modifica campos existentes.
   * 
   * Esta función se ejecuta después (prioridad 50).
   * Puede trabajar con campos añadidos por la función anterior.
   */
  public function modifyExistingFields(FormAlterEvent $event): void {
    if ($event->getFormId() !== 'node_article_form') {
      return;
    }

    $form = &$event->getForm();

    // Modificar el título existente
    if (isset($form['title']['widget'][0]['value'])) {
      $form['title']['widget'][0]['value']['#description'] = t('Título del artículo (modificado por segundo método)');
      $form['title']['widget'][0]['value']['#maxlength'] = 100;
    }

    // Hacer requerido el campo de prioridad añadido anteriormente
    if (isset($form['custom_fields']['prioridad'])) {
      $form['custom_fields']['prioridad']['#required'] = TRUE;
    }

    // Añadir validación personalizada
    $form['#validate'][] = [$this, 'validateCustomFields'];
  }

  /**
   * Validación personalizada para los campos.
   */
  public function validateCustomFields(array &$form, FormStateInterface $form_state): void {
    $referencia = $form_state->getValue('referencia');
    
    if (!empty($referencia) && !preg_match('/^[A-Z]{2}\d{4}$/', $referencia)) {
      $form_state->setErrorByName('referencia', t('La referencia debe tener el formato: 2 letras mayúsculas seguidas de 4 números (ej: AB1234)'));
    }
  }

}
```

Registro del servicio:

```yaml
services:
  mi_modulo.multi_stage_form_subscriber:
    class: Drupal\mi_modulo\FormAlter\MultiStageFormSubscriber
    tags:
      - { name: event_subscriber }
```

**Explicación del flujo**:

1. **Primera función (`addFieldsToForm`)** con prioridad 100:
   - Se ejecuta primero
   - Añade el grupo "Campos personalizados"
   - Crea los campos "referencia" y "prioridad"

2. **Segunda función (`modifyExistingFields`)** con prioridad 50:
   - Se ejecuta después
   - Puede acceder y modificar campos creados por la primera función
   - Modifica campos existentes del formulario base
   - Añade validación personalizada

Este patrón es útil cuando:
- Necesitas separar la lógica de creación y modificación
- Quieres que diferentes partes de tu código se ejecuten en momentos específicos
- Tienes dependencias entre modificaciones (la segunda función depende de lo que creó la primera)

## API del evento

### FormAlterEvent

Métodos disponibles:

- `getForm()`: Devuelve el array del formulario por referencia
- `getFormState()`: Devuelve el objeto `FormStateInterface`
- `getFormId()`: Devuelve el `form_id` del formulario
- `getBaseFormId()`: Devuelve el `base_form_id` (o `NULL` si no existe)

## Estructura del módulo

```
form_alter_events/
├── form_alter_events.info.yml
├── form_alter_events.module
├── form_alter_events.services.yml
└── src/
    ├── Event/
    │   └── FormAlterEvent.php
    └── Form/
        └── FormAlterDispatcher.php
```

## Cómo funciona internamente

1. El hook `hook_form_alter()` en `form_alter_events.module` intercepta todas las alteraciones de formularios
2. El servicio `FormAlterDispatcher` crea un evento `FormAlterEvent`
3. El Event Dispatcher de Symfony notifica a todos los subscribers registrados
4. Cada subscriber puede filtrar y modificar el formulario según necesite

## Compatibilidad

- Drupal 9-x, 10.x
- PHP 8.1+

## Notas importantes

- **Cache contexts**: Si tu alteración depende del usuario actual, rol, permisos, etc., asegúrate de añadir los cache contexts apropiados en el formulario
- **Referencia**: El método `getForm()` devuelve una referencia, así que las modificaciones persisten automáticamente
- **Prioridad**: Los subscribers con mayor prioridad se ejecutan primero

## Licencia

GPL-2.0-or-later

## Autor

Rafael García Rea.