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
    psql -v ON_ERROR_STOP=1 --username "${POSTGRES_USER}" --dbname "${POSTGRES_DB}" \
        -c "ALTER SYSTEM SET ssl = 'on'"
    echo "laravel-pg: generated a self-signed TLS certificate; ssl on from first start"
fi
