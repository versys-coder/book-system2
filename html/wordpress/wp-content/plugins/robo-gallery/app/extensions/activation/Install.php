<?php
/* @@copyright@ */

// Define the namespace for this class
namespace RoboGallery\app\extensions\activation;

// Ensure that this file is only accessed within the WordPress environment
if (!defined('WPINC')) {
    die; // Exit if accessed directly
}

// Prevent multiple initializations of the class (optional check)
// if (class_exists('Robo_Gallery_Install')) return;

/**
 * Class Install
 * Handles plugin activation and deactivation hooks.
 */
class Install {
    /**
     * Constructor
     * Initializes the hooks when the class is instantiated.
     */
    public function __construct() {
        $this->hooks(); // Register activation and deactivation hooks
    }

    /**
     * Register activation and deactivation hooks.
     */
    public function hooks() {
        // Register the activation hook for the plugin
        register_activation_hook(ROBO_GALLERY_MAIN_FILE, array($this, 'activation'));

        // Register the deactivation hook for the plugin
        register_deactivation_hook(ROBO_GALLERY_MAIN_FILE, array($this, 'deactivation'));
    }

    /**
     * Activation logic for the plugin.
     * This method is called when the plugin is activated.
     */
    public function activation() {
        // Store an option in the database to indicate post-installation state
        update_option('robo_gallery_after_install', '1');
    }

    /**
     * Deactivation logic for the plugin.
     * This method is called when the plugin is deactivated.
     */
    public function deactivation() {
        // Add any deactivation logic here if needed
        // For example, you can delete options, clear caches, or perform cleanup tasks
    }
}

// Instantiate the class
// Uncomment this line if you want to initialize the class automatically
// new Install();