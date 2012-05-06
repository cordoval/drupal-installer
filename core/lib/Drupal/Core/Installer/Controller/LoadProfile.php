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
    $profile = $session->get('profile');
    $langcode = $session->get('langcode');

    $profile_file = DRUPAL_ROOT . '/profiles/' . $profile . '/' . $profile . '.profile';
    if (file_exists($profile_file)) {
      include_once $profile_file;
      $session->set('profile_info', install_profile_info($profile, $langcode));
      $install_state['profile_info'] = install_profile_info($install_state['parameters']['profile'], $install_state['parameters']['langcode']);
      return new RedirectResponse('requirements');
    }
    else {
      return new Response(st('Sorry, the profile you have chosen cannot be loaded.'));
    }
  }
}

