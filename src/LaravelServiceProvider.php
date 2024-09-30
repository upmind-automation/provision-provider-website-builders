<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\WebsiteBuilders;

use Upmind\ProvisionBase\Laravel\ProvisionServiceProvider;
use Upmind\ProvisionProviders\WebsiteBuilders\Providers\Example\Provider as Example;
use Upmind\ProvisionProviders\WebsiteBuilders\Providers\BaseKit\Provider as BaseKit;
use Upmind\ProvisionProviders\WebsiteBuilders\Providers\Websitecom\Provider as Websitecom;
use Upmind\ProvisionProviders\WebsiteBuilders\Providers\Yola\Provider as Yola;
use Upmind\ProvisionProviders\WebsiteBuilders\Providers\Weebly\Provider as Weebly;

class LaravelServiceProvider extends ProvisionServiceProvider
{
    public function boot()
    {
        $this->bindCategory('website-builders', Category::class);

        // $this->bindProvider('website-builders', 'example', Example::class);

        $this->bindProvider('website-builders', 'base-kit', BaseKit::class);
        $this->bindProvider('website-builders', 'websitecom', Websitecom::class);
        $this->bindProvider('website-builders', 'yola', Yola::class);
        $this->bindProvider('website-builders', 'weebly', Weebly::class);
    }
}
