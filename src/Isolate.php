<?php
/**
 * Isolate plugin for Craft CMS 3.x
 *
 * Force users to only access a subset of your entries
 *
 * @link      https://trendyminds.com
 * @copyright Copyright (c) 2019 TrendyMinds
 */

namespace trendyminds\isolate;

use trendyminds\isolate\variables\IsolateVariable;
use trendyminds\isolate\models\Settings;
use trendyminds\isolate\assetbundles\Isolate\IsolateAsset;

use Craft;
use craft\base\Plugin;
use craft\elements\Entry;
use craft\events\ModelEvent;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\helpers\UrlHelper;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\ElementHelper;
use craft\helpers\FileHelper;
use craft\services\UserPermissions;
use trendyminds\isolate\records\IsolateRecord;
use yii\base\Event;

/**
 * Class Isolate
 *
 * @author    TrendyMinds
 * @package   Isolate
 * @since     1.0.0
 *
 * @property  IsolateServiceService $isolateService
 */
class Isolate extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var Isolate
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                // Only apply assets if user is logged in
                if (!Craft::$app->user->isGuest) {
                    if (Isolate::$plugin->isolateService->isUserIsolated(Craft::$app->getUser()->id))
                    {
                        Craft::$app->getView()->registerAssetBundle(IsolateAsset::class);
                        Isolate::$plugin->isolateService->verifyIsolatedUserAccess(Craft::$app->getUser()->id, Craft::$app->request->pathInfo);
                    }
                }

                $event->rules = array_merge($event->rules, [
                    "isolate" => "isolate/default/index",
                    "isolate/settings" => "isolate/default/settings",
                    "isolate/dashboard" => "isolate/default/dashboard",
                    "isolate/dashboard/<sectionHandle:{handle}>" => "isolate/default/dashboard",
                    "isolate/users" => "isolate/users/index",
                    "isolate/users/group/<groupId:\d+>" => "isolate/users/index",
                    "isolate/users/user/<userId:\d+>" => "isolate/users/user",
                    "isolate/users/user/<userId:\d+>/<sectionHandle:{handle}>" => "isolate/users/user"
                ]);
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('isolate', IsolateVariable::class);
            }
        );

        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event) {
                $event->permissions["Isolate"] = [
                    'heading' => 'Isolate',
                    'permissions' => [
                        ['label' => 'Assign permissions'],
                    ],
                ];
            }
        );

        Event::on(
            Entry::class,
            Entry::EVENT_AFTER_SAVE,
            function (ModelEvent $event) {
                // Don't do anything if this is a console request
                if (Craft::$app->getRequest()->isConsoleRequest) {
                    return false;
                }

                // Don't do anything if the user is not signed in
                if (Craft::$app->getUser()->isGuest) {
                    return false;
                }

                // If the user isn't isolated
                if (!Isolate::$plugin->isolateService->isUserIsolated(Craft::$app->getUser()->id)) {
                    return false;
                }

                // Ignore anything that isn't an entry
                if (!$event->sender instanceof Entry) {
                    return false;
                }

                // Don't process the revisions
                if (!ElementHelper::isDraftOrRevision($event->sender)) {
                    return false;
                }

                // Don't process non-drafts entries
                if (!$event->sender->draftId) {
                    return false;
                }

                // Don't process drafts of enabled entries
                if ($event->sender->enabled) {
                    return false;
                }

                // If Craft version is lower than 3.4, prefer the old way.
                if (version_compare(Craft::$app->getVersion(), '3.4', '<')) {
                    // Check if the isolated user already has access to this entry, if so, skip it
                    $existingRecord = IsolateRecord::findOne([
                        "userId" => Craft::$app->getUser()->id,
                        "sectionId" => $event->sender->sectionId,
                        "entryId" => $event->sender->id,
                    ]);

                    if ($existingRecord) {
                        return false;
                    }

                    // Otherwise make sure this user has access to this entry that they just created
                    $record = new IsolateRecord;
                    $record->setAttribute('userId', Craft::$app->getUser()->id);
                    $record->setAttribute('sectionId', $event->sender->sectionId);
                    $record->setAttribute('entryId', $event->sender->id);
                    $record->save();

                    return true;
                }

                // Did the user save a draft of a new entry that needs to be isolated, if not exit
                if (!$event->sender->duplicateOf) {
                    return false;
                }

                // Check if the isolated user already has access to this entry, if so, skip it
                $existingRecord = IsolateRecord::findOne([
                    "userId" => Craft::$app->getUser()->id,
                    "sectionId" => $event->sender->sectionId,
                    "entryId" => $event->sender->duplicateOf->id,
                ]);

                if ($existingRecord) {
                    return false;
                }

                // Otherwise make sure this user has access to this entry that they just created
                $record = new IsolateRecord;
                $record->setAttribute('userId', Craft::$app->getUser()->id);
                $record->setAttribute('sectionId', $event->sender->sectionId);
                $record->setAttribute('entryId', $event->sender->duplicateOf->id);
                $record->save();

                return true;
            }
        );

        Craft::info(
            Craft::t(
                'isolate',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    public function getSidebarLabel()
    {
        return Craft::t('isolate', $this->getSettings()->sidebarLabel);
    }

    public function getCpNavItem(): ?array
    {
        $subnav = [];
        $nav = parent::getCpNavItem();

        /**
         * If a user is assigned entries use the display name for the sidebar label
         */
        if (!Craft::$app->user->checkPermission('isolate:assign')) {
            $nav["label"] = $this->getSidebarLabel();
        }

        if (Craft::$app->user->checkPermission('isolate:assign')) {
            $subnav['users'] = [
                "label" => "Users",
                "url" => "isolate/users"
            ];

            $subnav['settings'] = [
                "label" => "Settings",
                "url" => "isolate/settings"
            ];

            $nav = array_merge($nav, [
                'subnav' => $subnav,
            ]);
        }

        return $nav;
    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('isolate/settings'));
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }
}
