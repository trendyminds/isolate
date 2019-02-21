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
use trendyminds\isolate\records\IsolateRecord;
use trendyminds\isolate\assetbundles\Isolate\IsolateAsset;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\elements\Entry;
use craft\db\Query;
use yii\web\ForbiddenHttpException;

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
    public function getUsers()
    {
        $users = User::find()
            ->admin(false)
            ->can("accessCp")
            ->all();

        return $users;
    }

    /*
     * @return mixed
     */
    public function getUserSections(int $userId)
    {
        $sections = [];

        // Get all editable sections
        $allSections = Craft::$app->sections->getEditableSections();

        // Filter out the sections that are "singles"
        $allSections = array_filter($allSections, function($section) {
            return $section->type !== "single";
        });

        // Return a new array that outputs the section name and if this user can edit the entries in that content type
        foreach ($allSections as $section) {
            $sections[] = [
                "name" => $section->name,
                "id" => $section->id,
                "handle" => $section->handle,
                "canEdit" => Craft::$app->getUserPermissions()->doesUserHavePermission($userId, "editEntries:{$section->uid}")
            ];
        }

        return $sections;
    }

    /*
     * @return mixed
     */
    public function getUserEntries(int $userId, int $sectionId = null)
    {
        $query = new Query();

        $records = $query
            ->select(["iso.*", "ent.sectionId", "sec.handle"])
            ->from("{{%isolate_permissions}} iso")
            ->leftJoin("{{%entries}} ent", "ent.id=iso.entryId")
            ->leftJoin("{{%sections}} sec", "sec.id=ent.sectionId")
            ->filterWhere([
                "iso.userId" => $userId,
                "ent.sectionId" => $sectionId
            ])
            ->all();

        return $records;
    }

    /*
     * @return mixed
     */
    public function savePermissions(int $userId, array $entries)
    {
        /**
         * If a user has been assigned permissions, enable Isolate automatically to make the workflow contained in one place
         */
        if (count($entries) > 0) {
            $usersPermissions = Craft::$app->userPermissions->getPermissionsByUserId($userId);
            $usersPermissions[] = "accessplugin-isolate";

            Craft::$app->userPermissions->saveUserPermissions($userId, $usersPermissions);
        }

        /**
         * If a user has no assigned permissions disable their access to Isolate
         */
        if (count($entries) === 0) {
            $usersPermissions = Craft::$app->userPermissions->getPermissionsByUserId($userId);

            $usersPermissions = array_filter($usersPermissions, function($permission) {
                return $permission !== "accessplugin-isolate";
            });

            Craft::$app->userPermissions->saveUserPermissions($userId, $usersPermissions);
        }

        /**
         * Remove entries that were de-selected
         */
        $existingEntries = IsolateRecord::findAll([
            "userId" => $userId
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
            $record->setAttribute('entryId', $entryId);

            $record->save();
        }
    }

    public function userIsIsolated(int $userId)
    {
        // If this user is assigned an entry then this user is isolated
        $hasEntry = IsolateRecord::findOne([
            "userId" => $userId
        ]);

        if ($hasEntry) {
            return true;
        }

        return false;
    }

    public function includeIsolatedAssets()
    {
        $currentUserId = Craft::$app->getUser()->id;

        if (Isolate::$plugin->isolateService->userIsIsolated($currentUserId))
        {
            Craft::$app->getView()->registerAssetBundle(IsolateAsset::class);
        }
    }

    public function isEntriesArea()
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

    public function isEntry()
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

    public function canUserAccessEntry(int $userId, int $entryId)
    {
        $entry = Entry::findOne([ "id" => $entryId ]);

        // Is the user restricted to entries within this section?
        $sectionRecords = Isolate::$plugin->isolateService->getUserEntries($userId, $entry->section->id);

        // If not, then the user can edit this entry
        if (count($sectionRecords) === 0)
        {
            return true;
        }

        $record = IsolateRecord::findOne([
            "userId" => $userId,
            "entryId" => $entry->id
        ]);

        if ($record === null) {
            return false;
        }

        return true;
    }

    public function getEntryIdFromUrl()
    {
        $entryUri = Craft::$app->request->getSegment(3);

        preg_match("/^\d*/", $entryUri, $matches);

        return $matches[0];
    }

    public function isUserIsolated(int $userId)
    {
        $record = IsolateRecord::findOne([
            "userId" => $userId
        ]);

        return $record !== null;
    }

    public function checkUserAccess()
    {
        $currentUserId = Craft::$app->getUser()->id;

        // Is this user managed by Isolate?
        // If not, don't bother checking anything else
        if (!Isolate::$plugin->isolateService->isUserIsolated($currentUserId))
        {
            return true;
        }

        // Prevent users from accessing the Entries section
        if (Isolate::$plugin->isolateService->isEntriesArea())
        {
            Isolate::$plugin->isolateService->showAuthError();
        }

        // Are we in an entry page?
        if (Isolate::$plugin->isolateService->isEntry())
        {
            $entryId = Isolate::$plugin->isolateService->getEntryIdFromUrl();

            // Can this user access this entry?
            if (!Isolate::$plugin->isolateService->canUserAccessEntry($currentUserId, $entryId))
            {
                Isolate::$plugin->isolateService->showAuthError();
            }
        }

        return true;
    }

    /**
     * Show authorization error to user
     *
     * @return ForbiddenHttpException
     */
    public function showAuthError()
    {
        throw new ForbiddenHttpException('User is not permitted to perform this action');
    }
}
