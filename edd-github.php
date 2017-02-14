<?php
// based on https://github.com/easydigitaldownloads/EDD-Extension-Boilerplate
/**
 * Plugin Name: Easy Digital Downloads Github
 * Plugin URI:  https://github.com/nwoetzel/edd-github
 * Description: This plugin extends easy-digital-downloads adding downloads from github repositories.
 * Version:     1.0.0
 * Author:      Nils Woetzel
 * Author URI:  https://github.com/nwoetzel
 * Text Domain: edd-github
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_Github' ) ) {

/**
 * Main EDD_Github class
 *
 * @since 1.0.0
 */
class EDD_Github {

    /**
     * @var EDD_Github $instance The one true EDD_Github
     * @since       1.0.0
     */
    private static $instance;

    /**
     * Get active instance
     *
     * @access      public
     * @since       1.0.0
     * @return      object self::$instance The one true EDD_Github
     */
    public static function instance() {
        if( !self::$instance ) {
            self::$instance = new EDD_Github();
            self::$instance->setup_constants();
            self::$instance->includes();
//            self::$instance->load_textdomain();
            self::$instance->hooks();
        }
        return self::$instance;
    }

    /**
     * Setup plugin constants
     *
     * @access      private
     * @since       1.0.0
     * @return      void
     */
    private function setup_constants() {
        // Plugin version
        define( 'EDD_GITHUB_VER', '1.0.0' );
        // Plugin path
        define( 'EDD_GITHUB_DIR', plugin_dir_path( __FILE__ ) );
        // Plugin URL
        define( 'EDD_GITHUB_URL', plugin_dir_url( __FILE__ ) );
    }

    /**
     * Include necessary files
     *
     * @access      private
     * @since       1.0.0
     * @return      void
     */
    private function includes() {
        // Include scripts
//        require_once EDD_PLUGIN_NAME_DIR . 'includes/scripts.php';
//        require_once EDD_PLUGIN_NAME_DIR . 'includes/functions.php';
        /**
         * @todo        The following files are not included in the boilerplate, but
         *              the referenced locations are listed for the purpose of ensuring
         *              path standardization in EDD extensions. Uncomment any that are
         *              relevant to your extension, and remove the rest.
         */
//        require_once EDD_PLUGIN_NAME_DIR . 'includes/shortcodes.php';
//        require_once EDD_PLUGIN_NAME_DIR . 'includes/widgets.php';
    }

    /**
     * Run action and filter hooks
     *
     * @access      private
     * @since       1.0.0
     * @return      void
     */
    private function hooks() {
        // metabox for github download
        add_filter('edd_meta_box_settings_fields', array( $this, 'add_github_metabox'), 30 );
        add_filter('edd_metabox_fields_save', array( $this, 'save_github_metabox' ) );

        // Handle licensing
//        if( class_exists( 'EDD_License' ) ) {
//            $license = new EDD_License( __FILE__, 'VC Integration', EDD_VC_INTEGRATION_VER, 'Nils Woetzel' );
//        }
    }

    /**
     * Add metabox settings to a download to enter github information
     *
     * @access      private
     * @since       1.0.0
     * @param       int post_id
     * @return      void
     */
    public function add_github_metabox( $post_id = 0 ) {
        $github_user = get_post_meta( $post_id, '_edd_github_user', true );
        $github_repo = get_post_meta( $post_id, '_edd_github_repo', true );
        $github_token = get_post_meta( $post_id, '_edd_github_token', true );

        // heading
        $metabox  = '<strong>Github repo</strong>';

        // username
        $metabox .= '<p>';
        $metabox .= '<label for="_edd_github_user">Username</label>';
        $metabox .= EDD()->html->text( array(
            'name' => '_edd_github_user',
            'value' => $github_user,
            'class' => 'large-text',
        ) );
        $metabox .= '</p>';

        // repo name
        $metabox .= '<p>';
        $metabox .= '<label for="_edd_github_repo">Repository</label>';
        $metabox .= EDD()->html->text( array(
            'name' => '_edd_github_repo',
            'value' => $github_repo,
            'class' => 'large-text',
        ) );
        $metabox .= '</p>';

        // accesstoken as required for private repos
        $metabox .= '<p>';
        $metabox .= '<label for="_edd_github_repo">Access token</label>';
        $metabox .= EDD()->html->text( array(
            'name' => '_edd_github_token',
            'value' => $github_token,
            'class' => 'large-text',
        ) );
        $metabox .= '</p>';

        echo $metabox;
    }

    /**
     * Add meta fields that are added thourgh the metabox and need to be saved.
     *
     * @access      private
     * @since       1.0.0
     * @param       string[] $fields all current fields
     * @return      string[] extended $fields by the additional fields that need to be saved
     */
    public function save_github_metabox( $fields ) {
        $fields[] = '_edd_github_user';
        $fields[] = '_edd_github_repo';
        $fields[] = '_edd_github_token';

        return $fields;
    }

}

} // End if class_exists check

/**
 * The main function responsible for returning the one true EDD_Github
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \EDD_Github The one true EDD_Github
 */
function edd_github_load() {
    if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
        if( ! class_exists( 'EDD_Extension_Activation' ) ) {
            require_once 'includes/class.extension-activation.php';
        }
        $activation = new EDD_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
        $activation = $activation->run();
    } else {
        return EDD_Github::instance();
    }
}
add_action( 'plugins_loaded', 'edd_github_load' );

/**
 * The activation hook is called outside of the singleton because WordPress doesn't
 * register the call from within the class, since we are preferring the plugins_loaded
 * hook for compatibility, we also can't reference a function inside the plugin class
 * for the activation function. If you need an activation function, put it here.
 *
 * @since       1.0.0
 * @return      void
 */
function edd_github_activation() {
    /* Activation functions here */
}
register_activation_hook( __FILE__, 'edd_github_activation' );

/**
 * A nice function name to retrieve the instance that's created on plugins loaded
 *
 * @since 1.0.0
 * @return \EDD_Github
 */
function edd_github() {
    return edd_github_load();
}
