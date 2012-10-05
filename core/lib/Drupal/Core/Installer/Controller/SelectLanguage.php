<?php

namespace Drupal\Core\Installer\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * This is installer step 1.
 */
class SelectLanguage extends InstallController {
  public function interactive() {
    // Find all available translations.
    $files = install_find_translations();

    if ($langcode = $this->request->get('langcode')) {
      foreach ($files as $file) {
        if ($langcode == $file->langcode) {
          $this->install_state['parameters']['langcode'] = $file->langcode;
          $this->saveInstallState($this->install_state);
          return new RedirectResponse('profile');
        }
      }
    }

    drupal_set_title(st('Select a language'));
    $elements = drupal_get_form('install_select_language_form', $files);
    $output = drupal_render($elements);
    return new Response($output);
  }
}
