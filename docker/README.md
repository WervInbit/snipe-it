# Docker profiles

This fork does not use upstream Snipe-IT deployment instructions.

- Local development uses `docker-compose.yml` plus the optional
  `docker-compose.localhost.yml` override documented in the root `README.md`.
- The only supported V1 production container path is
  `docker-compose.production.yml`, documented in
  `docs/production-deployment.md`.
- Other inherited Dockerfiles and startup assets are compatibility/development
  material unless the fork documentation explicitly names them.
