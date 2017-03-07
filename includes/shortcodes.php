<?php

/**
 * Shortcodes
 *
 * @package     EDD_Github\Shortcodes
 * @since       1.2.0
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

class EDD_Github_Shortcodes {

    /**
     * The json response from the github single releases api has several attributes.
     * Here, only the attributes are listed, that have a non-complex value.
     * @see https://developer.github.com/v3/repos/releases/#get-a-single-release
     *
     * @access protected
     * @since  1.2.0
     * @return string[] array of allowed attribute names
     */
    protected static function permittedAttributes() {
        return array(
            'url',
            'html_url',
            'assets_url',
            'upload_url',
            'tarball_url',
            'zipball_url',
            'id',
            'tag_name',
            'target_commitish',
            'name',
            'body',
            'draft',
            'prerelease',
            'created_at',
            'published_at',
        );
    }

    /**
     * This is a shortcode parameter to define the download id.
     * It adds 'Current' to leave the download_id blank - the shortcode will use the current post.
     *
     * @access       protected
     * @since        1.2.0
     * @return       array describing a shortcode parameter
     */
    protected static function downloadParam() {
        return array(
            'param_name' => 'download_id',
            'heading' => __( 'Specific Download', EDD_Github::TEXT_DOMAIN ),
            'description' => __( 'This post is used, if you do not select a download.', EDD_Github::TEXT_DOMAIN ),
            'type' => 'dropdown',
            'value' => array_merge(
                           array( __( 'Current Post', EDD_Github::TEXT_DOMAIN ) => ''), // none selectd => use the current post
                           self::downloads()
                       ),
            'admin_label' => true,
        );
    }

    /**
     * This is a shortcode parameter to define the json attribute to use.
     * @see permittedAttributes
     *
     * @access       protected
     * @since        1.2.0
     * @return       array describing a shortcode parameter
     */
    protected static function attributeParam() {
        return array(
            'param_name' => 'attribute',
            'heading' => 'Attribute',
            'description' => __( 'A single json attribute from the https://developer.github.com/v3/repos/releases/#get-a-single-release response', EDD_Github::TEXT_DOMAIN),
            'type' => 'dropdown',
            'value' => self::permittedAttributes(),
            'std' => 'tag_name',
            'save_always' => true,
            'admin_label' => true,
        );
    }

    /**
     * This is the shortcode callback function, registered with 'add_shortcode'.
     * It adds a shortcode to give direct acces to attribute-value paris in the json response of github single release api call.
     *
     * @access       protected
     * @since        1.2.0
     * @return       array describing the shortcode
     */
    protected static function releasesShortcode() {
        return array(
            'name' => __( 'Github Releases', EDD_Github::TEXT_DOMAIN ),
            'base' => 'edd_github_releases',
            'description' => __( 'Get any non-complex json value from the single release github api call', EDD_Github::TEXT_DOMAIN ),
            'category' => 'EDD',
            'params' => array(
                self::downloadParam(),
                self::attributeParam(),
            )
        );
    }

    /**
     * construct the class and register the shortcode and add action to register with the visual composer
     *
     * @access       public 
     * @since        1.2.0
     * @return       EDD_Github_Shortcode
     */
    public function __construct() {
        add_shortcode( self::releasesShortcode()['base'], array( $this, 'githubReleases') );
        // map shortcodes
        add_action( 'vc_before_init', array( $this, 'vcMap' ) );
    }

    /**
     * map edd-github shortcodes to visual composer elements
     * http://docs.easydigitaldownloads.com/category/219-short-codes
     *
     * @access      public registered as an action
     * @since       1.2.0
     * @return      void
     */
    public function vcMap() {
        // https://wpbakery.atlassian.net/wiki/pages/viewpage.action?pageId=524332
        vc_map( self::releasesShortcode() );
    }

    /**
     * get information for a github release in a download as shortcode
     * @see https://codex.wordpress.org/Shortcode_API
     *
     * @access      public
     * @since       1.2.0
     * @param       $attributes array shortcode attributes
     * @param       $content string if used with content in the shortcode
     * @return      string the value for the attribute, or an empty string if there is an error
     */
    public function githubReleases( $attributes, $content = null ) {
        $args = shortcode_atts( array(
                self::downloadParam()['param_name'] => null,
                self::attributeParam()['param_name'] => null,
            ),
            $attributes,
            self::releasesShortcode()['base']
        );

        // only meaningful with an actual attribute
        if ( !in_array($args['attribute'], self::permittedAttributes())) {
            return '';
        }

        // explicit dowload_id
        $download_id = $args['download_id'];
        // or the current global post
        if( empty($download_id) ) {
            $download_id = get_post()->ID;
        }

        // github info for that post
        $github_info = get_post_meta( $download_id, EDD_Github::GITHUB_META_KEY, true );

        if( empty($github_info) ) {
            return '';
        }
        $github_user = $github_info['user'];
        $github_repo = $github_info['repo'];
        $github_token = $github_info['token'];
        $github_tag = $github_info['tag'];

        if( !$github_user || !$github_repo ) {
            return '';
        }

        $github_releases = new EDD_Github_Releases($github_user, $github_repo, $github_token);
        $release = $github_releases->releases($github_tag);

        return $release->{$args['attribute']};
    }

    /********************
     * Helper functions *
     ********************/

    /**
     * get download posts as array title => id
     *
     * @access      protected
     * @since       1.2.0
     * @return      array post titel => ID
     */
    protected static function downloads() {
        $posts_array = get_posts(array(
            'post_type' => 'download',
            'numberposts' => -1,
            'orderby' => 'post_title',
            'order' => 'ASC',
            'fields' => array('ID','post_title')
        ));
        $downloads = array();
        foreach($posts_array as $post) {
            $downloads[$post->post_title.'_'.$post->ID] = $post->ID;
        }
        return $downloads;
    }

}
