# AGENTS.md

Personal script collection for comparing implementations across languages and platforms.

## Structure

- `linux/` — Linux-targeted scripts
- `macos/` — macOS-targeted scripts
- `projects/` — Larger multi-file projects
- `curriculum-vitae/` — Bilingual (en/es) LinkedIn profile content

## Conventions

- Content is bilingual: English and Spanish (`*.es.md` suffix for Spanish files).
- No build system, package manager, test suite, or linter is configured.
- No `package.json`, `Makefile`, `pyproject.toml`, or equivalent exists yet.

## Scripts

- `linux/setup-nuevo-equipo.sh` — Provisiona un equipo nuevo: PPAs, apt packages, zsh+OhMyZsh+Powerlevel10k, snaps, dotfiles. Ejecutar con `sudo bash setup-nuevo-equipo.sh`. Requiere pasos manuales post-instalación (p10k configure, bins en ~/.local/bin).

## CI

- `.github/workflows/opencode.yml` triggers OpenCode on `/oc` or `/opencode` comments in issues/PRs. Model: `opencode/big-pickle`.
