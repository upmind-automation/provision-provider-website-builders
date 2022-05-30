<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\WebsiteBuilders\Providers\BaseKit\Data;

use Upmind\ProvisionBase\Provider\DataSet\DataSet;
use Upmind\ProvisionBase\Provider\DataSet\Rules;

/**
 * @property-read string $api_url
 * @property-read string $username
 * @property-read string $password
 * @property-read string $brand_ref
 * @property-read string $suspension_package_ref
 * @property-read string|null $auto_login_redirect_url
 * @property-read bool $debug_mode
 */
class Configuration extends DataSet
{
    public static function rules(): Rules
    {
        return new Rules([
            'api_url' => ['required', 'url'],
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'brand_ref' => ['required', 'numeric'],
            'suspension_package_ref' => ['required', 'numeric'],
            'auto_login_redirect_url' => ['nullable', 'url'],
            'debug_mode' => ['boolean'],
        ]);
    }
}
