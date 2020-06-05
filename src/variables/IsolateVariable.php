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

    public function getUserEntries(int $userId, int $sectionId = null, int $limit = 50, bool $getDrafts = false)
    {
        return Isolate::$plugin->isolateService->getUserEntries($userId, $sectionId, $limit, $getDrafts);
    }

    public function getIsolatedEntries(int $userId, string $sectionHandle = null)
    {
        return Isolate::$plugin->isolateService->getIsolatedEntries($userId, $sectionHandle);
    }

    public function getAllEntries(int $sectionId)
    {
        return Isolate::$plugin->isolateService->getAllEntries($sectionId);
    }

    public function isStructure(int $sectionId)
    {
        return Isolate::$plugin->isolateService->isStructure($sectionId);
    }

    public function getStructureEntries(int $sectionId)
    {
        return Isolate::$plugin->isolateService->getStructureEntries($sectionId);
    }
}
