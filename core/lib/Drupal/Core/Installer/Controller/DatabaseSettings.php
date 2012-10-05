<?php
/**
 * @file
 * Handle the database settings.
 */

namespace Drupal\Core\Installer\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * This is installer step 5.
 */
class DatabaseSettings extends InstallController {

  /**
   * Display the database selection form to the user.
   */
  public function interactive() {
    $form = drupal_get_form(array($this, 'form'));

    return new Response(drupal_render($form));
  }

  /**
   * Form constructor for a form to configure and rewrite settings.php.
   */
  public function form($form, &$form_state) {
    global $databases;

    drupal_static_reset('conf_path');
    $conf_path = './' . conf_path(FALSE);
    $settings_file = $conf_path . '/settings.php';
    $database = isset($databases['default']['default']) ? $databases['default']['default'] : array();

    drupal_set_title(st('Database configuration'));

    $drivers = drupal_get_database_types();
    $drivers_keys = array_keys($drivers);

    $form['driver'] = array(
      '#type' => 'radios',
      '#title' => st('Database type'),
      '#required' => TRUE,
      '#default_value' => !empty($database['driver']) ? $database['driver'] : current($drivers_keys),
      '#description' => st('The type of database your @drupal data will be stored in.', array('@drupal' => drupal_install_profile_distribution_name())),
    );
    if (count($drivers) == 1) {
      $form['driver']['#disabled'] = TRUE;
      $form['driver']['#description'] .= ' ' . st('Your PHP configuration only supports a single database type, so it has been automatically selected.');
    }

    // Add driver specific configuration options.
    foreach ($drivers as $key => $driver) {
      $form['driver']['#options'][$key] = $driver->name();

      $form['settings'][$key] = $driver->getFormOptions($database);
      $form['settings'][$key]['#prefix'] = '<h2 class="js-hide">' . st('@driver_name settings', array('@driver_name' => $driver->name())) . '</h2>';
      $form['settings'][$key]['#type'] = 'container';
      $form['settings'][$key]['#tree'] = TRUE;
      $form['settings'][$key]['advanced_options']['#parents'] = array($key);
      $form['settings'][$key]['#states'] = array(
        'visible' => array(
          ':input[name=driver]' => array('value' => $key),
        )
      );
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['save'] = array(
      '#type' => 'submit',
      '#value' => st('Save and continue'),
      '#limit_validation_errors' => array(
        array('driver'),
        array(isset($form_state['input']['driver']) ? $form_state['input']['driver'] : current($drivers_keys)),
      ),
      '#submit' => array(array($this, 'submitCallback')),
    );

    $form['errors'] = array();
    $form['settings_file'] = array('#type' => 'value', '#value' => $settings_file);

    $form['#validate'] = array(array($this, 'validationCallback'));
    $form['#submit'] = array(array($this, 'submitCallback'));

    return $form;
  }

  public function validationCallback($form, &$form_state) {
    $driver = $form_state['values']['driver'];
    $database = $form_state['values'][$driver];
    $database['driver'] = $driver;

    // TODO: remove when PIFR will be updated to use 'db_prefix' instead of
    // 'prefix' in the database settings form.
    $database['prefix'] = $database['db_prefix'];
    unset($database['db_prefix']);

    $form_state['storage']['database'] = $database;
    $errors = install_database_errors($database, $form_state['values']['settings_file']);
    foreach ($errors as $name => $message) {
      form_set_error($name, $message);
    }
  }

  public function submitCallback($form, &$form_state) {
    // Update global settings array and save.
    $settings['databases'] = array(
      'value'    => array('default' => array('default' => $form_state['storage']['database'])),
      'required' => TRUE,
    );
    $settings['drupal_hash_salt'] = array(
      'value'    => drupal_hash_base64(drupal_random_bytes(55)),
      'required' => TRUE,
    );

    drupal_rewrite_settings($settings);

    // Add the config directories to settings.php.
    drupal_install_config_directories();

    // We have valid configuration directories in settings.php.
    // Reset the service container, so the config.storage service will use the
    // actual active storage for installing configuration.
    drupal_container(NULL, TRUE);

    // Indicate that the settings file has been verified, and check the database
    // for the last completed task, now that we have a valid connection. This
    // last step is important since we want to trigger an error if the new
    // database already has Drupal installed.
    $this->install_state['foo'] = 'bar';
    $this->install_state['settings_verified'] = TRUE;
    $this->install_state['config_verified'] = TRUE;
    $this->install_state['database_verified'] = TRUE;
    $this->install_state['completed_task'] = install_verify_completed_task();
    $this->saveInstallState($this->install_state);
  }
}
