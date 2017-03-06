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

    protected static $permitted_keys = array(
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

//  protected static $download_param = array();

//  protected static $key_param = array();

    protected static function releasesShortcode() {
        return array(
            'name' => __( 'Github Releases', EDD_Github::TEXT_DOMAIN ),
            'base' => 'edd_github_releases',
            'description' => __( 'Get any single value property of the json response from the single release github api', EDD_Github::TEXT_DOMAIN ),
            'category' => 'EDD',
            'params' => array(
//              self::downloadParam(),
//              self::keyParam(),
            )
        );
    }

    public function __construct() {
        add_shortcode( self::releasesShortcode()['base'], array( $this, 'githubReleases') );
        // map shortcodes
        if( function_exists( 'vc_map' ) ) { 
            add_action( 'vc_before_init', array( $this, 'vcMap' ) );
        }
    }

    /**
     * map edd-github shortcodes to visual composer elements
     * http://docs.easydigitaldownloads.com/category/219-short-codes
     *
     * @access      public since it is registered as an action
     * @since       1.2.0
     * @return      void
     */
    public function vcMap() {
        // https://wpbakery.atlassian.net/wiki/pages/viewpage.action?pageId=524332
        vc_map( self::releasesShortcode() );
    }

    /**
     * get information for a github release in a download
     * @since 1.2.0
     * @param $attributes array shortcode attributes
     * @param 
     */
    public function githubReleases( $attributes, $content = null ) {
        $args = shortcode_atts( array(
                'download_id' => null,
                'key' => null,
            ),
            $attributes,
            self::releasesShortcode()['base']
        );

        // only meaningful with an actual key
        if ( !in_array($args['key'], self::$permitted_keys)) {
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

        return $release->{$args['key']};
    }

}
