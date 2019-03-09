<?php
/**
 * Isolate plugin for Craft CMS 3.x
 *
 * Force users to only access a subset of your entries
 *
 * @link      https://trendyminds.com
 * @copyright Copyright (c) 2019 TrendyMinds
 */

namespace trendyminds\isolate\controllers;

use trendyminds\isolate\Isolate;

use Craft;
use craft\web\Controller;
use craft\elements\User;
use craft\helpers\UrlHelper;

/**
 * @author    TrendyMinds
 * @package   Isolate
 * @since     1.0.0
 */
class DefaultController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = [];

    // Public Methods
    // =========================================================================

    /**
     * @return mixed
     */
    public function actionIndex()
    {
        $route = "isolate/dashboard";

        if (Craft::$app->user->checkPermission('isolate:assign')) {
            $route = "isolate/users";
        }

        Craft::$app->controller->redirect(UrlHelper::cpUrl($route));
    }

    /**
     * @return mixed
     */
    public function actionDashboard()
    {
        return $this->renderTemplate('isolate/dashboard');
    }

    /**
     * @return mixed
     */
    public function actionSettings()
    {
        return $this->renderTemplate('isolate/settings', [
            "settings" => Isolate::$plugin->getSettings()
        ]);
    }
}
