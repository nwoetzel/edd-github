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
            self::$instance->registerPostMeta();
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

        require_once EDD_GITHUB_DIR . 'includes/class.github-releases.php';
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

        // files from github
        add_filter('edd_download_files', array( $this, 'add_github_files' ), 30, 3 );
        add_filter('sanitize_post_meta_edd_download_files', array( $this, 'remove_github_files' ), 11 );

        // file list
        add_action('edd_download_file_table_head', array( $this, 'fileTableColumnHead' ), 10, 1 );
        add_action('edd_download_file_table_row',array($this,'fileTableRow'),10,3);
        add_filter('edd_file_row_args',array($this,'fileRowArgs'), 10, 2);

        // Handle licensing
//        if( class_exists( 'EDD_License' ) ) {
//            $license = new EDD_License( __FILE__, 'VC Integration', EDD_VC_INTEGRATION_VER, 'Nils Woetzel' );
//        }
    }

    private function registerPostMeta() {
        register_meta(
            'post',
            '_edd_github_user',
            array(
                'type'              => 'string',
                'description'       => 'github username',
                'show_in_rest'      => true,
            )
        );
        register_meta(
            'post',
            '_edd_github_repo',
            array(
                'type'              => 'string',
                'description'       => 'github repository name',
                'show_in_rest'      => true,
            )
        );
        register_meta(
            'post',
            '_edd_github_token',
            array(
                'type'              => 'string',
                'description'       => 'github access token for api use (e.g. with private repositories)',
                'show_in_rest'      => true,
            )
        );
        register_meta(
            'post',
            '_edd_github_tag',
            array(
                'type'              => 'string',
                'description'       => 'github release tag',
                'show_in_rest'      => true,
            )
        );
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
        $github_tag = get_post_meta( $post_id, '_edd_github_tag', true );

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

        // release tag
        $metabox .= '<p>';
        $metabox .= '<label for="_edd_github_tag">Tag (default latest release)</label>';
        $metabox .= EDD()->html->text( array(
            'name' => '_edd_github_tag',
            'value' => $github_tag,
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

        if( $github_user && $github_repo ) {
            $github_releases = new EDD_Github_Releases($github_user, $github_repo, $github_token);
            $release = $github_releases->releases($github_tag);
            if (!empty($release)) {
                $metabox .= '<p>Number assets found: '.count($release->assets).'</p>';
            } else {
                $metabox .= '<p>No release found</p>';
            }
        }

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
        $fields[] = '_edd_github_tag';

        return $fields;
    }

    public function add_github_files( $files, $post_id, $variable_price_id) {
        $github_user = get_post_meta( $post_id, '_edd_github_user', true );
        $github_repo = get_post_meta( $post_id, '_edd_github_repo', true );

        // is there any github setting
        if (empty($github_user) || empty($github_repo)) {
            return $files;
        }

        $github_token = get_post_meta( $post_id, '_edd_github_token', true );
        $github_tag = get_post_meta( $post_id, '_edd_github_tag', true );

        $github_releases = new EDD_Github_Releases($github_user, $github_repo, $github_token);
        $release = $github_releases->releases($github_tag);

        // no release available
        if (empty($release)) {
            return $files;
        }

        // iterate through all assets and add to files
        foreach ($release->assets as $asset) {
            $file_info = array();
            $file_info['index'] = '';
            $file_info['attachment_id'] = 0;
            $file_info['name'] = $asset->name;
            $file_info['file'] = $asset->url;
            $file_info['condition'] = 'all';
            $file_info['github'] = 1;

            $files[] = $file_info;
        }

        return $files;
    }

    public function fileTableColumnHead( $post_id ) {
        echo '<th style="width: 15px">Github</th>'; 
    }

    public function fileRowArgs( $args, $value ) {
       if ( array_key_exists('github',$value) ) {
           $args['github'] = $value['github'];
       } else {
           $args['github'] = 0;
       }

       return $args;
    }

    public function fileTableRow($post_id, $key, $args) {
        $cell  = '<td>';
        $cell .= '<input type="hidden" name="edd_download_files['.absint( $key ).'][github]" class="edd_repeatable_github_field" value="'.($args['github'] ? 'true' : 'false').'"/>';
        $cell .= '<span class="dashicons dashicons-'.($args['github'] ? 'yes' : 'no').'"></span>';
        $cell .= '</td>';

        echo $cell;
    }

    public function remove_github_files( $files ) {
        $sanitized_files = array();
        foreach($files as $key => $file_info) {
            if (array_key_exists('github', $file_info) && $file_info['github'] == true ) {
                continue;
            }
            $sanitized_files[$key] = $file_info;
        }

        return $sanitized_files;
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
