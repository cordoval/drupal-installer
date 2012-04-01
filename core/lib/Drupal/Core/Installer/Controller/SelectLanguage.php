<?php

namespace Drupal\Core\Installer\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;


/**
 * This is installer step 1.
 */
class SelectLanguage {

  protected $generator;

  public function __construct(UrlGenerator $generator) {
    $this->generator = $generator;
  }

  public function nonInteractive(Request $request) {
    
  }

  public function interactive(Request $request) {
    // Find all available translations.
    require_once DRUPAL_ROOT . '/core/includes/file.inc';
    $files = install_find_translations();
    $install_state['translations'] = $files;

    // Temporary hack.
    $install_state['interactive'] = TRUE;

    $session = $request->getSession();

    $langcode = $session->get('langcode');

    if (empty($langcode)) {
      // If only the built-in (English) language is available, and we are
      // performing an interactive installation, inform the user that the
      // installer can be translated. Otherwise we assume the user knows what he
      // is doing.
      if (count($files) == 1) {
        if ($install_state['interactive']) {
          $directory = variable_get('locale_translate_file_directory', conf_path() . '/files/translations');

          drupal_set_title(st('Choose language'));
          if (!empty($install_state['parameters']['translate'])) {
            $output = '<p>Follow these steps to translate Drupal into your language:</p>';
            $output .= '<ol>';
            $output .= '<li>Download a translation from the <a href="http://localize.drupal.org/download" target="_blank">translation server</a>.</li>';
            $output .= '<li>Place it into the following directory:<pre>' . $directory . '</pre></li>';
            $output .= '</ol>';
            $output .= '<p>For more information on installing Drupal in different languages, visit the <a href="http://drupal.org/localize" target="_blank">drupal.org handbook page</a>.</p>';
            $output .= '<p>How should the installation continue?</p>';
            $output .= '<ul>';
            $output .= '<li><a href="' . check_url(drupal_current_script_url(array('translate' => NULL))) . '">Reload the language selection page after adding translations</a></li>';
            $output .= '<li><a href="' . check_url(drupal_current_script_url(array('langcode' => 'en', 'translate' => NULL))) . '">Continue installation in English</a></li>';
            $output .= '</ul>';
          }
          else {
            include_once DRUPAL_ROOT . '/core/includes/form.inc';
            $elements = drupal_get_form('install_select_language_form', $files);
            $output = drupal_render($elements);
          }
          return $output;
        }
        // One language, but not an interactive installation. Assume the user
        // knows what he is doing.
        $langcode = current($files);
        $install_state['parameters']['langcode'] = $file->langcode;
        return;
      }
      else {
        // We still don't have a langcode, so display a form for selecting one.
        // Only do this in the case of interactive installations, since this is
        // not a real form with submit handlers (the database isn't even set up
        // yet), rather just a convenience method for setting parameters in the
        // URL.
        if ($install_state['interactive']) {
          drupal_set_title(st('Choose language'));
          include_once DRUPAL_ROOT . '/core/includes/form.inc';
          $elements = drupal_get_form('install_select_language_form', $files);
          return drupal_render($elements);
        }
        else {
          throw new Exception(st('Sorry, you must select a language to continue the installation.'));
        }
      }
    }
  }
}

