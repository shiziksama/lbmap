This repository now contains a basic Laravel skeleton. Existing helper
classes have been moved under `app/Services` so they can be autoloaded
via PSR-4. Legacy PHP scripts remain in the project root for reference.

The Laravel router now serves the original HTML page at `/`. Tile
requests for overlays and rendered maps are handled by `MapController`:
`/lb_overlay/{z}/{x}/{y}.png` and `/lb_map/{z}/{x}/{y}.png`.

To get started you will need to install dependencies using Composer and
configure your environment variables based on `.env.example`.
