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

use Craft;
use craft\base\Plugin;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\web\twig\variables\UserSession;
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
                $userSession = new UserSession();

                // Only apply assets if user is logged in
                if ($userSession->isLoggedIn()) {
                    Isolate::$plugin->isolateService->includeIsolatedAssets();
                    Isolate::$plugin->isolateService->checkUserAccess();
                }

                $event->rules = array_merge($event->rules, [
                    "isolate" => "isolate/default/index",
                    "isolate/users/<userId:\d+>" => "isolate/default/get-user"
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

    public function getDisplayName()
    {
        return Craft::t('isolate', $this->getSettings()->displayName);
    }

    public function getCpNavItem()
    {
        $item = parent::getCpNavItem();
        $item['label'] = $this->getDisplayName();

        /**
         * If a user can't assign entries then they
         *   1. Don't need to see any of the subnav items (the nav item goes to dashboard by default)
         *   2. They can't see the users area
         */
        if (Craft::$app->user->checkPermission('isolate:assign')) {
            $item['subnav']['dashboard'] = [
                'label' => 'Dashboard',
                'url' => 'isolate'
            ];

            $item['subnav']['users'] = [
                'label' => 'Users',
                'url' => 'isolate/users'
            ];
        }

        return $item;
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

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'isolate/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }
}
