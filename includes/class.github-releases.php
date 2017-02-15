<?php

/**
 * Releases
 *
 * @package     EDD_Github\Releases
 * @since       1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


/**
 * some code is based on https://code.tutsplus.com/tutorials/distributing-your-plugins-in-github-with-automatic-updates--wp-34817
 */
class EDD_Github_Releases {

    private $username;

    private $repository;

    private $access_token;

    public function __construct( $username, $repository, $access_token = null) {
        $this->username = $username;
        $this->repository = $repository;
        $this->access_token = $access_token;
    }

    
    /**
     * https://developer.github.com/v3/repos/releases/
     **/
    public function releases($tag = null) {
        $url = "https://api.github.com/repos/{$this->username}/{$this->repository}/releases";
        if ( empty($tag) || $tag == 'latest' ) {
            // https://developer.github.com/v3/repos/releases/#get-the-latest-release
            $url .= '/latest';
        } else {
            // https://developer.github.com/v3/repos/releases/#get-a-release-by-tag-name
            $url .= '/tags/{$tag}';
        }
        $url = $this->addAccessToken($url);

        $response = wp_remote_get( $url );
        if ( wp_remote_retrieve_response_code( $response ) != 200 ) {
            return null;
        }

        $body = wp_remote_retrieve_body( $response );
        if ( ! empty( $body ) ) {
            $body = json_decode( $body );
        }

        return $body;
    }

    private function addAccessToken($url) {
        if( empty($this->access_token ) ) {
            return $url;
        }

        return add_query_arg( array( "access_token" => $this->access_token ), $url );
    }

}
