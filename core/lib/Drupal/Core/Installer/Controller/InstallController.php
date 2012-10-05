<?php

namespace Drupal\Core\Installer\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;


/**
 * This is the most basic installation Controller. It makes handling the
 * install state more convenient.
 */
class InstallController {
  protected $generator;
  protected $request;
  protected $install_state;

  public function __construct(UrlGenerator $generator, Request $request) {
    $session = $request->getSession();
    $session->start();

    $this->generator = $generator;
    $this->request = $request;
    $this->install_state = $session->get('install_state');
  }

  protected function saveInstallState($install_state) {
    $this->request->getSession()->set('install_state', $install_state);
  }
}
