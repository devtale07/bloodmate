#!/bin/bash

# BloodMate Database Backup Script
# This script creates automated backups of the BloodMate database

# Configuration
BACKUP_DIR="/var/backups/bloodmate"
LOG_FILE="/var/log/bloodmate-backup.log"
RETENTION_DAYS=30
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="bloodmate_backup_${TIMESTAMP}.sql"

# Load environment variables
if [ -f /var/www/html/.env ]; then
    export $(cat /var/www/html/.env | grep -v '^#' | xargs)
fi

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Log function
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

log "Starting database backup"

# Perform backup
if command -v docker &> /dev/null; then
    # Docker environment
    log "Using Docker for backup"
    docker exec bloodmate-mysql mysqldump -u"${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}" > "${BACKUP_DIR}/${BACKUP_FILE}" 2>> "$LOG_FILE"
else
    # Direct MySQL access
    log "Using direct MySQL access"
    mysqldump -h"${DB_HOST}" -u"${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}" > "${BACKUP_DIR}/${BACKUP_FILE}" 2>> "$LOG_FILE"
fi

# Check if backup was successful
if [ $? -eq 0 ]; then
    # Compress the backup
    gzip "${BACKUP_DIR}/${BACKUP_FILE}"
    
    # Get file size
    FILE_SIZE=$(du -h "${BACKUP_DIR}/${BACKUP_FILE}.gz" | cut -f1)
    
    log "Backup completed successfully: ${BACKUP_FILE}.gz (${FILE_SIZE})"
    
    # Remove old backups
    log "Removing backups older than ${RETENTION_DAYS} days"
    find "$BACKUP_DIR" -name "bloodmate_backup_*.sql.gz" -mtime +${RETENTION_DAYS} -delete
    
    # Count remaining backups
    BACKUP_COUNT=$(find "$BACKUP_DIR" -name "bloodmate_backup_*.sql.gz" | wc -l)
    log "Total backups retained: ${BACKUP_COUNT}"
    
    exit 0
else
    log "ERROR: Backup failed!"
    exit 1
fi
