<?php

/**
 * Plugin Name:       Cégem360 MarketingHub
 * Description:       A WordPress-űrlapok (Contact Form 7, WPForms, Elementor Pro, Gravity Forms) beküldéseit továbbítja a MarketingHubba, ahol érdeklődőként jelennek meg.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Cégem360
 * Author URI:        https://cegem360.eu
 * License:           MIT
 * Text Domain:       cegem360-marketinghub
 */
if (! defined('ABSPATH')) {
    exit;
}

define('CEGEM360_MH_VERSION', '1.0.0');

require_once __DIR__.'/includes/class-payload.php';
require_once __DIR__.'/includes/class-sender.php';
require_once __DIR__.'/includes/class-integrations.php';
require_once __DIR__.'/includes/class-settings.php';

Cegem360_MarketingHub_Integrations::register();
Cegem360_MarketingHub_Settings::register();

register_uninstall_hook(__FILE__, 'cegem360_mh_uninstall');

function cegem360_mh_uninstall(): void
{
    delete_option(Cegem360_MarketingHub_Settings::OPTION_CONNECTIONS);
    delete_option(Cegem360_MarketingHub_Sender::OPTION_LOG);
}
