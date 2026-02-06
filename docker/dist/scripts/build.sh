#!/bin/bash

# Configuration
REGISTRY="ghcr.io/gmedia/erp"
TAG=${1:-"latest"}
PUSH=false
PUSH_FLAG="--load"

# Check for --push flag
for arg in "$@"; do
    if [ "$arg" == "--push" ]; then
        PUSH=true
        PUSH_FLAG="--push"
        break
    fi
done

BASE_IMAGE="${REGISTRY}/base:8.4"

# Check if Base Image exists in Registry
echo "Checking if Base Image exists in Registry: ${BASE_IMAGE}..."
if docker buildx imagetools inspect "${BASE_IMAGE}" > /dev/null 2>&1; then
    echo "Base Image already exists in Registry. Skipping build."
else
    echo "Base Image not found in Registry. Building Base Image..."
    docker buildx build ${PUSH_FLAG} \
        -t "${BASE_IMAGE}" \
        -f docker/dist/base/Dockerfile .
fi

# Build App Image
echo "Building App Image: ${REGISTRY}/app:${TAG}..."
docker buildx build ${PUSH_FLAG} \
    -t "${REGISTRY}/app:${TAG}" \
    --build-arg BASE_IMAGE="${BASE_IMAGE}" \
    -f docker/dist/http/Dockerfile .

echo "Build complete: ${REGISTRY}/app:${TAG}"
