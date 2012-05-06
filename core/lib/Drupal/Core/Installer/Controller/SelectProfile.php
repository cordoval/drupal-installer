<?php

namespace Drupal\Core\Installer\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * This is installer step 2.
 */
class SelectProfile {
  public function interactive(Request $request) {
    if (empty($install_state['parameters']['profile'])) {
      $session = $request->getSession();
      $session->start();

      $install_state = $session->get('install_state');
      $install_state['profiles'] = $this->install_find_profiles();

      // Try to find a profile.
      $profile = $this->_install_select_profile($install_state['profiles'], $request);

      if (empty($profile)) {
        // We still don't have a profile, so display a form for selecting one.
        // Only do this in the case of interactive installations, since this is
        // not a real form with submit handlers (the database isn't even set up
        // yet), rather just a convenience method for setting parameters in the
        // URL.

        if ($install_state['interactive']) {
          include_once DRUPAL_ROOT . '/core/includes/form.inc';
          drupal_set_title(st('Select an installation profile'));
          $form = drupal_get_form('install_select_profile_form', $install_state['profiles']);
          return new Response(drupal_render($form));
        }
        else {
          throw new Exception(install_no_profile_error());
        }
      }
      else {
        $install_state['parameters']['profile'] = $profile;
        $session->set('install_state', $install_state);
        return new RedirectResponse('load_profile');
      }
    }
  }

  function install_find_profiles() {
    return file_scan_directory('./profiles', '/\.profile$/', array('key' => 'name'));
  }

  /**
   * Helper function for automatically selecting an installation profile from a
   * list or from a selection passed in via $_POST.
   */
  function _install_select_profile($profiles, $request) {
    if (sizeof($profiles) == 0) {
      throw new Exception(install_no_profile_error());
    }
    // Don't need to choose profile if only one available.
    if (sizeof($profiles) == 1) {
      $profile = array_pop($profiles);
      // TODO: is this right?
      require_once DRUPAL_ROOT . '/' . $profile->uri;
      return $profile->name;
    }
    else {
      $profile_from_form = $request->get('profile');
      if (!empty($profile_from_form)) {
        foreach ($profiles as $profile) {
          if ($profile_from_form == $profile->name) {
            return $profile->name;
          }
        }
      }
    }
  }
}
