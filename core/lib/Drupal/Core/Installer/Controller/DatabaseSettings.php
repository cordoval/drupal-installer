<?php
/**
 * @file
 * Handle the database settings.
 */

namespace Drupal\Core\Installer\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * This is installer step 5.
 */
class DatabaseSettings extends InstallController {

  /**
   * Display the database selection form to the user.
   */
  public function interactive() {
    include_once DRUPAL_ROOT . '/core/includes/form.inc';

    $install_state = $this->install_state;
    $form_state = array(
      // We need to pass $install_state by reference in order for forms to
      // modify it, since the form API will use it in call_user_func_array(),
      // which requires that referenced variables be passed explicitly.
      'build_info' => array('args' => array(&$install_state)),
      'no_redirect' => TRUE,
    );
    $form = drupal_build_form('install_settings_form', $form_state);

    return new Response(drupal_render($form));
  }
}
