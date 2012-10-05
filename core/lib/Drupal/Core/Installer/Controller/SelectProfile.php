<?php

namespace Drupal\Core\Installer\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * This is installer step 2.
 */
class SelectProfile extends InstallController {
  public function interactive() {
    // Find all available profiles.
    $files = file_scan_directory('core/profiles', '/\.profile$/', array('key' => 'name'));
    $profile = count($files) == 1 ? reset($files)->name : $this->request->get('profile');

    if ($profile) {
      foreach ($files as $file) {
        if ($profile == $file->name) {
          $langcode = $this->install_state['parameters']['langcode'];
          $this->install_state['profile_info'] = install_profile_info($profile, $langcode);
          $this->install_state['parameters']['profile'] = $file->name;
          $this->saveInstallState($this->install_state);

          return new RedirectResponse('database');
        }
      }
    }

    drupal_set_title(st('Select an installation profile'));
    $elements = drupal_get_form(array($this, 'form'), $files);

    return new Response(drupal_render($elements));
  }

  /**
   * Form constructor for the profile selection form.
   */
  public function form($form, &$form_state, $files) {
    $profiles = array();
    $names = array();

    foreach ($files as $profile) {
      $details = install_profile_info($profile->name);
      // Don't show hidden profiles. This is used by to hide the testing profile,
      // which only exists to speed up test runs.
      if ($details['hidden'] === TRUE) {
        continue;
      }
      $profiles[$profile->name] = $details;

      // Determine the name of the profile; default to file name if defined name
      // is unspecified.
      $name = isset($details['name']) ? $details['name'] : $profile->name;
      $names[$profile->name] = $name;
    }

    // Display radio buttons alphabetically by human-readable name, but always
    // put the core profiles first (if they are present in the filesystem).
    natcasesort($names);
    if (isset($names['minimal'])) {
      // If the expert ("Minimal") core profile is present, put it in front of
      // any non-core profiles rather than including it with them alphabetically,
      // since the other profiles might be intended to group together in a
      // particular way.
      $names = array('minimal' => $names['minimal']) + $names;
    }
    if (isset($names['standard'])) {
      // If the default ("Standard") core profile is present, put it at the very
      // top of the list. This profile will have its radio button pre-selected,
      // so we want it to always appear at the top.
      $names = array('standard' => $names['standard']) + $names;
    }

    foreach ($names as $profile => $name) {
      // The profile name and description are extracted for translation from the
      // .info file, so we can use st() on them even though they are dynamic data
      // at this point.
      $form['profile'][$name] = array(
        '#type' => 'radio',
        '#value' => 'standard',
        '#return_value' => $profile,
        '#title' => st($name),
        '#description' => isset($profiles[$profile]['description']) ? st($profiles[$profile]['description']) : '',
        '#parents' => array('profile'),
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
