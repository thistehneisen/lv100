<?php

/*
 * This file is part of the Wid'op package.
 *
 * (c) Wid'op <contact@widop.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Widop\Twitter\Rest\Help;

use Widop\Twitter\Rest\AbstractGetRequest;

/**
 * Help languages request.
 *
 * @link https://dev.twitter.com/docs/api/1.1/get/help/languages
 *
 * @author Geoffrey Brier <geoffrey.brier@gmail.com>
 */
class HelpLanguagesRequest extends AbstractGetRequest
{
    /**
     * {@inheritdoc}
     */
    protected function getPath()
    {
        return '/help/languages.json';
    }
}
