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
    protected function initializeContainer() {

        /** @ */
        $this->container->set('kernel', $this);

        echo "drupal container installer 0\n";
        $this->container = drupal_container(new ContainerBuilder());

        drupal_container($this->container);

        $installerContainer
            ->register('twig.template.engine', 'Drupal\Core\Config\FileStorage')
            ->addArgument(config_get_config_directory(CONFIG_ACTIVE_DIRECTORY));

        echo "drupal container installer 1\n";
    }

    /**
     * Gets a new ContainerBuilder instance used to build the service container.
     *
     * @return ContainerBuilder
     */
    protected function getContainerBuilder() {
        return new ContainerBuilder(new ParameterBag($this->getKernelParameters()));
    }


}
