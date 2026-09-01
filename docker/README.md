# Docker profiles

This fork does not use upstream Snipe-IT deployment instructions.

- Local development uses `docker-compose.yml` plus the optional
  `docker-compose.localhost.yml` override documented in the root `README.md`.
- The supported V1 production foundation is
  `docker-compose.production.yml`. Deployments may add the reviewed
  `docker-compose.production.dependencies.yml` overlay for repository-managed
  MariaDB/Redis and `docker-compose.production.edge.yml` for single-host TLS
  termination. These overlays are optional and must not be replaced with the
  rehearsal overlay.
- `docker-compose.production.registry.yml` is an optional loopback-only
  registry for verified offline artifact transfer when an organization registry
  is unavailable. It does not replace image scanning or digest verification.
- Production configuration, overlay selection, validation, backup, and
  rollback are documented in `docs/production-deployment.md`.
- Other inherited Dockerfiles and startup assets are compatibility/development
  material unless the fork documentation explicitly names them.
