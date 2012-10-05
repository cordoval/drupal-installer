<?php

namespace Drupal\Core\Installer\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * This is installer step 3.
 */
class LoadProfile extends InstallController {

  public function interactive() {
    $profile = $this->install_state['parameters']['profile'];
    $langcode = $this->install_state['parameters']['langcode'];

    $profile_file = DRUPAL_ROOT . '/core/profiles/' . $profile . '/' . $profile . '.profile';
    if (file_exists($profile_file)) {
      include_once $profile_file;
      $this->install_state['profile_info'] = install_profile_info($profile, $langcode);
      $this->saveInstallState($this->install_state);
      return new RedirectResponse('requirements');
    }
    else {
      return new Response(st('Sorry, the profile you have chosen cannot be loaded.'));
    }
  }
}

