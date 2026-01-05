<?php
/**
 * Plugin Name: Casa Alba - Headless Authentication
 * Plugin URI: https://productoscasaalba.cl
 * Description: Sistema completo de autenticación JWT para aplicaciones headless con Cloudflare Turnstile, rate limiting, gestión de sesiones y analítica.
 * Version: 1.0.0
 * Author: Casa Alba
 * Author URI: https://productoscasaalba.cl
 * License: GPL v3
 * Text Domain: casa-alba-headless-auth
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define constants
define('CASA_ALBA_AUTH_VERSION', '1.0.0');
define('CASA_ALBA_AUTH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CASA_ALBA_AUTH_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Class Casa_Alba_Headless_Auth
 *
 * Main plugin class for headless authentication
 */
class Casa_Alba_Headless_Auth {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * JWT Manager instance
     */
    private $jwt_manager;

    /**
     * Rate Limiter instance
     */
    private $rate_limiter;

    /**
     * Session Manager instance
     */
    private $session_manager;

    /**
     * Analytics instance
     */
    private $analytics;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Load dependencies
        $this->load_dependencies();

        // Initialize components
        add_action('plugins_loaded', array($this, 'init_components'));

        // Initialize hooks
        add_action('init', array($this, 'init'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Database tables installation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once CASA_ALBA_AUTH_PLUGIN_DIR . 'includes/class-jwt-manager.php';
        require_once CASA_ALBA_AUTH_PLUGIN_DIR . 'includes/class-rate-limiter.php';
        require_once CASA_ALBA_AUTH_PLUGIN_DIR . 'includes/class-session-manager.php';
        require_once CASA_ALBA_AUTH_PLUGIN_DIR . 'includes/class-analytics.php';
        require_once CASA_ALBA_AUTH_PLUGIN_DIR . 'includes/class-turnstile-validator.php';
        require_once CASA_ALBA_AUTH_PLUGIN_DIR . 'includes/class-auth-api.php';
    }

    /**
     * Initialize components
     */
    public function init_components() {
        $this->jwt_manager = new Casa_Alba_JWT_Manager();
        $this->rate_limiter = new Casa_Alba_Rate_Limiter();
        $this->session_manager = new Casa_Alba_Session_Manager();
        $this->analytics = new Casa_Alba_Auth_Analytics();
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('casa-alba-headless-auth', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Log initialization
        error_log('Casa Alba Headless Auth: Plugin initialized');
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        $auth_api = new Casa_Alba_Auth_API(
            $this->jwt_manager,
            $this->rate_limiter,
            $this->session_manager,
            $this->analytics
        );
        $auth_api->register_routes();
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Headless Auth', 'casa-alba-headless-auth'),
            __('Headless Auth', 'casa-alba-headless-auth'),
            'manage_options',
            'casa-alba-auth',
            array($this, 'render_admin_page'),
            'dashicons-lock',
            30
        );

        add_submenu_page(
            'casa-alba-auth',
            __('Configuración', 'casa-alba-headless-auth'),
            __('Configuración', 'casa-alba-headless-auth'),
            'manage_options',
            'casa-alba-auth',
            array($this, 'render_admin_page')
        );

        add_submenu_page(
            'casa-alba-auth',
            __('Sesiones Activas', 'casa-alba-headless-auth'),
            __('Sesiones Activas', 'casa-alba-headless-auth'),
            'manage_options',
            'casa-alba-auth-sessions',
            array($this, 'render_sessions_page')
        );

        add_submenu_page(
            'casa-alba-auth',
            __('Analítica', 'casa-alba-headless-auth'),
            __('Analítica', 'casa-alba-headless-auth'),
            'manage_options',
            'casa-alba-auth-analytics',
            array($this, 'render_analytics_page')
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('casa_alba_auth_settings', 'casa_alba_auth_jwt_algorithm');
        register_setting('casa_alba_auth_settings', 'casa_alba_auth_jwt_secret');
        register_setting('casa_alba_auth_settings', 'casa_alba_auth_jwt_expiration');
        register_setting('casa_alba_auth_settings', 'casa_alba_auth_jwt_refresh_expiration');
        register_setting('casa_alba_auth_settings', 'casa_alba_auth_turnstile_enabled');
        register_setting('casa_alba_auth_settings', 'casa_alba_auth_turnstile_site_key');
        register_setting('casa_alba_auth_settings', 'casa_alba_auth_turnstile_secret_key');
        register_setting('casa_alba_auth_settings', 'casa_alba_auth_rate_limit_enabled');
        register_setting('casa_alba_auth_settings', 'casa_alba_auth_rate_limit_max_attempts');
        register_setting('casa_alba_auth_settings', 'casa_alba_auth_rate_limit_window');
        register_setting('casa_alba_auth_settings', 'casa_alba_auth_rate_limit_lockout_duration');
        register_setting('casa_alba_auth_settings', 'casa_alba_auth_session_limit');
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'casa-alba-auth') === false) {
            return;
        }

        wp_enqueue_style(
            'casa-alba-auth-admin',
            CASA_ALBA_AUTH_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            CASA_ALBA_AUTH_VERSION
        );

        wp_enqueue_script(
            'casa-alba-auth-admin',
            CASA_ALBA_AUTH_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            CASA_ALBA_AUTH_VERSION,
            true
        );

        wp_localize_script('casa-alba-auth-admin', 'casaAlbaAuth', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('casa_alba_auth_admin')
        ));
    }

    /**
     * Render admin settings page
     */
    public function render_admin_page() {
        include CASA_ALBA_AUTH_PLUGIN_DIR . 'admin/settings-page.php';
    }

    /**
     * Render sessions management page
     */
    public function render_sessions_page() {
        include CASA_ALBA_AUTH_PLUGIN_DIR . 'admin/sessions-page.php';
    }

    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        include CASA_ALBA_AUTH_PLUGIN_DIR . 'admin/analytics-page.php';
    }

    /**
     * Plugin activation
     */
    public function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Create sessions table
        $sessions_table = $wpdb->prefix . 'casa_alba_auth_sessions';
        $sessions_sql = "CREATE TABLE IF NOT EXISTS $sessions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            token_hash varchar(255) NOT NULL,
            refresh_token_hash varchar(255) NOT NULL,
            device_type varchar(50) DEFAULT NULL,
            browser varchar(100) DEFAULT NULL,
            os varchar(100) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            last_activity datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY token_hash (token_hash),
            KEY refresh_token_hash (refresh_token_hash),
            KEY expires_at (expires_at)
        ) $charset_collate;";

        // Create rate limiting table
        $rate_limit_table = $wpdb->prefix . 'casa_alba_auth_rate_limits';
        $rate_limit_sql = "CREATE TABLE IF NOT EXISTS $rate_limit_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            identifier varchar(255) NOT NULL,
            attempts int(11) DEFAULT 0,
            locked_until datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY identifier (identifier),
            KEY locked_until (locked_until)
        ) $charset_collate;";

        // Create analytics table
        $analytics_table = $wpdb->prefix . 'casa_alba_auth_analytics';
        $analytics_sql = "CREATE TABLE IF NOT EXISTS $analytics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            email varchar(255) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            metadata text DEFAULT NULL,
            success tinyint(1) DEFAULT 1,
            error_message text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY user_id (user_id),
            KEY created_at (created_at),
            KEY success (success)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sessions_sql);
        dbDelta($rate_limit_sql);
        dbDelta($analytics_sql);

        // Set default options
        if (!get_option('casa_alba_auth_jwt_algorithm')) {
            add_option('casa_alba_auth_jwt_algorithm', 'HS256');
        }
        if (!get_option('casa_alba_auth_jwt_secret')) {
            add_option('casa_alba_auth_jwt_secret', wp_generate_password(64, true, true));
        }
        if (!get_option('casa_alba_auth_jwt_expiration')) {
            add_option('casa_alba_auth_jwt_expiration', 3600); // 1 hour
        }
        if (!get_option('casa_alba_auth_jwt_refresh_expiration')) {
            add_option('casa_alba_auth_jwt_refresh_expiration', 604800); // 7 days
        }
        if (!get_option('casa_alba_auth_rate_limit_enabled')) {
            add_option('casa_alba_auth_rate_limit_enabled', 1);
        }
        if (!get_option('casa_alba_auth_rate_limit_max_attempts')) {
            add_option('casa_alba_auth_rate_limit_max_attempts', 5);
        }
        if (!get_option('casa_alba_auth_rate_limit_window')) {
            add_option('casa_alba_auth_rate_limit_window', 900); // 15 minutes
        }
        if (!get_option('casa_alba_auth_rate_limit_lockout_duration')) {
            add_option('casa_alba_auth_rate_limit_lockout_duration', 1800); // 30 minutes
        }
        if (!get_option('casa_alba_auth_session_limit')) {
            add_option('casa_alba_auth_session_limit', 5); // Max 5 sessions per user
        }

        error_log('Casa Alba Headless Auth: Plugin activated');
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        error_log('Casa Alba Headless Auth: Plugin deactivated');
    }
}

// Initialize plugin
function casa_alba_headless_auth() {
    return Casa_Alba_Headless_Auth::get_instance();
}

casa_alba_headless_auth();

