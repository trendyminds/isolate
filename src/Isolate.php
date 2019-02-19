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

use trendyminds\isolate\services\IsolateService as IsolateServiceService;
use trendyminds\isolate\variables\IsolateVariable;
use trendyminds\isolate\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;

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
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
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

        $item['subnav'] = [
            'dashboard' => ['label' => 'Dashboard', 'url' => 'isolate'],
            'users' => ['label' => 'Users', 'url' => 'isolate/users'],
            'help' => ['label' => 'Help', 'url' => 'isolate/help'],
        ];

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
