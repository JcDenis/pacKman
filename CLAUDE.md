# pacKman

Notes for AI assistants working on the Dotclear plugin pacKman.

**Read first** the shared conventions of the author's Dotclear modules:
https://raw.githubusercontent.com/JcDenis/gh2da/master/CONVENTIONS.md
(if a web tool refuses the address, use `curl -s` on it from the shell).
The rules below take precedence for this module.

## Module

Create and manage packages of themes and plugins from Dotclear administration pages.

Admin page and configuration page. Nothing on the public side.

Required Dotclear and PHP versions: see `_define.php`.

## Structure

* `src/Backend.php`: admin side: menu entry, favorites, behaviors registration.
* `src/Config.php`: configuration page of the module (plugins manager).
* `src/Core.php`: main class.
* `src/Install.php`: install and update, default settings.
* `src/Manage.php`: admin page of the module.
* `src/My.php`: module helpers (`My::id()`, settings, permissions).
* `src/Settings.php`: module settings.
* `src/Uninstall.php`: cleaners for the plugin Uninstaller.
* `src/Utils.php`: helpers.
* `src/Zip.php`: this class extends dotclear zip class.
* `js/`: `backend.js`.
* Behaviors used: `adminDashboardFavoritesV2`.

## Pitfalls

* None recorded yet: add here the traps met while working on the module.

## Testing

* Check the admin page, the configuration page, in English and French.
* CI browses the common admin and public pages; extra pages can be listed in `.github/dotclear-test.txt`.
