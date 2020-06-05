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
    this.$isolateTables = document.querySelectorAll("[data-isolate-table]");

    this.moveIsolateNav();
    this.removeEntriesNav();

    if (this.$isolateTables.length) {
      this.getLargestCell();
    }
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

  getLargestCell() {
    let largestWidth = 0;

    for (let i = 0; i < this.$isolateTables.length; i++) {
      const $row = this.$isolateTables[i].querySelector("tbody tr td:first-of-type");
      
      if ($row.scrollWidth > largestWidth) {
        largestWidth = $row.scrollWidth;
      }
    }

    this.resizeRows(largestWidth);
  }

  resizeRows(cellWidth) {
    const $rows = document.querySelectorAll("#content-container table tbody tr td:first-of-type");

    for (let i = 0; i < $rows.length; i++) {
      $rows[i].style.width = `${cellWidth}px`;
    }
  }
}

new Isolate();
