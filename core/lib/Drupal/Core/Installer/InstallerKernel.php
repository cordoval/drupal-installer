<?php

namespace Drupal\Core\Installer;

use Symfony\Component\HttpKernel\HttpKernel;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Drupal\Core\CoreBundle;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Description of InstallerKernel
 *
 * @author crell
 */
class InstallerKernel extends HttpKernel {

  protected $container;

  public function __construct(UrlMatcher $matcher) {
      $resolver = new ControllerResolver();

      $dispatcher = new EventDispatcher();
      $dispatcher->addSubscriber(new RouterListener($matcher));
      $dispatcher->addSubscriber(new ResponseListener('UTF-8'));

      parent::__construct($dispatcher, $resolver);
  }

  /**
   * Initializes the service container.
   */
  public function initializeContainer() {
    $this->container = $this->buildContainer();
    $this->container->set('kernel', $this);
    drupal_container($this->container);
  }

  /**
   * Builds the service container.
   *
   * @return ContainerBuilder The compiled service container
   */
  protected function buildContainer() {
    $container = new ContainerBuilder();
    // Return a ContainerBuilder instance with the bare essentials needed for any
    // full bootstrap regardless of whether there will be a DrupalKernel involved.
    // This will get merged with the full Kernel-built Container on normal page
    // requests.
    // Return a ContainerBuilder instance with the bare essentials needed for any
    // full bootstrap regardless of whether there will be a DrupalKernel involved.
    // This will get merged with the full Kernel-built Container on normal page
    // requests.

    $container->register('dispatcher', 'Symfony\Component\EventDispatcher\EventDispatcher');

    $container->register('config.storage', 'Drupal\Core\Config\InstallStorage');
    $container->register('config.factory', 'Drupal\Core\Config\ConfigFactory')
      ->addArgument(new Reference('config.storage'))
      ->addArgument(new Reference('dispatcher'));

    $bundle = new CoreBundle();
    $bundle->build($container);

    return $container;
  }
}
