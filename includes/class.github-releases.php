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
 * EDD_Github_Releases class
 * Access to the releases api for a github repository.
 * Can be used with or withotu an access_token to gain access to a private repository.
 * some code is based on https://code.tutsplus.com/tutorials/distributing-your-plugins-in-github-with-automatic-updates--wp-34817
 *
 * @since 1.0.0
 */
class EDD_Github_Releases {

    /**
     * string denoting the latest release
     * @since 1.0.1
     * @var string
     */
    CONST LATEST = 'latest';

    /**
     * Lifetime of the release cache - 12hours
     * @since 1.0.1
     * @var string
     */
    CONST CACHE_EXPIRATION_HOURS = 12;

    /**
     * Github user name.
     *
     * @access private
     * @since  1.0.0
     * @var string
     */
    private $username;

    /**
     * Github repository name.
     *
     * @access private
     * @since  1.0.0
     * @var string
     */
    private $repository;

    /**
     * Github access token e.g. for private repository.
     *
     * @access private
     * @since  1.0.0
     * @var string
     */
    private $access_token;

    /**
     * Construct form github usernam, reponame and optional access_toekn required for private repositories.
     *
     * @access public
     * @since  1.0.0
     * @param  string $username
     * @param  string $repository
     * @param  string $access_token
     */
    public function __construct( $username, $repository, $access_token = null) {
        $this->username = $username;
        $this->repository = $repository;
        $this->access_token = $access_token;
    }
    
    /**
     * Releases api to report the content of a release with all assets and information
     * https://developer.github.com/v3/repos/releases/
     * Internally caching through wordpress transients is used
     *
     * @access public
     * @since  1.0.0
     * @param  string $tag the release tag of interest (latest release by default if $tag is empty)
     * @return \stdClass|null release object from json response, null if there is no release
     */
    public function releases($tag = null) {
        if (empty($tag)) {
            $tag = self::LATEST;
        }

        $body = $this->getCachedRelease($tag);
        // no cache hit
        if ( $body === false ) {
            // get a fresh reponse
            $body = $this->githubReleases($tag);
            $this->cacheRelease($tag,$body);
        }

        return $body;
    }

    /**
     * Releases api to report the content of a release with all assets and information
     * https://developer.github.com/v3/repos/releases/
     *
     * @access public
     * @since  1.0.1
     * @param  string $tag the release tag of interest
     * @return \stdClass|null release object from json response, null if there is no release
     */
    private function githubReleases($tag) {
        $url = "https://api.github.com/repos/{$this->username}/{$this->repository}/releases";
        if ( $tag == self::LATEST ) {
            // https://developer.github.com/v3/repos/releases/#get-the-latest-release
            $url .= '/latest';
        } else {
            // https://developer.github.com/v3/repos/releases/#get-a-release-by-tag-name
            $url .= '/tags/'.$tag;
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

    /**
     * Adds the access token to a url, of one if available.
     *
     * @access private
     * @since  1.0.0
     * @param  string $url
     * @return string $url with appended access_token if available
     */
    private function addAccessToken($url) {
        if( empty($this->access_token ) ) {
            return $url;
        }

        return add_query_arg( array( "access_token" => $this->access_token ), $url );
    }

    /**
     * cache the given body with that tag in a transient
     * @see githubReleases
     * @see getCachedRelease
     *
     * @access private
     * @since  1.0.1
     * @param  string $tag tag of the release
     * @param  \stdClass $body the json_decoded github api repsonse
     * @return void
     */
    private function cacheRelease($tag,$body) {
        set_transient( $this->transientName($tag),$body, self::CACHE_EXPIRATION_HOURS * HOUR_IN_SECONDS);
    }

    /**
     * get the cached release for the given tag
     * @see https://codex.wordpress.org/Function_Reference/get_transient
     * @see githubReleases
     * @see getCachedRelease
     *
     * @access private
     * @since  1.0.1
     * @param  string $tag tag of the release
     * @return \stdClass|bool the json_decoded github api repsonse or false, if the cache does not exist
     */
    private function getCachedRelease($tag) {
        return get_transient( $this->transientName($tag));
    }

    /**
     * helper function to assemble the transient name
     * assembled from the github user, repository and $tag
     *
     * @access private
     * @since  1.0.1
     * @param  string $tag tag of the release
     * @return string the transient name
     */
    private function transientName($tag) {
        return "edd_github_{$this->username}_{$this->repository}_{$tag}";
    }

}
