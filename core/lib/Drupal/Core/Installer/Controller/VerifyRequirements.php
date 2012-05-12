<?php

namespace Drupal\Core\Installer\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * This is installer step 4.
 */
class VerifyRequirements {

  public function interactive(Request $request) {
    $session = $request->getSession();
    $session->start();

    $install_state = $session->get('install_state');
    // Check the installation requirements for Drupal and this profile.
    $requirements = $this->install_check_requirements($install_state);

    // Verify existence of all required modules.
    $requirements += $this->drupal_verify_profile($install_state);

    // Check the severity of the requirements reported.
    $severity = drupal_requirements_severity($requirements);

    // If there are errors, always display them. If there are only warnings, skip
    // them if the user has provided a URL parameter acknowledging the warnings
    // and indicating a desire to continue anyway. See drupal_requirements_url().
    if ($severity == REQUIREMENT_ERROR || ($severity == REQUIREMENT_WARNING && empty($install_state['parameters']['continue']))) {
      if ($install_state['interactive']) {
        drupal_set_title(st('Requirements problem'));
        $status_report = theme('status_report', array('requirements' => $requirements));
        $status_report .= st('Check the messages and <a href="!url">try again</a>.', array('!url' => check_url(drupal_requirements_url($severity))));
        return new Response($status_report);
      }
      else {
        // Throw an exception showing any unmet requirements.
        $failures = array();
        foreach ($requirements as $requirement) {
          // Skip warnings altogether for non-interactive installations; these
          // proceed in a single request so there is no good opportunity (and no
          // good method) to warn the user anyway.
          if (isset($requirement['severity']) && $requirement['severity'] == REQUIREMENT_ERROR) {
            $failures[] = $requirement['title'] . ': ' . $requirement['value'] . "\n\n" . $requirement['description'];
          }
        }
        if (!empty($failures)) {
          return new Response(implode("\n\n", $failures));
        }
      }
    }
  }

  function install_check_requirements($install_state) {
    $profile = $install_state['parameters']['profile'];

    // Check the profile requirements.
    $requirements = $this->drupal_check_profile($profile);

    // If Drupal is not set up already, we need to create a settings file.
    if (!$install_state['settings_verified']) {
      $writable = FALSE;
      $conf_path = './' . conf_path(FALSE, TRUE);
      $settings_file = $conf_path . '/settings.php';
      $default_settings_file = './sites/default/default.settings.php';
      $file = $conf_path;
      $exists = FALSE;
      // Verify that the directory exists.
      if (drupal_verify_install_file($conf_path, FILE_EXIST, 'dir')) {
        // Check if a settings.php file already exists.
        $file = $settings_file;
        if (drupal_verify_install_file($settings_file, FILE_EXIST)) {
          // If it does, make sure it is writable.
          $writable = drupal_verify_install_file($settings_file, FILE_READABLE|FILE_WRITABLE);
          $exists = TRUE;
        }
      }

      // If default.settings.php does not exist, or is not readable, throw an
      // error.
      if (!drupal_verify_install_file($default_settings_file, FILE_EXIST|FILE_READABLE)) {
        $requirements['default settings file exists'] = array(
          'title'       => st('Default settings file'),
          'value'       => st('The default settings file does not exist.'),
          'severity'    => REQUIREMENT_ERROR,
          'description' => st('The @drupal installer requires that the %default-file file not be modified in any way from the original download.', array('@drupal' => drupal_install_profile_distribution_name(), '%default-file' => $default_settings_file)),
        );
      }
      // Otherwise, if settings.php does not exist yet, we can try to copy
      // default.settings.php to create it.
      elseif (!$exists) {
        $copied = drupal_verify_install_file($conf_path, FILE_EXIST|FILE_WRITABLE, 'dir') && @copy($default_settings_file, $settings_file);
        if ($copied) {
          // If the new settings file has the same owner as default.settings.php,
          // this means default.settings.php is owned by the webserver user.
          // This is an inherent security weakness because it allows a malicious
          // webserver process to append arbitrary PHP code and then execute it.
          // However, it is also a common configuration on shared hosting, and
          // there is nothing Drupal can do to prevent it. In this situation,
          // having settings.php also owned by the webserver does not introduce
          // any additional security risk, so we keep the file in place.
          if (fileowner($default_settings_file) === fileowner($settings_file)) {
            $writable = drupal_verify_install_file($settings_file, FILE_READABLE|FILE_WRITABLE);
            $exists = TRUE;
          }
          // If settings.php and default.settings.php have different owners, this
          // probably means the server is set up "securely" (with the webserver
          // running as its own user, distinct from the user who owns all the
          // Drupal PHP files), although with either a group or world writable
          // sites directory. Keeping settings.php owned by the webserver would
          // therefore introduce a security risk. It would also cause a usability
          // problem, since site owners who do not have root access to the file
          // system would be unable to edit their settings file later on. We
          // therefore must delete the file we just created and force the
          // administrator to log on to the server and create it manually.
          else {
            $deleted = @drupal_unlink($settings_file);
            // We expect deleting the file to be successful (since we just
            // created it ourselves above), but if it fails somehow, we set a
            // variable so we can display a one-time error message to the
            // administrator at the bottom of the requirements list. We also try
            // to make the file writable, to eliminate any conflicting error
            // messages in the requirements list.
            $exists = !$deleted;
            if ($exists) {
              $settings_file_ownership_error = TRUE;
              $writable = drupal_verify_install_file($settings_file, FILE_READABLE|FILE_WRITABLE);
            }
          }
        }
      }

      // If settings.php does not exist, throw an error.
      if (!$exists) {
        $requirements['settings file exists'] = array(
          'title'       => st('Settings file'),
          'value'       => st('The settings file does not exist.'),
          'severity'    => REQUIREMENT_ERROR,
          'description' => st('The @drupal installer requires that you create a settings file as part of the installation process. Copy the %default_file file to %file. More details about installing Drupal are available in <a href="@install_txt">INSTALL.txt</a>.', array('@drupal' => drupal_install_profile_distribution_name(), '%file' => $file, '%default_file' => $default_settings_file, '@install_txt' => base_path() . 'core/INSTALL.txt')),
        );
      }
      else {
        $requirements['settings file exists'] = array(
          'title'       => st('Settings file'),
          'value'       => st('The %file file exists.', array('%file' => $file)),
        );
        // If settings.php is not writable, throw an error.
        if (!$writable) {
          $requirements['settings file writable'] = array(
            'title'       => st('Settings file'),
            'value'       => st('The settings file is not writable.'),
            'severity'    => REQUIREMENT_ERROR,
            'description' => st('The @drupal installer requires write permissions to %file during the installation process. If you are unsure how to grant file permissions, consult the <a href="@handbook_url">online handbook</a>.', array('@drupal' => drupal_install_profile_distribution_name(), '%file' => $file, '@handbook_url' => 'http://drupal.org/server-permissions')),
          );
        }
        else {
          $requirements['settings file'] = array(
            'title'       => st('Settings file'),
            'value'       => st('The settings file is writable.'),
          );
        }
        if (!empty($settings_file_ownership_error)) {
          $requirements['settings file ownership'] = array(
            'title'       => st('Settings file'),
            'value'       => st('The settings file is owned by the web server.'),
            'severity'    => REQUIREMENT_ERROR,
            'description' => st('The @drupal installer failed to create a settings file with proper file ownership. Log on to your web server, remove the existing %file file, and create a new one by copying the %default_file file to %file. More details about installing Drupal are available in <a href="@install_txt">INSTALL.txt</a>. If you have problems with the file permissions on your server, consult the <a href="@handbook_url">online handbook</a>.', array('@drupal' => drupal_install_profile_distribution_name(), '%file' => $file, '%default_file' => $default_settings_file, '@install_txt' => base_path() . 'core/INSTALL.txt', '@handbook_url' => 'http://drupal.org/server-permissions')),
          );
        }
      }
    }
    return $requirements;
  }

  /**
   * Check an install profile's requirements.
   *
   * @param $profile
   *   Name of install profile to check.
   * @return
   *   Array of the install profile's requirements.
   */
  function drupal_verify_profile($install_state) {
    $profile = $install_state['parameters']['profile'];

    include_once DRUPAL_ROOT . '/core/includes/file.inc';
    include_once DRUPAL_ROOT . '/core/includes/common.inc';

    $profile_file = DRUPAL_ROOT . "/profiles/$profile/$profile.profile";

    if (!isset($profile) || !file_exists($profile_file)) {
      throw new Exception(install_no_profile_error());
    }
    $info = $install_state['profile_info'];

    // Get a list of modules that exist in Drupal's assorted subdirectories.
    $present_modules = array();
    foreach (drupal_system_listing('/^' . DRUPAL_PHP_FUNCTION_PATTERN . '\.module$/', 'modules', 'name', 0) as $present_module) {
      $present_modules[] = $present_module->name;
    }

    // The install profile is also a module, which needs to be installed after all the other dependencies
    // have been installed.
    $present_modules[] = drupal_get_profile();

    // Verify that all of the profile's required modules are present.
    $missing_modules = array_diff($info['dependencies'], $present_modules);

    $requirements = array();

    if (count($missing_modules)) {
      $modules = array();
      foreach ($missing_modules as $module) {
        $modules[] = '<span class="admin-missing">' . drupal_ucfirst($module) . '</span>';
      }
      $requirements['required_modules'] = array(
        'title'       => st('Required modules'),
        'value'       => st('Required modules not found.'),
        'severity'    => REQUIREMENT_ERROR,
        'description' => st('The following modules are required but were not found. Move them into the appropriate modules subdirectory, such as <em>sites/all/modules</em>. Missing modules: !modules', array('!modules' => implode(', ', $modules))),
      );
    }
    return $requirements;
  }

  function drupal_check_profile($profile) {
    include_once DRUPAL_ROOT . '/core/includes/file.inc';

    $profile_file = DRUPAL_ROOT . "/profiles/$profile/$profile.profile";

    if (!isset($profile) || !file_exists($profile_file)) {
      throw new Exception(install_no_profile_error());
    }

    $info = install_profile_info($profile);

    // Collect requirement testing results.
    $requirements = array();
    foreach ($info['dependencies'] as $module) {
      module_load_install($module);
      $function = $module . '_requirements';
      if (function_exists($function)) {
        $requirements = array_merge($requirements, $function('install'));
      }
    }
    return $requirements;
  }
}
