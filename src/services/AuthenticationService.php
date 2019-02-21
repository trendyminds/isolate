<?php
/**
 * Isolate plugin for Craft CMS 3.x
 *
 * Force users to only access a subset of your entries
 *
 * @link      https://trendyminds.com
 * @copyright Copyright (c) 2019 TrendyMinds
 */

namespace trendyminds\isolate\services;

use trendyminds\isolate\Isolate;

use Craft;
use craft\base\Component;

/**
 * @author    TrendyMinds
 * @package   Isolate
 * @since     1.0.0
 */
class AuthenticationService extends Component
{
    /**
     * Is the user currently viewing the Entries area?
     *
     * @return boolean
     */
    public function isUserInEntriesArea()
    {
        if (
            Craft::$app->request->getSegment(1) === "entries" &&
            Craft::$app->request->getSegment(2) !== "" &&
            Craft::$app->request->getSegment(3) === null
        ) {
            return true;
        }

        return false;
    }

    /**
     * Is the user currently viewing an Entry?
     *
     * @return boolean
     */
    public function isUserInEntry()
    {
        if (
            Craft::$app->request->getSegment(1) === "entries" &&
            Craft::$app->request->getSegment(2) !== "" &&
            Craft::$app->request->getSegment(3) !== ""
        ) {
            return true;
        }

        return false;
    }
}
