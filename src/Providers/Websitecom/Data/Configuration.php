<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\WebsiteBuilders\Providers\Websitecom\Data;

use Upmind\ProvisionBase\Provider\DataSet\DataSet;
use Upmind\ProvisionBase\Provider\DataSet\Rules;

/**
 * Website.com API credentials.
 *
 * @property-read string $reseller_key Reseller key
 * @property-read string $client_id Client ID
 * @property-read bool $debug Whether or not to log API requests and responses
 */
class Configuration extends DataSet
{
    public static function rules(): Rules
    {
        return new Rules([
            'reseller_key' => ['required', 'string'],
            'client_id' => ['required','string'],
            'debug' => ['boolean'],
        ]);
    }
}
