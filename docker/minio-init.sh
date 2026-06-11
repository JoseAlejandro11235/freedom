#!/bin/sh
set -e

MINIO_HOST="${MINIO_HOST:-minio}"
MINIO_PORT="${MINIO_PORT:-9000}"
MINIO_USER="${MINIO_ROOT_USER:-minioadmin}"
MINIO_PASSWORD="${MINIO_ROOT_PASSWORD:-minioadmin}"
BUCKET="${AWS_BUCKET:-freedom}"

echo "Configuring MinIO at ${MINIO_HOST}:${MINIO_PORT}..."

until mc alias set local "http://${MINIO_HOST}:${MINIO_PORT}" "${MINIO_USER}" "${MINIO_PASSWORD}" 2>/dev/null; do
    echo "Waiting for MinIO..."
    sleep 2
done

mc mb "local/${BUCKET}" --ignore-existing
mc anonymous set download "local/${BUCKET}"

echo "Bucket '${BUCKET}' is ready (public read)."
