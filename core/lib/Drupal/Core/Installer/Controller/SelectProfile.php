<?php

namespace Drupal\Core\Installer\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * This is installer step 2.
 */
class SelectProfile extends InstallController {
  public function interactive(Request $request) {
    if (empty($this->install_state['parameters']['profile'])) {
      $this->install_state['profiles'] = $this->install_find_profiles();

      // Try to find a profile.
      $profile = $this->install_select_profile($this->install_state['profiles']);

      if (empty($profile)) {
        // We still don't have a profile, so display a form for selecting one.
        // Only do this in the case of interactive installations, since this is
        // not a real form with submit handlers (the database isn't even set up
        // yet), rather just a convenience method for setting parameters in the
        // URL.

        if ($this->install_state['interactive']) {
          include_once DRUPAL_ROOT . '/core/includes/form.inc';
          drupal_set_title(st('Select an installation profile'));
          $form = drupal_get_form('install_select_profile_form', $this->install_state);
          return new Response(drupal_render($form));
        }
        else {
          throw new \Exception(install_no_profile_error());
        }
      }
      else {
        $this->install_state['parameters']['profile'] = $profile;
        $this->saveInstallState($this->install_state);
        return new RedirectResponse('load_profile');
      }
    }
  }

  function install_find_profiles() {
    return file_scan_directory('core/profiles', '/\.profile$/', array('key' => 'name'));
  }

  /**$install_state
   * Helper function for automatically selecting an installation profile from a
   * list or from a selection passed in via $_POST.
   */
  function install_select_profile($profiles) {
    if (sizeof($profiles) == 0) {
      throw new \Exception(install_no_profile_error());
    }
    // Don't need to choose profile if only one available.
    if (sizeof($profiles) == 1) {
      $profile = array_pop($profiles);
      // TODO: is this right?
      require_once DRUPAL_ROOT . '/' . $profile->uri;
      return $profile->name;
    }
    else {
      $profile_from_form = $this->request->get('profile');
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
