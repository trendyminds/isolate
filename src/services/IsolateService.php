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
use trendyminds\isolate\assetbundles\Isolate\IsolateAsset;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\elements\Entry;
use craft\db\Query;
use yii\helpers\ArrayHelper;

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
     * Get all entries (and by section)
     * A more performant way of pulling *all* entries from Craft — limited to id, title and section handle
     *
     * @param string $sectionHandle
     * @return array
     */
    public function getEntries(int $sectionId = null)
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
     * Returns all (or section-specific) entries that a user has access to
     *
     * @param integer $userId
     * @param string $sectionHandle
     * @return Entry
     */
    public function getUserEntries(int $userId, int $sectionId = null)
    {
        return IsolateRecord::findAll([
            "userId" => $userId,
            "sectionId" => $sectionId
        ]);

        // $userSections = $this->getSectionPermissions($userId);

        // // If we should only get entries from a specific section
        // if ($sectionHandle)
        // {
        //     // Determine if the user has full or partial access to that section
        //     $section = array_values(array_filter($userSections, function($section) use ($sectionHandle) {
        //         return $section->handle === $sectionHandle;
        //     }));

        //     // If we didn't match a section exit
        //     if (!$section)
        //     {
        //         return false;
        //     }

        //     // Return all entries if the user has full access
        //     if ($section[0]->access === "full")
        //     {
        //         return Entry::find()
        //             ->section($section[0]->handle)
        //             ->all();
        //     }

        //     // Return the subset of entries if the user has partial access
        //     if ($section[0]->access === "isolated")
        //     {
        //         $isolateEntries = IsolateRecord::findAll([
        //             "userId" => $userId
        //         ]);

        //         // Get all entries that match the entries a user is assigned to but only in the section the user requested
        //         return Entry::find()
        //             ->id(ArrayHelper::getColumn($isolateEntries, "entryId"))
        //             ->section($sectionHandle)
        //             ->all();
        //     }
        // }

        // // Get all the entries that a user is assigned
        // $isolateEntries = IsolateRecord::findAll([
        //     "userId" => $userId
        // ]);

        // $userSections = $this->getSectionPermissions($userId);

        // $fullUserSections = array_values(array_filter($userSections, function($section) {
        //     return $section->access === "full";
        // }));

        // // Pull all entries from the sections that a user is not isolated from
        // $entriesFromFullAccessSections = Entry::find()
        //     ->section(ArrayHelper::getColumn($fullUserSections, "handle"))
        //     ->all();

        // // Make a second query to get the entries a user has been assigned
        // $isolateEntries = Entry::find()
        //     ->id(ArrayHelper::getColumn($isolateEntries, "entryId"))
        //     ->all();

        // // Return both sets of entries
        // return array_merge($entriesFromFullAccessSections, $isolateEntries);
    }

    /**
     * Checks if a user is isolated from a specific section
     *
     * @param integer $userId
     * @param string $sectionHandle
     * @return boolean
     */
    public function userIsolatedFromSection(int $userId, string $sectionHandle)
    {
        $userSections = $this->getSectionPermissions($userId);

        $filteredSection = array_filter($userSections, function($section) use ($sectionHandle) {
            return $section->handle === $sectionHandle;
        });

        $filteredSection = array_values($filteredSection);

        if ($filteredSection)
        {
            return $filteredSection[0]->access === "isolated";
        }

        return false;
    }

    /**
     * Returns an array of sections that a user has access to
     * This also denotes if the user has full or partial access to each section
     *
     * @param integer $userId
     * @return array
     */
    public function getSectionPermissions(int $userId)
    {
        // Get all the entries that a user is assigned
        $isolateEntries = IsolateRecord::findAll([
            "userId" => $userId
        ]);


        // Get the handles of the sections a user is isolated from
        $query = new Query();
        $isolateSections = $query->select(["sec.handle"])
            ->from("{{%entries}} ent")
            ->leftJoin("{{%sections}} sec", "sec.id=ent.sectionId")
            ->where(["in", "ent.id", ArrayHelper::getColumn($isolateEntries, "entryId")])
            ->all();

        $isolateSections = ArrayHelper::getColumn($isolateSections, "handle");
        $isolateSections = array_unique($isolateSections);


        // Find the sections a user can edit entries in
        $allSections = Craft::$app->sections->getAllSections();

        $userAccessibleSections = array_filter($allSections, function ($section) use ($userId) {
            return Craft::$app->userPermissions->doesUserHavePermission($userId, "editEntries:{$section->uid}");
        });

        $userAccessibleSections = ArrayHelper::getColumn($userAccessibleSections, "handle");
        $userAccessibleSections = array_values($userAccessibleSections);


        // If a user is assigned entries in a section we need to remove them from our final query
        // This is because the user should only see those *specific* entries and not every entry from that section
        $sectionsWithFullAccess = array_values(array_diff($userAccessibleSections, $isolateSections));


        // Generate final array that contains all sections and their access level for the supplied user
        $sections = [];

        foreach ($sectionsWithFullAccess as $handle)
        {
            $sections[] = (object) [
                "handle" => $handle,
                "access" => "full"
            ];
        }

        foreach ($isolateSections as $handle)
        {
            $sections[] = (object) [
                "handle" => $handle,
                "access" => "isolated"
            ];
        }

        return $sections;
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
     * Injects the Isolated CSS and JS files
     *
     * @return void
     */
    public function includeIsolatedAssets()
    {
        $currentUserId = Craft::$app->getUser()->id;

        if (Isolate::$plugin->isolateService->isUserIsolated($currentUserId))
        {
            Craft::$app->getView()->registerAssetBundle(IsolateAsset::class);
        }
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
