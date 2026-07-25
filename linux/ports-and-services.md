# Port & Services Map

> Active services, their ports, config locations, and access URLs.
> Update this file whenever you install, remove, or reconfigure a service.

---

## Legend

- **Port** — actual listening port
- **Default** — whether the port matches the upstream default
- **Bind** — `0.0.0.0` = exposed on LAN, `127.0.0.1` = localhost only

---

## AI / LLM Stack

<!-- ia-stack project — env vars in /home/michael/ia-stack/.env -->

| Service | Port | Default | Bind | Config | URL | Notes |
|---------|------|---------|------|--------|-----|-------|
| Ollama | 11434 | ✅ | 127.0.0.1 | `/etc/systemd/system/ollama.service` | http://localhost:11434 | Local LLM inference server |
| LiteLLM | 4000 | ✅ | 0.0.0.0 | `/home/michael/ia-stack/litellm_config.yaml` | http://localhost:4000 | LLM proxy — routes to Ollama, OpenAI, etc. |
| litellm-db (Docker) | 5433 | ❌ (5432→5433) | 0.0.0.0 | Docker volume `litellm-db-data` | http://localhost:5433 | PostgreSQL backing LiteLLM — host port remapped to avoid conflict |
| Prisma Query Engine | 43543 | ❌ | 127.0.0.1 | `/home/michael/.cache/prisma-python/` | — | Internal, spawned by Python/Prisma |
| OpenCode Desktop | 40315 | ❌ (ephemeral) | 127.0.0.1 | `/opt/OpenCode/` | — | Electron app internal port, changes per session |

---

## Web Server

<!-- Apache virtual hosts, SSL certs, document roots, etc. -->

| Service | Port | Default | Bind | Config | URL | Notes |
|---------|------|---------|------|--------|-----|-------|
| Apache2 | 80 | ✅ | * | `/etc/apache2/sites-enabled/000-default.conf` | http://localhost | Default vhost, DocumentRoot `/var/www/html` |
| Apache2 (SSL) | 443 | ✅ | — | `/etc/apache2/ports.conf` | https://localhost | Enabled in ports.conf but no vhost configured |

---

## CI/CD

<!-- Jenkins plugins, jobs, credentials, etc. -->

| Service | Port | Default | Bind | Config | URL | Notes |
|---------|------|---------|------|--------|-----|-------|
| Jenkins | 8080 | ✅ | * | `/etc/default/jenkins` (`HTTP_PORT=8080`) | http://localhost:8080 | Managed by systemd |

---

## Databases

<!-- Connection strings, backup crons, extensions, etc. -->

| Service | Port | Default | Bind | Config | URL | Notes |
|---------|------|---------|------|--------|-----|-------|
| PostgreSQL 16 | 5432 | ✅ | 127.0.0.1 | `/etc/postgresql/16/main/postgresql.conf` | postgresql://localhost:5432 | Host-only; Docker litellm-db uses separate instance on 5433 |

---

## System Services

<!-- CUPS printers, DNS, Bluetooth, etc. -->

| Service | Port | Default | Bind | Config | URL | Notes |
|---------|------|---------|------|--------|-----|-------|
| CUPS | 631 | ✅ | 127.0.0.1 | `/etc/cups/cupsd.conf` | http://localhost:631 | Print scheduler |
| systemd-resolved | 53 | ✅ | 127.0.0.53 | `/etc/systemd/resolved.conf` | — | Local DNS stub resolver |

---

## Port Conflicts (historical)

<!-- Document any port clashes you've resolved and how -->

| Conflict | Resolution |
|----------|------------|
| PostgreSQL (host) 5432 ↔ Docker litellm-db 5432 | Mapped Docker container to host port **5433** (`-p 5433:5432`) |

---

## Quick Reference: Port Summary

| Port | Service | Status |
|------|---------|--------|
| 53 | systemd-resolved | system |
| 80 | Apache2 | user |
| 443 | Apache2 (SSL) | user (no vhost) |
| 631 | CUPS | system |
| 4000 | LiteLLM | user |
| 5432 | PostgreSQL (host) | user |
| 5433 | litellm-db (Docker) | user (remapped) |
| 8080 | Jenkins | user |
| 11434 | Ollama | user |
| 1716 | GNOME Shell (gjs) | system |
| 38443 | (unknown — system) | system |
| 40315 | OpenCode Desktop | user (ephemeral) |
| 43543 | Prisma Query Engine | user (ephemeral) |

---

## Adding a New Service

When installing a new service, update this file:

1. Add a row to the appropriate category table
2. Fill in port, default status, bind address, and config path
3. If it conflicts with an existing port, document the resolution in **Port Conflicts**
4. Add it to the **Quick Reference** table at the bottom

---

*Last updated: 2026-07-25*
