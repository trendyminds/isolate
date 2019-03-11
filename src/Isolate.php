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
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\helpers\UrlHelper;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;

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
    public $schemaVersion = '1.0.0';

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
                $event->permissions["General"]["accessCp"]["nested"]["accessPlugin-isolate"]["nested"] = [
                    'isolate:assign' => [
                        'label' => 'Assign permissions',
                    ],
                ];
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

    public function getCpNavItem()
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

    public function getSettingsResponse()
    {
        Craft::$app->controller->redirect(UrlHelper::cpUrl('isolate/settings'));
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }
}
