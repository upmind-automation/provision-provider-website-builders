<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\WebsiteBuilders\Providers\Weebly\Data;

use Upmind\ProvisionBase\Provider\DataSet\DataSet;
use Upmind\ProvisionBase\Provider\DataSet\Rules;

/**
 * Weebly API credentials.
 *
 * @property-read string $api_key API key
 * @property-read string $api_secret API Key Secret
 */
class Configuration extends DataSet
{
    public static function rules(): Rules
    {
        return new Rules([
            'api_key' => ['required', 'string'],
            'api_secret' => ['required', 'string'],
        ]);
    }
}
