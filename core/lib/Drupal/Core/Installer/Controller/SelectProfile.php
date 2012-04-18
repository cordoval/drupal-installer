<?php

namespace Drupal\Core\Installer\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * This is installer step 2.
 */
class SelectProfile {
  public function interactive() {
    if (empty($install_state['parameters']['profile'])) {

      // Temporary hack.
      $install_state = array();
      $install_state['profiles'] = install_find_profiles();

      // Try to find a profile.
      $profile = _install_select_profile($install_state['profiles']);
      if (empty($profile)) {
        // We still don't have a profile, so display a form for selecting one.
        // Only do this in the case of interactive installations, since this is
        // not a real form with submit handlers (the database isn't even set up
        // yet), rather just a convenience method for setting parameters in the
        // URL.

        // Temporary hack.
        $install_state['interactive'] = TRUE;

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
      }
    }
  }
}
