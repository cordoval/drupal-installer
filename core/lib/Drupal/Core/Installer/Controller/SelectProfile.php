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
    $elements = drupal_get_form('install_select_profile_form', $files);
    $output = drupal_render($elements);
    return new Response($output);
  }
}
