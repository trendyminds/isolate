/**
 * Isolate plugin for Craft CMS
 *
 * Index Field JS
 *
 * @author    TrendyMinds
 * @copyright Copyright (c) 2019 TrendyMinds
 * @link      https://trendyminds.com
 * @package   Isolate
 * @since     1.0.0
 */

class PermissionGroup {
  constructor($group) {
    this.$group = $group;
    this.groupName = this.$group.dataset.isolateGroup;
    this.$toggle = this.$group.querySelector("[data-toggle]");
    this.$label = this.$group.querySelector("[data-toggle-label]");
    this.$checkboxes = this.$group.querySelectorAll("input");
    this.enabled = (this.$group.dataset.enabled == "true");

    this.handleToggle = this.handleToggle.bind(this);

    this.init();
    this.events();
  }

  init() {
    if (!this.enabled) {
      this.disable();
    } else {
      this.enableCheckboxes();
    }
  }

  events() {
    this.$toggle.addEventListener("click", this.handleToggle);
  }

  handleToggle(ev) {
    ev.preventDefault();

    if (this.enabled) {
      const getConfirm = confirm(`Are you sure you want to all this user to edit all ${this.groupName} entries?`);

      if (getConfirm) {
        this.disable();
      }
    } else {
      this.enable();
    }
  }

  enable() {
    this.enabled = true;
    this.$checkboxes.forEach($checkbox => $checkbox.removeAttribute("checked"));
    this.enableCheckboxes();
  }

  disable() {
    this.enabled = false;
    this.$checkboxes.forEach($checkbox => $checkbox.setAttribute("checked", "checked"));
    this.disableCheckboxes();
  }

  enableCheckboxes() {
    this.$checkboxes.forEach($checkbox => $checkbox.removeAttribute("disabled"));
    this.$toggle.classList.remove("insecure");
    this.$toggle.classList.add("secure");
    this.$label.innerHTML = `Allow user to access all entries`;
  }

  disableCheckboxes() {
    this.$checkboxes.forEach($checkbox => $checkbox.setAttribute("disabled", "disabled"));
    this.$toggle.classList.remove("secure");
    this.$toggle.classList.add("insecure");
    this.$label.innerHTML = "Assign a subset of entries";
  }
}

document.querySelectorAll("[data-isolate-group]").forEach($el => new PermissionGroup($el));
