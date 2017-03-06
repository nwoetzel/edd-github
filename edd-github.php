<?php
// based on https://github.com/easydigitaldownloads/EDD-Extension-Boilerplate
/**
 * Plugin Name: Easy Digital Downloads Github
 * Plugin URI:  https://github.com/nwoetzel/edd-github
 * Description: This plugin extends easy-digital-downloads adding downloads from github repositories.
 * Version:     1.1.0
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

    CONST TEXT_DOMAIN = 'edd-github';

    /**
     * @var EDD_Github $instance The one true EDD_Github
     * @since       1.0.0
     */
    private static $instance;

    /**
     * @since       1.2.0
     * @var         EDD_Github_Shortcodes
     */
    public $shortcodes;

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
            self::$instance->shortcodes = new EDD_Github_Shortcodes();
            self::$instance->load_textdomain();
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
        define( 'EDD_GITHUB_VER', '1.1.0' );
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
//        require_once EDD_GITHUB_DIR . 'includes/scripts.php';
//        require_once EDD_GITHUB_DIR . 'includes/functions.php';
        /**
         * @todo        The following files are not included in the boilerplate, but
         *              the referenced locations are listed for the purpose of ensuring
         *              path standardization in EDD extensions. Uncomment any that are
         *              relevant to your extension, and remove the rest.
         */
        require_once EDD_GITHUB_DIR . 'includes/shortcodes.php';
//        require_once EDD_GITHUB_DIR . 'includes/widgets.php';

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

        // handle download
        add_action('edd_process_verified_download', array( $this, 'process_download' ), 10, 4 );

        // Handle licensing
//        if( class_exists( 'EDD_License' ) ) {
//            $license = new EDD_License( __FILE__, 'VC Integration', EDD_GITHUB_VER, 'Nils Woetzel' );
//        }
    }

    /**
     * Internationalization
     *
     * @access      public
     * @since       1.1.0
     * @return      void
     */
    public function load_textdomain() {
        // Set filter for language directory
        $lang_dir = EDD_GITHUB_DIR . '/languages/';
        $lang_dir = apply_filters( 'edd_github_languages_directory', $lang_dir );
        // Traditional WordPress plugin locale filter
        $locale = apply_filters( 'plugin_locale', get_locale(), self::TEXT_DOMAIN );
        $mofile = sprintf( '%1$s-%2$s.mo', self::TEXT_DOMAIN, $locale );
        // Setup paths to current locale file
        $mofile_local   = $lang_dir . $mofile;
        $mofile_global  = WP_LANG_DIR . '/' . self::TEXT_DOMAIN . '/' . $mofile;
        if( file_exists( $mofile_global ) ) {
            // Look in global /wp-content/languages/edd-github/ folder
            load_textdomain( self::TEXT_DOMAIN, $mofile_global );
        } elseif( file_exists( $mofile_local ) ) {
            // Look in local /wp-content/plugins/edd-github/languages/ folder
            load_textdomain( self::TEXT_DOMAIN, $mofile_local );
        } else {
            // Load the default language files
            load_plugin_textdomain( self::TEXT_DOMAIN, false, 'edd-github/languages' );
        }
    }

    /**
     * define the meta key for github release information
     * @since 1.0.0
     * @var string
     */
    CONST GITHUB_META_KEY = '_edd_github';

    /**
     * register post meta for github setup
     *
     * @access      private
     * @since       1.0.0
     * @return      void
     */
    private function registerPostMeta() {
        register_meta(
            'post',
            self::GITHUB_META_KEY,
            array(
                'type'              => 'array',
                'description'       => 'github repo information',
                'show_in_rest'      => true,
            )
        );
    }

    /**
     * Add metabox settings to a download to enter github information
     *
     * @access      private
     * @since       1.0.0
     * @param       int $post_id
     * @return      void
     */
    public function add_github_metabox( $post_id = 0 ) {
        $github_info = get_post_meta( $post_id, self::GITHUB_META_KEY, true );
        $github_user = $github_info['user'];
        $github_repo = $github_info['repo'];
        $github_token = $github_info['token'];
        $github_tag = $github_info['tag'];

        $release = null;
        $version = null;
        if( $github_user && $github_repo ) {
            $github_releases = new EDD_Github_Releases($github_user, $github_repo, $github_token);
            $release = $github_releases->releases($github_tag);
            if (!empty($release)) {
                $version = $release->tag_name;
            }
        }

        // heading
        $metabox  = '<strong>Github repo</strong>';

        // username
        $metabox .= '<p>';
        $metabox .= '<label for="'.self::GITHUB_META_KEY.'[user]">'.__('Username', self::TEXT_DOMAIN).'</label>';
        $metabox .= EDD()->html->text( array(
            'name' => self::GITHUB_META_KEY.'[user]',
            'value' => $github_user,
            'class' => 'large-text',
        ) );
        $metabox .= '</p>';

        // repo name
        $metabox .= '<p>';
        $metabox .= '<label for="'.self::GITHUB_META_KEY.'[repo]">'.__('Repository', self::TEXT_DOMAIN).'</label>';
        $metabox .= EDD()->html->text( array(
            'name' => self::GITHUB_META_KEY.'[repo]',
            'value' => $github_repo,
            'class' => 'large-text',
        ) );
        $metabox .= '</p>';

        // release tag
        $metabox .= '<p>';
        $metabox .= '<label for="'.self::GITHUB_META_KEY.'[tag]">'.__('Tag (default latest release)', self::TEXT_DOMAIN).'</label>';
        $metabox .= EDD()->html->text( array(
            'name' => self::GITHUB_META_KEY.'[tag]',
            'value' => $github_tag,
            'placeholder' => $version,
            'class' => 'large-text',
        ) );
        $metabox .= '</p>';

        // accesstoken as required for private repos
        $metabox .= '<p>';
        $metabox .= '<label for="'.self::GITHUB_META_KEY.'[token]">'.__('Access token', self::TEXT_DOMAIN).'</label>';
        $metabox .= EDD()->html->text( array(
            'name' => self::GITHUB_META_KEY.'[token]',
            'value' => $github_token,
            'class' => 'large-text',
        ) );
        $metabox .= '</p>';

        if( !empty($release) ) {
            $version = empty($github_tag) ? ($release->tag_name.__(' (latest)', self::TEXT_DOMAIN)) : $github_tag;
            $metabox .= '<p>'.__('Version', self::TEXT_DOMAIN).': '.$version.'</p>';
            $metabox .= '<p>'.__('Published at', self::TEXT_DOMAIN).': '.$release->published_at.'</p>';
            $metabox .= '<p>'.__('Number assets found', self::TEXT_DOMAIN).': '.count($release->assets).'</p>';
        } else {
            $metabox .= '<p>'.__('No release found!', self::TEXT_DOMAIN).'</p>';
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
        $fields[] = self::GITHUB_META_KEY;

        return $fields;
    }

    /**
     * Add files from a github release to the edd_download_files array.
     * github files have additional keys:
     * 'github' - indicates if the file is a github file
     * 'github_asset' - the asset object derived from the json response
     *
     * @access      public
     * @since       1.0.0
     * @param       array $files all current files
     * @param       int $post_id the id of the download post
     * @param       int $variable_price_id
     * @return      array $files array extended by the assets of the github release
     */
    public function add_github_files( $files, $post_id, $variable_price_id) {
        $github_info = get_post_meta( $post_id, self::GITHUB_META_KEY, true );
        $github_user = $github_info['user'];
        $github_repo = $github_info['repo'];

        // is there any github setting
        if (empty($github_user) || empty($github_repo)) {
            return $files;
        }

        $github_token = $github_info['token'];
        $github_tag = $github_info['tag'];

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
            $file_info['file'] = $asset->browser_download_url;
            $file_info['condition'] = 'all';
            $file_info['github'] = 1;
            $file_info['github_asset'] = $asset;

            $files[] = $file_info;
        }

        return $files;
    }

    /**
     * Adds the header to the download files table in the post edit view.
     *
     * @access      public
     * @since       1.0.0
     * @param       int $post_id the download id of the post.
     * @return      void
     */
    public function fileTableColumnHead( $post_id ) {
        echo '<th style="width: 15px">Github</th>'; 
    }

    /**
     * Adds the github arg for rendering the additional github column.
     *
     * @access      public
     * @since       1.0.0
     * @param       int $post_id the download id of the post.
     * @return      array $args extended by github key 
     */
    public function fileRowArgs( $args, $value ) {
       if ( array_key_exists('github',$value) ) {
           $args['github'] = $value['github'];
       } else {
           $args['github'] = 0;
       }

       return $args;
    }

    /**
     * Renders the cell for the file row with github information.
     *
     * @access      public
     * @since       1.0.0
     * @param       int $post_id the download id of the post.
     * @param       int $file_key the current file key, i.e. index in edd_download_files array
     * @param       array $args the args for the current file
     * @return      void 
     */
    public function fileTableRow($post_id, $key, $args) {
        $cell  = '<td>';
        $cell .= '<input type="hidden" name="edd_download_files['.absint( $key ).'][github]" value="'.($args['github'] ? 'true' : '').'"/>';
        if ($args['github']) {
            $cell .= '<div class="chosen-container">';
            $cell .= '<span class="dashicons dashicons-yes"></span>';
            $cell .= '</div>';
        }
        $cell .= '</td>';

        echo $cell;
    }

    /**
     * Removes all github files before saving a donwload post, since they were just added for information.
     *
     * @access      public
     * @since       1.0.0
     * @param       array $files the POST content for all files
     * @return      array $files wihout files that had the key 'github'.
     */
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

    /**
     * Serves a github file, if the given files is linked to a github asset.
     *
     * @access      public
     * @since       1.0.0
     * @param       int $download_id the download post id
     * @param       string $email the customer email
     * @param       int $payment the payment
     * @param       array $args all args for the download link
     * @return      void either returns if the file is not a github asset, or serve the file content and exit
     */
    public function process_download($download_id, $email, $payment, $args) {
        $download_files = edd_get_download_files( $download_id );
        $file_key = $args['file_key'];
        $file_info = $download_files[ $file_key ];

        // is this a github download?
        if (!$file_info['github']) {
            return;
        }

        $asset = $file_info['github_asset'];
        $requested_file_url = $asset->url;
        $requested_filename = $asset->name;
        $ctype = $asset->content_type; 
        $size = $asset->size;

        $method = 'github';

        do_action( 'edd_process_download_pre_record_log', $requested_file, $args, $method );

        // Record this file download in the log
	$user_info = array();
	$user_info['email'] = $email;
        if ( is_user_logged_in() ) {
            $user_data         = get_userdata( get_current_user_id() );
            $user_info['id']   = get_current_user_id();
            $user_info['name'] = $user_data->display_name;
        }
        edd_record_download_in_log( $download_id, $file_key, $user_info, edd_get_ip(), $payment, $args['price_id'] );

        // deliver the content of the file
        $github_token = get_post_meta( $download_id, self::GITHUB_META_KEY, true )['token'];

        if (!empty($github_token)) {
            $requested_file_url =  add_query_arg( array( "access_token" => $github_token ), $requested_file_url ); 
        }

        nocache_headers();
        header("Robots: none");
        header("Content-Type: " . $ctype . "");
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=\"" . apply_filters( 'edd_requested_file_name', $requested_filename ) . "\"");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . $size );
        if( self::read_file( $requested_file_url ) ) {
            // successfully served file
            exit();
        }

        // read file was not successful
        edd_die();
    }

    /**
     * Reads file in chunks so big downloads are possible without changing PHP.INI
     * See http://codeigniter.com/wiki/Download_helper_for_large_files/
     *
     * @access   protected
     * @param    string  $file      The file url
     * @return   bool               status after closing file handle
     */
    static protected function read_file( $file ) {
        $opts = array(
            'http'=>array(
                'method'=>'GET',
                'header'=>'User-Agent: wordpress'."\r\n".
                          'Content-type: application/x-www-form-urlencoded'."\r\n".
                          'Accept: application/octet-stream'."\r\n",
            ),
        );
        $context = stream_context_create($opts);
        $handle    = fopen( $file, 'rb', false, $context );

        // file opened successfully?
        if ( false === $handle ) {
            return false;
        }

        // write all data to the output buffer
        fpassthru( $handle );

        return @fclose( $handle );
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
