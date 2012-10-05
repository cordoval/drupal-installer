<?php

namespace Drupal\Core\Installer\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Language\Language;

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

          return new RedirectResponse('requirements');
        }
      }
    }

    drupal_set_title(st('Select a language'));
    $elements = drupal_get_form(array($this, 'form'), $files);

    return new Response(drupal_render($elements));
  }

  /**
   * Form constructor for the language selection form.
   */
  public function form($form, &$form_state, $files) {
    include_once DRUPAL_ROOT . '/core/includes/standard.inc';
    include_once DRUPAL_ROOT . '/core/modules/language/language.module';
    include_once DRUPAL_ROOT . '/core/modules/language/language.negotiation.inc';

    $standard_languages = standard_language_list();
    $select_options = array();
    $languages = array();

    foreach ($files as $file) {
      if (isset($standard_languages[$file->langcode])) {
        // Build a list of select list options based on files we found.
        $select_options[$file->langcode] = $standard_languages[$file->langcode][1];
      }
      else {
        // If the language was not found in standard.inc, display its langcode.
        $select_options[$file->langcode] = $file->langcode;
      }
      // Build a list of languages simulated for browser detection.
      $languages[$file->langcode] = new Language(array(
        'langcode' => $file->langcode,
      ));
    }

    $browser_langcode = language_from_browser($languages);
    $form['langcode'] = array(
      '#type' => 'select',
      '#options' => $select_options,
      // Use the browser detected language as default or English if nothing found.
      '#default_value' => !empty($browser_langcode) ? $browser_langcode : 'en',
    );

    if (count($files) == 1) {
      $form['help'] = array(
        '#markup' => '<p><a href="translate">' . st('Learn how to install Drupal in other languages') . '</a></p>',
      );
    }
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] =  array(
      '#type' => 'submit',
      '#value' => st('Save and continue'),
    );

    return $form;
  }
}
