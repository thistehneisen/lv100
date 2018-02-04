<?php

/*
 * This file is part of the Wid'op package.
 *
 * (c) Wid'op <contact@widop.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Widop\Twitter\Rest\SavedSearches;

use Widop\Twitter\Options\OptionBag;
use Widop\Twitter\Options\OptionInterface;
use Widop\Twitter\Rest\AbstractGetRequest;

/**
 * Saved searches show request.
 *
 * @link https://dev.twitter.com/docs/api/1.1/get/saved_searches/show/%3Aid
 *
 * @method string getId()           Gets the saved search id.
 * @method null   setId(string $id) Sets the saved search id.
 *
 * @author Geoffrey Brier <geoffrey.brier@gmail.com>
 */
class SavedSearchesShowRequest extends AbstractGetRequest
{
    /**
     * Creates a saved searches show request.
     *
     * @param string $id The ssaved search identifier.
     */
    public function __construct($id)
    {
        parent::__construct();

        $this->setId($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptionBag(OptionBag $optionBag)
    {
        $optionBag->register('id', OptionInterface::TYPE_PATH);
    }

    /**
     * {@inheritdoc}
     */
    protected function validateOptionBag(OptionBag $optionBag)
    {
        if (!isset($optionBag['id'])) {
            throw new \RuntimeException('You must provide an id.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getPath()
    {
        return '/saved_searches/show/:id.json';
    }
}
