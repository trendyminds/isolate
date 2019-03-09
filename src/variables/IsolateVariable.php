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
    public function getUsers(int $groupId = null)
    {
        return Isolate::$plugin->isolateService->getUsers($groupId);
    }

    public function getUserSections(int $userId)
    {
        return Isolate::$plugin->isolateService->getUserSections($userId);
    }

    public function isUserIsolated(int $userId)
    {
        return Isolate::$plugin->isolateService->isUserIsolated($userId);
    }

    public function getIsolatedEntries(int $userId, string $sectionHandle = null)
    {
        return Isolate::$plugin->isolateService->getIsolatedEntries($userId, $sectionHandle);
    }

    public function getEntries(int $sectionId = null)
    {
        return Isolate::$plugin->isolateService->getEntries($sectionId);
    }
}
