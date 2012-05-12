<?php

namespace Drupal\Core\Installer\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * This is installer step 3.
 */
class LoadProfile {

  public function interactive(Request $request) {
    $session = $request->getSession();
    $session->start();
    $install_state = $session->get('install_state');
    $profile = $install_state['parameters']['profile'];
    $langcode = $install_state['parameters']['langcode'];

    $profile_file = DRUPAL_ROOT . '/profiles/' . $profile . '/' . $profile . '.profile';
    if (file_exists($profile_file)) {
      include_once $profile_file;
      $install_state['profile_info'] = install_profile_info($profile, $langcode);

      $session->set('install_state', $install_state);
      return new RedirectResponse('requirements');
    }
    else {
      return new Response(st('Sorry, the profile you have chosen cannot be loaded.'));
    }
  }
}

