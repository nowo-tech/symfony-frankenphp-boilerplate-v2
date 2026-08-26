# Feature Specification: Platform form chrome (checkbox + password toggle)

**Feature Branch**: `086-platform-form-chrome`  
**Created**: 2026-08-07  
**Status**: Implemented (2026-08-07)  

**Input**: Align Nowo Shell host form paint with Beacon: platform native `.checkbox` (moss/sand/surface), FormKit `checkbox` field class, AuthKit / kit-admin / RoutingKit checks, and password-toggle layout (eye on the right with horizontal gap). No new product routes.

## Summary

Contributors and AuthKit guests need consistent checkbox and password-field chrome. Tailwind utility classes on native checkboxes do not replace `appearance` paint; password-toggle container Tailwind flex utilities fought host SCSS and could drop the eye below the input.

## Scope (as-built)

| Area | Delivered |
|------|-----------|
| SCSS | `.checkbox` / `.checkbox-label` + contrast rules; `.nowo-ui-check` / confirm-dialog selectors |
| Form themes | `app_theme` + `auth_kit_theme` checkbox / expanded choice blocks |
| FormKit | `field_types.checkbox.attr.class: checkbox` |
| Password toggle | `nowo_password_toggle.yaml` → `input-group-text`; flex + `gap: 0.5rem` in `_components.scss` |
| Kit admin | `.nowo-ui-check` appearance paint under `_kit_admin_styles.html.twig` |
| RoutingKit | Import `replace_all` checkbox uses `class="checkbox"` |

## Non-goals

- Controlling password-manager extension UI (NordPass / Bitwarden icons on focus)
- Changing AuthKit / FormKit / PasswordToggle package pins
- Product-domain forms outside platform shells

## Requirements

- **FR-001**: Native platform checkboxes MUST use `.checkbox` (or documented host selectors).
- **FR-002**: Password toggle MUST keep the eye on the right with a horizontal gap; host YAML MUST NOT add Tailwind flex/gap on the toggle container.
- **FR-003**: Document in `docs/CHANGELOG.md` / `docs/CONTRIBUTING.md` / `docs/UPGRADING.md`.

## Related

- Beacon `086-dry-refactor` (source chrome)
- `081-formkit-uikit-kit-sync`, `077-form-type-field-loop`
