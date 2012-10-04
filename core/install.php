<?php

/**
 * @file
 * Initiates a browser-based installation of Drupal.
 */

// Change the directory to the Drupal root.
chdir('..');

/**
 * Defines the root directory of the Drupal installation.
 */
define('DRUPAL_ROOT', getcwd());

/**
 * Global flag to indicate the site is in installation mode.
 *
 * The constant is defined using define() instead of const so that PHP
 * versions prior to 5.3 can display proper PHP requirements instead of causing
 * a fatal error.
 */
define('MAINTENANCE_MODE', 'install');

// Exit early if running an incompatible PHP version to avoid fatal errors.
// The minimum version is specified explicitly, as DRUPAL_MINIMUM_PHP is not
// yet available. It is defined in bootstrap.inc, but it is not possible to
// load that file yet as it would cause a fatal error on older versions of PHP.
if (version_compare(PHP_VERSION, '5.3.3') < 0) {
  print 'Your PHP installation is too old. Drupal requires at least PHP 5.3.3. See the <a href="http://drupal.org/requirements">system requirements</a> page for more information.';
  exit;
}

// Start the installer.
require_once DRUPAL_ROOT . '/core/includes/install.core.inc';
//install_drupal();
//install_drupal_2();

// So that the class loader works.
//require_once DRUPAL_ROOT . '/core/includes/bootstrap.inc';

//install_drupal_2_init();

$request = Request::createFromGlobals();

$session = new Session(new NativeSessionStorage());
$session->start();
$request->setSession($session);

// We currently don't have a way of doing non-interactive
$settings = array();
$interactive = empty($settings);

$install_state = $session->get('install_state', array())
    + $settings
    + array('interactive' => $interactive)
    + install_state_defaults();

$session->set('install_state', $install_state);

$routes = new RouteCollection();

$context = new RequestContext();
$matcher = new UrlMatcher($routes, $context);
$generator = new UrlGenerator($routes, $context);

$language_controller = new SelectLanguage($generator, $request);
$profile_controller = new SelectProfile($generator, $request);
$load_profile_controller = new LoadProfile($generator);
$requirements_controller = new VerifyRequirements($generator);

$routes->add('language', new Route('/language', array(
    '_controller' => array($language_controller, 'interactive'),
)));
$routes->add('profile', new Route('/profile', array(
    '_controller' => array($profile_controller, 'interactive'),
)));
$routes->add('home', new Route('/', array(
    '_controller' => function() use ($generator) {
        return new RedirectResponse($generator->generate('language'));
    },
)));

$routes->add('load_profile', new Route('/load_profile', array(
    '_controller' => array($load_profile_controller, 'interactive'),
)));
$routes->add('requirements', new Route('/requirements', array(
    '_controller' => array($requirements_controller, 'interactive'),
)));


$kernel = new InstallerKernel($matcher);
$kernel->handle($request)->send();