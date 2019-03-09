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
     * Checks if a given user has the permissions to access an entry
     *
     * @param integer $userId
     * @param integer $entryId
     * @return boolean
     */
    public function canUserAccessEntry(int $userId, int $entryId)
    {
        $allEntries = $this->getUserEntries($userId);

        foreach ($allEntries as $entry)
        {
            if ($entryId === (int) $entry->id)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Takes an entry URL from the control panel and grabs just the ID of the entry
     *
     * @return integer
     */
    public function getEntryIdFromUrl()
    {
        $entryUri = Craft::$app->request->getSegment(3);

        preg_match("/^\d*/", $entryUri, $matches);

        return $matches[0];
    }

    /**
     * Checks if the user has access to the page they requested
     *
     * @return void
     */
    public function checkUserAccess()
    {
        $authenticateCheck = new AuthenticationService();
        $currentUser = Craft::$app->getUser();

        // Is this user managed by Isolate?
        // If not, don't bother checking anything else
        if (!Isolate::$plugin->isolateService->isUserIsolated($currentUser->id))
        {
            return true;
        }

        // Prevent users from accessing the Entries section
        if ($authenticateCheck->isUserInEntriesArea())
        {
            $authenticateCheck->displayError();
        }

        // Are we in an entry page?
        if ($authenticateCheck->isUserInEntry())
        {
            $entryId = Isolate::$plugin->isolateService->getEntryIdFromUrl();

            // Can this user access this entry?
            if (!Isolate::$plugin->isolateService->canUserAccessEntry($currentUser->id, $entryId))
            {
                $authenticateCheck->displayError();
            }
        }

        return true;
    }
}
