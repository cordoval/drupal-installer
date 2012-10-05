<?php

namespace Drupal\Core\Installer\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * This is installer step 3.
 */
class Translate extends InstallController {

  public function interactive() {
    $directory = variable_get('locale_translate_file_directory', conf_path() . '/files/translations');

    $output = '<p>Follow these steps to translate Drupal into your language:</p>';
    $output .= '<ol>';
    $output .= '<li>Download a translation from the <a href="http://localize.drupal.org/download" target="_blank">translation server</a>.</li>';
    $output .= '<li>Place it into the following directory: <pre>' . $directory . '</pre></li>';
    $output .= '</ol>';
    $output .= '<p>For more information on installing Drupal in different languages, visit the <a href="http://drupal.org/localize" target="_blank">drupal.org handbook page</a>.</p>';
    $output .= '<p>How should the installation continue?</p>';
    $output .= '<ul>';
    $output .= '<li><a href="language">Reload the language selection page after adding translations</a></li>';
    $output .= '<li><a href="language?langcode=en">Continue installation in English</a></li>';
    $output .= '</ul>';

    return new Response($output);
  }
}

