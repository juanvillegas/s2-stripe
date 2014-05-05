<?php
// Taken from: http://code.tutsplus.com/tutorials/distributing-your-plugins-in-github-with-automatic-updates--wp-34817
class CoolPluginUpdater {
 
    private $slug; // plugin slug
    private $pluginData; // plugin data
    private $username; // GitHub username
    private $repo; // GitHub repo name
    private $pluginFile; // __FILE__ of our plugin
    private $githubAPIResult; // holds data from GitHub
    private $accessToken; // GitHub private repo token
 
    function __construct( $pluginFile, $gitHubUsername, $gitHubProjectName, $accessToken = '' ) {
        add_filter( "pre_set_site_transient_update_plugins", array( $this, "setTransitent" ) );
        add_filter( "plugins_api", array( $this, "setPluginInfo" ), 10, 3 );
        add_filter( "upgrader_post_install", array( $this, "postInstall" ), 10, 3 );
 
        $this->pluginFile = $pluginFile;
        $this->username = $gitHubUsername;
        $this->repo = $gitHubProjectName;
        $this->accessToken = $accessToken;
    }
 
    // Get information regarding our plugin from WordPress
    private function initPluginData() {
        $this->slug = plugin_basename( $this->pluginFile );
		$this->pluginData = get_plugin_data( $this->pluginFile );
    }
 
    // Get information regarding our plugin from GitHub
    private function getRepoReleaseInfo() {
        if ( ! empty( $this->githubAPIResult ) ) {
		    return;
		}

		// Query the GitHub API
		$url = "https://api.github.com/repos/{$this->username}/{$this->repo}/releases";
		 
		// We need the access token for private repos
		if ( ! empty( $this->accessToken ) ) {
		    $url = add_query_arg( array( "access_token" => $this->accessToken ), $url );
		}
		 
		// Get the results
		$this->githubAPIResult = wp_remote_retrieve_body( wp_remote_get( $url ) );
		if ( ! empty( $this->githubAPIResult ) ) {
		    $this->githubAPIResult = @json_decode( $this->githubAPIResult );
		}

		if ( is_array( $this->githubAPIResult ) ) {
		    $this->githubAPIResult = $this->githubAPIResult[0];
		}
    }
 
    // Push in plugin version information to get the update notification
    public function setTransitent( $transient ) {
        if ( empty( $transient->checked ) ) {
		    return $transient;
		}

		$this->initPluginData();
		$this->getRepoReleaseInfo();

		$doUpdate = version_compare( $this->githubAPIResult->tag_name, $transient->checked[$this->slug] );

		if ( $doUpdate == 1 ) {
		    $package = $this->githubAPIResult->zipball_url;
		 
		    // Include the access token for private GitHub repos
		    if ( !empty( $this->accessToken ) ) {
		        $package = add_query_arg( array( "access_token" => $this->accessToken ), $package );
		    }
		 
		    $obj = new stdClass();
		    $obj->slug = $this->slug;
		    $obj->new_version = $this->githubAPIResult->tag_name;
		    $obj->url = $this->pluginData["PluginURI"];
		    $obj->package = $package;
		    $transient->response[$this->slug] = $obj;
		}
		 
		return $transient;
        return $transient;
    }
 
    // Push in plugin version information to display in the details lightbox
    public function setPluginInfo( $false, $action, $response ) {
        // code ehre
        return $response;
    }
 
    // Perform additional actions to successfully install our plugin
    public function postInstall( $true, $hook_extra, $result ) {
        $this->initPluginData();

        $wasActivated = is_plugin_active( $this->slug );

        global $wp_filesystem;
		$pluginFolder = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname( $this->slug );
		$wp_filesystem->move( $result['destination'], $pluginFolder );
		$result['destination'] = $pluginFolder;

		if ( $wasActivated ) {
		    $activate = activate_plugin( $this->slug );
		}
		 
		return $result;
    }
}