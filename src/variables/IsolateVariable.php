<?php
/**
 * Isolate plugin for Craft CMS 3.x
 *
 * Force users to only access a subset of your entries
 *
 * @link      https://trendyminds.com
 * @copyright Copyright (c) 2019 TrendyMinds
 */

namespace trendyminds\isolate\variables;

use trendyminds\isolate\Isolate;

use Craft;

/**
 * @author    TrendyMinds
 * @package   Isolate
 * @since     1.0.0
 */
class IsolateVariable
{
    // Public Methods
    // =========================================================================

    public function displayName()
    {
        return Isolate::$plugin->settings->displayName;
    }

    public function users()
    {
        return Isolate::$plugin->isolateService->getUsers();
    }

    public function getAssignedEntries(int $userId, int $sectionId = NULL)
    {
        return Isolate::$plugin->isolateService->getAssignedEntries($userId, $sectionId);
    }
}
