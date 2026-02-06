#!/bin/bash

# Configuration
REGISTRY="ghcr.io/gmedia/erp"
TAG="latest"

# Build Base Image
echo "Building Base Image..."
docker build -t "${REGISTRY}/base:8.4" -f docker/dist/base/Dockerfile .

# Build App Image
echo "Building App Image..."
docker build -t "${REGISTRY}/app:${TAG}" \
    --build-arg BASE_IMAGE="${REGISTRY}/base:8.4" \
    -f docker/dist/http/Dockerfile .

echo "Build complete: ${REGISTRY}/app:${TAG}"
