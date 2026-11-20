# syntax=docker/dockerfile:1
# PostgreSQL 18 + pgvector for the Laravel AI SDK starter.
#
# Why this image exists: the Laravel AI SDK generates embeddings
# natively, and Laravel 13's vector API (vector columns,
# Schema::ensureVectorExtensionExists(), whereVectorSimilarTo())
# works only on PostgreSQL with the pgvector extension. Railway's
# stock postgres-ssl:18 image does not ship pgvector, so this
# template builds its own database image instead: the official
# PostgreSQL 18 image plus pgvector from PGDG, the PostgreSQL
# project's own apt repository (already configured inside the
# official image, scoped to PostgreSQL 18). Versions are pinned
# exactly; a patch release is a deliberate bump of those pins,
# never a silent drift.
#
# Existing deployments are protected by construction:
# - Same major version (18) as the stock image it replaces, so
#   existing data directories stay readable. A major version
#   change would not be done this way; it needs dump + restore.
# - PGDATA is pinned to /var/lib/postgresql/data, the layout the
#   stock Railway image uses and where existing volumes already
#   hold their data. (The official image's own default moved to
#   a versioned subdirectory in 18; without this pin its
#   entrypoint refuses the existing layout.)
# - The service keeps the stock variable contract
#   (POSTGRES_USER / POSTGRES_PASSWORD / POSTGRES_DB /
#   DATABASE_URL), so the app service needs no change.

FROM postgres:18.7@sha256:eb63372c1e05c755f55fa30cbd07aecc443676fc6c0fa90b9002d01537841e1a

# pgvector from PGDG, pinned exactly. The official image already
# trusts the PGDG repository; no key or source setup is needed.
# openssl generates the self-signed TLS certificate at first
# init (see initdb.d/20-ssl-certs.sh).
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        postgresql-18-pgvector=0.8.7-1.pgdg13+2 \
        openssl \
    && rm -rf /var/lib/apt/lists/*

# Fail the BUILD, not the deploy, if the stack is not exactly
# what this template promises.
RUN postgres --version | grep -q 'PostgreSQL) 18.7' \
    && grep -q "default_version = '0.8.7'" /usr/share/postgresql/18/extension/vector.control \
    && openssl version > /dev/null

# Pin the data directory to the layout existing deployments
# already use (see header). The template's volume mounts here.
ENV PGDATA=/var/lib/postgresql/data

# The official entrypoint runs everything in
# /docker-entrypoint-initdb.d once, at first start on an empty
# data directory. 10-pgvector.sql creates the vector extension
# in the application database (POSTGRES_DB); 20-ssl-certs.sh
# generates a self-signed TLS certificate into the data
# directory and switches ssl on, matching the behaviour of the
# stock Railway SSL image this replaces. Neither runs again on
# an existing data directory, so an upgraded deployment keeps
# its data and settings untouched.
COPY docker/postgres/initdb.d/10-pgvector.sql /docker-entrypoint-initdb.d/10-pgvector.sql
COPY docker/postgres/initdb.d/20-ssl-certs.sh /docker-entrypoint-initdb.d/20-ssl-certs.sh
RUN chmod +x /docker-entrypoint-initdb.d/20-ssl-certs.sh
