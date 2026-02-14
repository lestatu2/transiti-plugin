# Transiti Plugin

Base plugin for custom Transiti features.

## Structure

- `transiti.php`: plugin bootstrap.
- `src/Core/Plugin.php`: main lifecycle hooks.
- `src/Admin/Admin.php`: admin-related entrypoint.
- `src/Autoloader.php`: lightweight PSR-4 style autoloader.
- `assets/`: CSS/JS placeholders.
- `languages/`: translation files.
- `uninstall.php`: uninstall cleanup entrypoint.

## Conventions

- Namespace: `Fabermind\\Transiti`
- Text domain: `transiti`
- Author: Fabermind srl (`http://fabermind.it`)
