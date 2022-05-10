<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\WebsiteBuilders;

use Upmind\ProvisionBase\Provider\BaseCategory;
use Upmind\ProvisionBase\Provider\DataSet\AboutData;
use Upmind\ProvisionBase\Provider\DataSet\ResultData;
use Upmind\ProvisionProviders\WebsiteBuilders\Data\AccountIdentifier;
use Upmind\ProvisionProviders\WebsiteBuilders\Data\AccountInfo;
use Upmind\ProvisionProviders\WebsiteBuilders\Data\ChangePackageParams;
use Upmind\ProvisionProviders\WebsiteBuilders\Data\CreateParams;
use Upmind\ProvisionProviders\WebsiteBuilders\Data\LoginResult;
use Upmind\ProvisionProviders\WebsiteBuilders\Data\UnSuspendParams;

/**
 * This provision category contains the common functions used in provisioning
 * flows for website builders and related tools.
 */
abstract class Category extends BaseCategory
{
    public static function aboutCategory(): AboutData
    {
        return AboutData::create()
            ->setName('Website Builders')
            ->setDescription('Provision category for managing accounts of website builder services');
    }

    /**
     * Creates a site builder account.
     */
    abstract public function create(CreateParams $params): AccountInfo;

    /**
     * Gets information about a site builder account such as the main domain name,
     * whether or not it is suspended, usage data etc.
     */
    abstract public function getInfo(AccountIdentifier $params): AccountInfo;

    /**
     * Obtains a signed URL which a user can be redirected to which automatically
     * logs the customer into their account.
     */
    abstract public function login(AccountIdentifier $params): LoginResult;

    /**
     * Update the product/package a site builder account is set to.
     */
    abstract public function changePackage(ChangePackageParams $params): AccountInfo;

    /**
     * Suspends services for a site builder account.
     */
    abstract public function suspend(AccountIdentifier $params): AccountInfo;

    /**
     * Un-suspends services for a site builder account.
     */
    abstract public function unSuspend(UnSuspendParams $params): AccountInfo;

    /**
     * Completely delete a site builder account.
     */
    abstract public function terminate(AccountIdentifier $params): ResultData;
}
