#!/bin/bash
set -e

# Create replication user
psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE USER replicator WITH REPLICATION ENCRYPTED PASSWORD 'replicator_password';
    GRANT CONNECT ON DATABASE $POSTGRES_DB TO replicator;
EOSQL

# Configure postgresql.conf for replication
echo "# Replication settings" >> /var/lib/postgresql/data/postgresql.conf
echo "wal_level = replica" >> /var/lib/postgresql/data/postgresql.conf
echo "max_wal_senders = 10" >> /var/lib/postgresql/data/postgresql.conf
echo "max_replication_slots = 5" >> /var/lib/postgresql/data/postgresql.conf
echo "synchronous_commit = off" >> /var/lib/postgresql/data/postgresql.conf
echo "archive_mode = on" >> /var/lib/postgresql/data/postgresql.conf
echo "archive_command = '/bin/true'" >> /var/lib/postgresql/data/postgresql.conf

# Configure pg_hba.conf for replication
echo "# Replication connections" >> /var/lib/postgresql/data/pg_hba.conf
echo "host replication replicator 0.0.0.0/0 md5" >> /var/lib/postgresql/data/pg_hba.conf
echo "host all postgres_slave 0.0.0.0/0 md5" >> /var/lib/postgresql/data/pg_hba.conf


# Reload configuration
pg_ctl reload -D /var/lib/postgresql/data