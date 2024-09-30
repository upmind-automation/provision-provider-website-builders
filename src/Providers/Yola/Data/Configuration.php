<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\WebsiteBuilders\Providers\Yola\Data;

use Upmind\ProvisionBase\Provider\DataSet\DataSet;
use Upmind\ProvisionBase\Provider\DataSet\Rules;

/**
 * Yola API credentials.
 *
 * @property-read string $auth_key Auth key
 * @property-read string $agent_id AgentID header
 * @property-read string $brand_id Brand ID
 * @property-read bool|null $sandbox
 */
class Configuration extends DataSet
{
    public static function rules(): Rules
    {
        return new Rules([
            'auth_key' => ['required', 'string'],
            'agent_id' => ['string'],
            'brand_id' => ['required', 'string'],
            'sandbox' => ['nullable', 'boolean'],
        ]);
    }
}
