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
use trendyminds\isolate\services\AuthenticationService;
use trendyminds\isolate\records\IsolateRecord;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\elements\Entry;
use craft\db\Query;

/**
 * @author    TrendyMinds
 * @package   Isolate
 * @since     1.0.0
 */
class IsolateService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Get users
     *
     * Fetches all users who can be isolated.
     * This also notes which users are "isolated"
     * Does not include admins or users who can't access the control panel
     *
     * @return array
     */
    public function getUsers(int $groupId = null)
    {
        $data = [];
        $isolatedUsers = [];

        $users = User::find()
            ->admin(false)
            ->can("accessCp")
            ->groupId($groupId)
            ->all();

        $isolateEntries = IsolateRecord::find()->all();

        foreach ($isolateEntries as $entry) {
            $isolatedUsers[] = $entry->userId;
        }

        $isolatedUsers = array_values(array_unique($isolatedUsers));

        foreach ($users as $user) {
            $data[] = [
                "id" => $user->id,
                "name" => $user->name,
                "fullName" => $user->fullName,
                "email" => $user->email,
                "dateCreated" => $user->dateCreated,
                "isIsolated" => in_array($user->id, $isolatedUsers),
            ];
        }

        return $data;
    }

    /**
     * Gets the sections a user has access to
     *
     * This also notes what sections are "isolated"
     *
     * @param integer $userId
     * @return array
     */
    public function getUserSections(int $userId)
    {
        $data = [];
        $isolatedSections = [];

        $sections = Craft::$app->sections->getAllSections();

        $isolateEntries = IsolateRecord::findAll([
            "userId" => $userId
        ]);

        foreach ($isolateEntries as $entry) {
            $isolatedSections[] = $entry->sectionId;
        }

        $isolatedSections = array_values(array_unique($isolatedSections));

        foreach ($sections as $section) {
            $data[] = [
                "id" => $section->id,
                "name" => $section->name,
                "handle" => $section->handle,
                "isIsolated" => in_array($section->id, $isolatedSections),
            ];
        }

        return $data;
    }

    /**
     * Get all entries (and by section)
     * A more performant way of pulling *all* entries from Craft — limited to id, title and section handle
     *
     * @param string $sectionHandle
     * @return array
     */
    public function getAllEntries(int $sectionId = null)
    {
        $query = new Query();
        $entries = $query->select(["ent.id", "con.title", "sec.handle"])
            ->from("{{%entries}} ent")
            ->leftJoin("{{%content}} con", "con.elementId=ent.id")
            ->leftJoin("{{%sections}} sec", "sec.id=ent.sectionId")
            ->filterWhere(['sec.id' => $sectionId])
            ->orderBy("con.title")
            ->all();

        return $entries;
    }

    /**
     * Returns the isolated entries for a given user
     *
     * @param integer $userId
     * @param string $sectionHandle
     * @return Entry
     */
    public function getIsolatedEntries(int $userId, int $sectionId = null)
    {
        return IsolateRecord::findAll([
            "userId" => $userId,
            "sectionId" => $sectionId
        ]);
    }

    /**
     * Modifies database record of an isolated user (adds/edit/removes)
     *
     * @param integer $userId
     * @param array $entries
     * @return void
     */
    public function modifyRecords(int $userId, int $sectionId, array $entries)
    {
        /**
         * Remove entries that were de-selected
         */
        $existingEntries = IsolateRecord::findAll([
            "userId" => $userId,
            "sectionId" => $sectionId
        ]);

        $existingEntries = array_map(function($permission) {
            return $permission->entryId;
        }, $existingEntries);

        $entriesToRemove = array_values(array_diff($existingEntries, $entries));

        foreach ($entriesToRemove as $entryId)
        {
            $record = IsolateRecord::findOne([
                "entryId" => $entryId
            ]);

            $record->delete();
        }

        /**
         * Add entries that are new selections
         */
        $entriesToAdd = array_values(array_diff($entries, $existingEntries));

        foreach ($entriesToAdd as $entryId)
        {
            $record = new IsolateRecord;
            $record->setAttribute('userId', $userId);
            $record->setAttribute('sectionId', $sectionId);
            $record->setAttribute('entryId', $entryId);
            $record->save();
        }
    }

    /**
     * Is the user isolated?
     *
     * @param integer $userId
     * @return boolean
     */
    public function isUserIsolated(int $userId)
    {
        // If this user is assigned an entry then this user is isolated
        $userHasIsolateRecord = IsolateRecord::findOne([
            "userId" => $userId
        ]);

        if ($userHasIsolateRecord) {
            return true;
        }

        return false;
    }

    /**
     * Gets the IDs of every entry a user can edit
     * Can be scoped down to specific sections
     *
     * @param integer $userId
     * @param integer $sectionId
     * @return void
     */
    public function getUserEntriesIds(int $userId, int $sectionId = null)
    {
        $isoQuery = new Query();
        $isolatedRecords = $isoQuery->select(["iso.*"])
            ->from("{{%isolate_permissions}} iso")
            ->where(["iso.userId" => $userId])
            ->andFilterWhere(["iso.sectionId" => $sectionId])
            ->all();

        $isolatedSections = null;
        $isolatedEntryIds = [];

        if (count($isolatedRecords) > 0)
        {
            $isolatedSections = array_map(function($record) {
                return $record['sectionId'];
            }, $isolatedRecords);

            $isolatedEntryIds = array_map(function($record) {
                return $record['entryId'];
            }, $isolatedRecords);

            $isolatedSections = array_values(array_unique($isolatedSections));
        }

        $ids = [];

        $secQuery = new Query();
        $sectionEntries = $secQuery->select(["ent.id"])
            ->from("{{%entries}} ent")
            ->leftJoin("{{%sections}} sec", "ent.sectionId=sec.id")
            ->filterWhere(["ent.sectionId" => $sectionId])
            ->andFilterWhere(["not", ["ent.sectionId" => $isolatedSections]])
            ->all();

        foreach ($sectionEntries as $entry)
        {
            $ids[] = $entry['id'];
        }

        $ids = array_merge($ids, $isolatedEntryIds);

        return $ids;
    }

    /**
     * Takes the IDs of every entry a user can access and returns an entry model loop
     *
     * @param integer $userId
     * @param integer $sectionId
     * @return void
     */
    public function getUserEntries(int $userId, int $sectionId = null, int $limit = 50)
    {
        $ids = $this->getUserEntriesIds($userId, $sectionId);

        return Entry::find()
            ->id($ids)
            ->status(null)
            ->limit($limit);
    }
}
