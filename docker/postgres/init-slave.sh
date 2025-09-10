#!/bin/bash
set -e

# Wait for primary to be ready
until pg_isready -h $POSTGRES_MASTER_HOST -p $POSTGRES_MASTER_PORT -U postgres; do
  echo "Waiting for primary PostgreSQL to be ready..."
  sleep 2
done

# Remove existing data if any
rm -rf /var/lib/postgresql/data/*

# Create base backup from primary
PGPASSWORD=replicator_password pg_basebackup -h $POSTGRES_MASTER_HOST -D /var/lib/postgresql/data -U replicator -v -P -W

# Create standby.signal file
touch /var/lib/postgresql/data/standby.signal

# Configure recovery settings in postgresql.conf
echo "# Recovery settings" >> /var/lib/postgresql/data/postgresql.conf
echo "primary_conninfo = 'host=$POSTGRES_MASTER_HOST port=$POSTGRES_MASTER_PORT user=replicator password=replicator_password'" >> /var/lib/postgresql/data/postgresql.conf

# Set proper permissions
chown -R postgres:postgres /var/lib/postgresql/data
chmod 755 /var/lib/postgresql/data