<?php

namespace Drupal\Core\Installer\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * This is installer step 1.
 */
class DatabaseSettings extends InstallController {

  public function interactive() {
    include_once DRUPAL_ROOT . '/core/includes/form.inc';
    $form = drupal_get_form('install_settings_form', $this->state);
    return new Response(drupal_render($form));
  }
}
