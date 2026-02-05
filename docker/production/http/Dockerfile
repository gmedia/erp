# Stage 1: Build assets
FROM node:22-alpine AS asset-builder
WORKDIR /app
COPY . .
RUN npm install -g bun \
    && bun install \
    && bun run build:ssr

# Stage 2: Production image
# Note: You should build the base image first and push it to ghcr.io, then use it here.
# For now, we reference it as a generic name that can be overridden via build args or CI.
ARG BASE_IMAGE=ghcr.io/gmedia/erp/base:8.4
FROM ${BASE_IMAGE}

WORKDIR /var/www/html

# Copy application code
COPY . .

# Copy built assets from builder stage
COPY --from=asset-builder /app/public/build ./public/build
COPY --from=asset-builder /app/bootstrap/ssr ./bootstrap/ssr

# Install production dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Setup scripts and permissions
COPY docker/production/scripts/start-container.sh /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port (Octane default)
EXPOSE 80

ENTRYPOINT ["start-container"]
CMD ["php", "artisan", "octane:swoole", "--host=0.0.0.0", "--port=80", "--workers=2", "--max-requests=500"]
