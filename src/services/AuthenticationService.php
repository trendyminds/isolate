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

use Craft;
use craft\base\Component;
use yii\web\ForbiddenHttpException;

/**
 * @author    TrendyMinds
 * @package   Isolate
 * @since     1.0.0
 */
class AuthenticationService extends Component
{
}
