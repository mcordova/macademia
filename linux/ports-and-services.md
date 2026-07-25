# Port & Services Map

> Active services across all hosts — their ports, config locations, and access URLs.
> Update this file whenever you install, remove, or reconfigure a service.

---

## Hosts

<!-- Register every machine here. Use the Host ID in tables below. -->

| Host | Role | OS | Address | Notes |
|------|------|----|---------|-------|
| `local` | Workstation | Zorin OS 18.1 | localhost | Primary dev machine — THIS EQUIPO |
| `--` | *fill in* | — | — | e.g. VPS, NAS, Raspberry Pi, etc. |

---

## Legend

- **Host** — machine where the service runs (see [Hosts](#hosts) table). `--` = local.
- **Port** — actual listening port
- **Default** — whether the port matches the upstream default
- **Bind** — `0.0.0.0` = exposed on LAN, `127.0.0.1` = localhost only

---

## AI / LLM Stack

<!-- ia-stack project — env vars in /home/michael/ia-stack/.env -->

| Host | Service | Port | Default | Bind | Config | URL | Notes |
|------|---------|------|---------|------|--------|-----|-------|
| local | Ollama | 11434 | ✅ | 127.0.0.1 | `/etc/systemd/system/ollama.service` | http://localhost:11434 | Local LLM inference server |
| local | LiteLLM | 4000 | ✅ | 0.0.0.0 | `/home/michael/ia-stack/litellm_config.yaml` | http://localhost:4000 | LLM proxy — routes to Ollama, OpenAI, etc. |
| local | litellm-db (Docker) | 5433 | ❌ (5432→5433) | 0.0.0.0 | Docker volume `litellm-db-data` | http://localhost:5433 | PostgreSQL backing LiteLLM — host port remapped |
| local | Prisma Query Engine | 43543 | ❌ | 127.0.0.1 | `/home/michael/.cache/prisma-python/` | — | Internal, spawned by Python/Prisma |
| local | OpenCode Desktop | 40315 | ❌ (ephemeral) | 127.0.0.1 | `/opt/OpenCode/` | — | Electron app internal port, changes per session |

---

## Web Server

<!-- Apache virtual hosts, SSL certs, document roots, etc. -->

| Host | Service | Port | Default | Bind | Config | URL | Notes |
|------|---------|------|---------|------|--------|-----|-------|
| local | Apache2 | 80 | ✅ | * | `/etc/apache2/sites-enabled/000-default.conf` | http://localhost | Default vhost, DocumentRoot `/var/www/html` |
| local | Apache2 (SSL) | 443 | ✅ | — | `/etc/apache2/ports.conf` | https://localhost | Enabled in ports.conf but no vhost configured |

---

## CI/CD

<!-- Jenkins plugins, jobs, credentials, etc. -->

| Host | Service | Port | Default | Bind | Config | URL | Notes |
|------|---------|------|---------|------|--------|-----|-------|
| local | Jenkins | 8080 | ✅ | * | `/etc/default/jenkins` (`HTTP_PORT=8080`) | http://localhost:8080 | Managed by systemd |

---

## Databases

<!-- Connection strings, backup crons, extensions, etc. -->

| Host | Service | Port | Default | Bind | Config | URL | Notes |
|------|---------|------|---------|------|--------|-----|-------|
| local | PostgreSQL 16 | 5432 | ✅ | 127.0.0.1 | `/etc/postgresql/16/main/postgresql.conf` | postgresql://localhost:5432 | Host-only; Docker litellm-db uses separate instance on 5433 |

---

## System Services

<!-- CUPS printers, DNS, Bluetooth, etc. -->

| Host | Service | Port | Default | Bind | Config | URL | Notes |
|------|---------|------|---------|------|--------|-----|-------|
| local | CUPS | 631 | ✅ | 127.0.0.1 | `/etc/cups/cupsd.conf` | http://localhost:631 | Print scheduler |
| local | systemd-resolved | 53 | ✅ | 127.0.0.53 | `/etc/systemd/resolved.conf` | — | Local DNS stub resolver |

---

## Port Conflicts (historical)

<!-- Document any port clashes you've resolved and how -->

| Host | Conflict | Resolution |
|------|----------|------------|
| local | PostgreSQL 5432 ↔ Docker litellm-db 5432 | Mapped Docker container to host port **5433** (`-p 5433:5432`) |

---

## Quick Reference: Port Summary

| Host | Port | Service | Status |
|------|------|---------|--------|
| local | 53 | systemd-resolved | system |
| local | 80 | Apache2 | user |
| local | 443 | Apache2 (SSL) | user (no vhost) |
| local | 631 | CUPS | system |
| local | 4000 | LiteLLM | user |
| local | 5432 | PostgreSQL (host) | user |
| local | 5433 | litellm-db (Docker) | user (remapped) |
| local | 8080 | Jenkins | user |
| local | 11434 | Ollama | user |
| local | 1716 | GNOME Shell (gjs) | system |
| local | 38443 | (unknown — system) | system |
| local | 40315 | OpenCode Desktop | user (ephemeral) |
| local | 43543 | Prisma Query Engine | user (ephemeral) |

---

## Adding a New Service

When installing a new service, update this file:

1. If it's a **new machine**, add it to the **Hosts** table first
2. Add a row to the appropriate category table with the correct **Host** value
3. Fill in port, default status, bind address, and config path
4. If it conflicts with an existing port, document the resolution in **Port Conflicts**
5. Add it to the **Quick Reference** table at the bottom

---

*Last updated: 2026-07-25*
