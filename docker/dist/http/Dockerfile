ARG BASE_IMAGE=ghcr.io/gmedia/erp/base:8.4

# Stage 0: Composer dependencies
FROM ${BASE_IMAGE} AS composer
WORKDIR /app
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Stage 1: Build assets
FROM ${BASE_IMAGE} AS asset-builder
# Install Node.js and NPM
RUN apk add --no-cache nodejs npm
WORKDIR /app
COPY . .
# We need vendor for Wayfinder to run php artisan
COPY --from=composer /app/vendor /app/vendor
RUN npm ci
RUN npm run build

# Stage 2: Production image
FROM ${BASE_IMAGE}
ENV APPDIR=/var/www/html
WORKDIR ${APPDIR}

# Copy application code and vendor from composer stage
COPY . .
COPY --from=composer /app/vendor ${APPDIR}/vendor

# Copy built assets from asset-builder stage
COPY --from=asset-builder /app/public/build ${APPDIR}/public/build
COPY --from=asset-builder /app/node_modules ${APPDIR}/node_modules

# Setup scripts and permissions
COPY docker/dist/scripts/start-container.sh /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container \
    && chown -R www-data:www-data ${APPDIR}/storage ${APPDIR}/bootstrap/cache

# Expose port (Octane default)
EXPOSE 80

ENTRYPOINT ["start-container"]
CMD ["php", "artisan", "octane:swoole", "--host=0.0.0.0", "--port=80", "--workers=2", "--max-requests=500"]
