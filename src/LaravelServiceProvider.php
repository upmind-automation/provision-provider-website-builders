<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\WebsiteBuilders;

use Upmind\ProvisionBase\Laravel\ProvisionServiceProvider;
use Upmind\ProvisionProviders\WebsiteBuilders\Providers\BaseKit\Provider as BaseKit;

class LaravelServiceProvider extends ProvisionServiceProvider
{
    public function boot()
    {
        $this->bindCategory('website-builders', Category::class);

        $this->bindProvider('website-builders', 'base-kit', BaseKit::class);
    }
}
