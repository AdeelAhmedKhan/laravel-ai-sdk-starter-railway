#!/bin/bash
# First-init TLS, matching the stock Railway SSL Postgres image
# this template used before: generate a self-signed certificate
# into the data directory (PostgreSQL's default certificate
# locations) and switch ssl on through ALTER SYSTEM, which
# persists on the volume in postgresql.auto.conf.
#
# Runs once, at first start on an empty data directory, as an
# official-entrypoint init script. The certificate is
# self-signed, so clients use sslmode=require or prefer;
# sslmode=verify-full cannot validate it. That is the same
# trade the stock image makes. Plain connections keep working.
# Set PG_SSL=off in the service variables to skip this and run
# without TLS.
set -e

if [ "${PG_SSL:-on}" != "off" ] && [ ! -f "${PGDATA}/server.key" ]; then
    openssl req -new -x509 -days 3650 -nodes \
        -out "${PGDATA}/server.crt" -keyout "${PGDATA}/server.key" \
        -subj "/CN=postgres-railway" >/dev/null 2>&1
    # The key must not be readable by anyone but the owner.
    chmod 644 "${PGDATA}/server.crt"
    chmod 600 "${PGDATA}/server.key"
    # Connect over the local Unix socket, explicitly. The service
    # environment can carry PGHOST pointing at the private network
    # domain (a leftover from the stock image's variable set);
    # during first init the only server listening is the
    # entrypoint's temporary local one, so a TCP connection to the
    # private domain fails, and with set -e the ALTER SYSTEM below
    # would never run, leaving ssl off while everything else looks
    # healthy. Proven live 2026-11-21: SHOW ssl read "off" on a
    # deploy whose init logs showed exactly those psql connection
    # errors. Same trap the PostgreSQL 19 template hit.
    PGHOST=/var/run/postgresql psql -v ON_ERROR_STOP=1 --username "${POSTGRES_USER}" --dbname "${POSTGRES_DB}" \
        -c "ALTER SYSTEM SET ssl = 'on'"
    echo "laravel-pg: generated a self-signed TLS certificate; ssl on from first start"
fi
