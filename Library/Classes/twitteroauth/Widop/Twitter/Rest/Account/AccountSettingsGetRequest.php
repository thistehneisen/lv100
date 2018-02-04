<?php

/*
 * This file is part of the Wid'op package.
 *
 * (c) Wid'op <contact@widop.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Widop\Twitter\Rest\Account;

use Widop\Twitter\Rest\AbstractGetRequest;

/**
 * Account settings GET request.
 *
 * @link https://dev.twitter.com/docs/api/1.1/get/account/settings
 *
 * @author Geoffrey Brier <geoffrey.brier@gmail.com>
 */
class AccountSettingsGetRequest extends AbstractGetRequest
{
    /**
     * {@inheritdoc}
     */
    protected function getPath()
    {
        return '/account/settings.json';
    }
}
