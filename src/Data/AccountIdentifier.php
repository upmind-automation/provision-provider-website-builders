<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\WebsiteBuilders\Data;

use Upmind\ProvisionBase\Provider\DataSet\DataSet;
use Upmind\ProvisionBase\Provider\DataSet\Rules;

/**
 * @property-read string|integer $account_reference
 * @property-read string $domain_name
 */
class AccountIdentifier extends DataSet
{
    public static function rules(): Rules
    {
        return new Rules([
            'account_reference' => ['required'],
            'domain_name' => ['required', 'domain_name'],
        ]);
    }
}
