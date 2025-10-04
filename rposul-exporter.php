<?php

/**
 *  *
 * @link              http://www.cubeat.com.br
 * @since             1.0.0
 * @package           Rposul_Exporter
 *
 * @wordpress-plugin
 * Plugin Name:       Exportador Jornal O Sul
 * Plugin URI:        http://www.cubeat.com.br/
 * Description:       Este plugin faz a geração assistida de um PDF a partir dos posts existentes no portal O Sul.
 * Version:           3.3.1
 * Author:            Cubeat Soluções em tecnologia Ltda.
 * Author URI:        http://www.cubeat.com.br/
 * Text Domain:       rposul-exporter
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

date_default_timezone_set('America/Sao_Paulo');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-rposul-exporter-activator.php
 */
function activate_rposul_exporter() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-rposul-exporter-activator.php';
    Rposul_Exporter_Activator::activate();
}

function update_db_check_rposul_exporter() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-rposul-exporter-activator.php';
    Rposul_Exporter_Activator::install_db();    
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-rposul-exporter-deactivator.php
 */
function deactivate_rposul_exporter() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-rposul-exporter-deactivator.php';
    Rposul_Exporter_Deactivator::deactivate();
}

register_activation_hook(plugin_basename(__FILE__), 'activate_rposul_exporter');
register_deactivation_hook(plugin_basename(__FILE__), 'deactivate_rposul_exporter');
add_action( 'plugins_loaded', 'update_db_check_rposul_exporter' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-rposul-exporter.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.1.0
 */
function run_rposul_exporter() {
    $plugin = new Rposul_Exporter();
    $plugin->run();
}

run_rposul_exporter();
