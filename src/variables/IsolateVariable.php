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
    public function users(int $groupId = null)
    {
        return Isolate::$plugin->isolateService->getUsers($groupId);
    }

    public function isUserIsolated(int $userId)
    {
        return Isolate::$plugin->isolateService->isUserIsolated($userId);
    }

    public function getSectionPermissions(int $userId)
    {
        return Isolate::$plugin->isolateService->getSectionPermissions($userId);
    }

    public function getUserEntries(int $userId, string $sectionHandle = null)
    {
        return Isolate::$plugin->isolateService->getUserEntries($userId, $sectionHandle);
    }

    public function getUserSections(int $userId)
    {
        return Isolate::$plugin->isolateService->getUserSections($userId);
    }

    public function getEntries(int $sectionId = null)
    {
        return Isolate::$plugin->isolateService->getEntries($sectionId);
    }

    public function userIsolatedFromSection(int $userId, string $sectionHandle)
    {
        return Isolate::$plugin->isolateService->userIsolatedFromSection($userId, $sectionHandle);
    }
}
