# Release checklist

- Increase `FYND_VERSION` and plugin header `Version:` before building.
- Run `tools/build-release.ps1 -version "1.0.1"` to build `release/fyndo-1.0.1.zip`.
- Upload `fyndo-1.0.1.zip` to your HTTPS server and update `release/manifest.json` `download_url` and `version`.
- Publish `manifest.json` at the URL defined in `FYND_UPDATE_MANIFEST` (e.g., `https://example.com/fyndo/manifest.json`).
- Optionally clear transient on servers running WordPress to see update immediately:

```bash
wp transient delete fyndo_update_manifest
wp transient delete update_plugins
```
