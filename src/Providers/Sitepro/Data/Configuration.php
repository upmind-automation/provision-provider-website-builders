<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\WebsiteBuilders\Providers\Sitepro\Data;

use Upmind\ProvisionBase\Provider\DataSet\DataSet;
use Upmind\ProvisionBase\Provider\DataSet\Rules;

/**
 * Site.pro API credentials and configuration.
 *
 * @property-read string $username Site.pro API username
 * @property-read string $password Site.pro API password
 * @property-read string|null $api_url On-premises server base URL. Leave empty for Site.pro cloud.
 *
 * Publication type
 * @property-read string|null $publish_type One of: external, internal, http, ssh, local (default: http)
 *
 * Publish credentials
 * @property-read string|null $publish_username Username for the publish connection
 * @property-read string|null $publish_password Password for the publish connection
 *
 * external (FTP)
 * @property-read string|null $external_server IP address of the client's FTP server
 * @property-read string|null $external_upload_dir Client document root directory, relative to FTP account root folder (e.g. "/public_html")
 *
 * internal, http
 * @property-read string|null $login_api_url     Endpoint URL for SSO login
 * @property-read string|null $create_api_url    Endpoint URL for site creation
 * @property-read string|null $get_info_api_url  Endpoint URL for fetching site info
 * @property-read string|null $terminate_api_url Endpoint URL for site termination
 *
 * ssh
 * @property-read string|null $ssh_server IP address of the client's SSH server
 * @property-read string|null $ssh_upload_dir Full path to website document root
 *
 * local
 * @property-read string|null $local_upload_dir Full path to website document root
 */
class Configuration extends DataSet
{
    public static function rules(): Rules
    {
        return new Rules([
            'username' => ['required', 'string', 'min:3'],
            'password' => ['required', 'string', 'min:6'],
            'api_url' => ['nullable', 'string'],

            'publish_type' => ['nullable', 'string', 'in:external,internal,http,ssh,local'],

            // publish credentials
            'publish_username' => ['required_unless:publish_type,local', 'nullable', 'string'],
            'publish_password' => ['required_unless:publish_type,local', 'nullable', 'string'],

            // external
            'external_server' => ['required_if:publish_type,external', 'nullable', 'string'],
            'external_upload_dir' => ['required_if:publish_type,external', 'nullable', 'string'],

            // internal, http
            'login_api_url'    => ['required_if:publish_type,http,internal', 'nullable', 'string'],
            'create_api_url'   => ['required_if:publish_type,http,internal', 'nullable', 'string'],
            'get_info_api_url' => ['required_if:publish_type,http,internal', 'nullable', 'string'],
            'terminate_api_url' => ['required_if:publish_type,http,internal', 'nullable', 'string'],

            // ssh
            'ssh_server' => ['required_if:publish_type,ssh', 'nullable', 'string'],
            'ssh_upload_dir' => ['required_if:publish_type,ssh', 'nullable', 'string'],

            // local
            'local_upload_dir' => ['required_if:publish_type,local', 'nullable', 'string'],
        ]);
    }
}
