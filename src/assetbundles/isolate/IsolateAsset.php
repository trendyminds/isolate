<?php
/**
 * Isolate plugin for Craft CMS 3.x
 *
 * Force users to only access a subset of your entries
 *
 * @link      https://trendyminds.com
 * @copyright Copyright (c) 2019 TrendyMinds
 */

namespace trendyminds\isolate\assetbundles\Isolate;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    TrendyMinds
 * @package   Isolate
 * @since     1.0.0
 */
class IsolateAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@trendyminds/isolate/assetbundles/isolate/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/Isolate.js',
        ];

        $this->css = [
            'css/Isolate.css',
        ];

        parent::init();
    }
}
