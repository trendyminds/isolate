/**
 * Isolate plugin for Craft CMS
 *
 * Isolate JS
 *
 * @author    TrendyMinds
 * @copyright Copyright (c) 2019 TrendyMinds
 * @link      https://trendyminds.com
 * @package   Isolate
 * @since     1.0.0
 */

class Isolate {
  constructor() {
    this.$mainNav = document.querySelector("#nav");

    this.moveIsolateNav();
    this.removeEntriesNav();
  }

  removeEntriesNav() {
    const $nav = this.$mainNav.querySelector("#nav-entries");

    $nav.parentNode.removeChild($nav);
  }

  moveIsolateNav() {
    const $isolateNav = this.$mainNav.querySelector("#nav-isolate");
    const isolateNavHTML = $isolateNav.outerHTML;
    const $dashboardNav = this.$mainNav.querySelector("#nav-dashboard");

    $isolateNav.parentNode.removeChild($isolateNav);
    $dashboardNav.insertAdjacentHTML("afterend", isolateNavHTML);
  }
}

new Isolate();
