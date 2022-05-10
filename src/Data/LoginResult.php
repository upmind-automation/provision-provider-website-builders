<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\WebsiteBuilders\Data;

use Upmind\ProvisionBase\Provider\DataSet\ResultData;
use Upmind\ProvisionBase\Provider\DataSet\Rules;

/**
 * @property-read string $login_url
 */
class LoginResult extends ResultData
{
    public static function rules(): Rules
    {
        return new Rules([
            'login_url' => ['required', 'url'],
        ]);
    }
}
