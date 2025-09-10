#!/bin/bash
set -e

# Run the slave initialization script
/docker-entrypoint-initdb.d/init-slave.sh

# Start PostgreSQL using the original entrypoint
exec docker-entrypoint.sh postgres