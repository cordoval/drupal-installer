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

    // Check the installation requirements for Drupal and this profile.
    $requirements = install_check_requirements($session);

    // Verify existence of all required modules.
    $requirements += drupal_verify_profile($session);

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
}
