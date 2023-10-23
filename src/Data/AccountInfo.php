<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\WebsiteBuilders\Data;

use Upmind\ProvisionBase\Provider\DataSet\ResultData;
use Upmind\ProvisionBase\Provider\DataSet\Rules;

/**
 * @property-read string|integer|null $site_builder_user_id
 * @property-read string|integer $account_reference
 * @property-read string|null $domain_name
 * @property-read string|integer $package_reference
 * @property-read bool $suspended
 * @property-read integer|null $site_count
 * @property-read string|null $storage_used
 * @property-read array|null $extra
 */
class AccountInfo extends ResultData
{
    public static function rules(): Rules
    {
        return new Rules([
            'site_builder_user_id' => ['nullable'],
            'account_reference' => ['required'],
            'domain_name' => ['nullable', 'domain_name'],
            'package_reference' => ['required'],
            'suspended' => ['nullable', 'bool'],
            'site_count' => ['nullable', 'integer'],
            'storage_used' => ['nullable', 'string'],
            'extra' => ['nullable', 'array'],
        ]);
    }
}
