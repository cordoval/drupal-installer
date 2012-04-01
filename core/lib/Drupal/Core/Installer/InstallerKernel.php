<?php

namespace Drupal\Core\Installer;

use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Description of InstallerKernel
 *
 * @author crell
 */
class InstallerKernel extends HttpKernel {

  public function __construct(UrlMatcher $matcher) {
    $resolver = new ControllerResolver();

    $dispatcher = new EventDispatcher();
    $dispatcher->addSubscriber(new RouterListener($matcher));
    $dispatcher->addSubscriber(new ResponseListener('UTF-8'));

    parent::__construct($dispatcher, $resolver);
  }

}
