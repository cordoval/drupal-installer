<?php

namespace Drupal\Core\Installer\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * This is installer step 1.
 */
class SelectLanguage extends InstallController {

  public function nonInteractive() {

  }

  public function interactive() {
    $install_state = $this->install_state;

    $directory = variable_get('locale_translate_file_directory', conf_path() . '/files/translations');

    drupal_set_title('Choose language');
    include_once DRUPAL_ROOT . '/core/includes/form.inc';
    $elements = drupal_get_form('install_select_language_form', 'installer-form');
    $output = drupal_render($elements);
    return new Response($output);
  }
}
