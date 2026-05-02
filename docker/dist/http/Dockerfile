ARG BASE_IMAGE=ghcr.io/gmedia/erp/base:8.4

# Stage 0: Composer dependencies
FROM ${BASE_IMAGE} AS composer
WORKDIR /app
COPY artisan composer.json composer.lock package.json package-lock.json tsconfig.json vite.config.ts components.json ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY lang ./lang
COPY public ./public
COPY resources ./resources
COPY routes ./routes
COPY storage ./storage
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Stage 1: Build assets
FROM ${BASE_IMAGE} AS asset-builder
# Install Node.js and NPM
RUN apk add --no-cache nodejs npm
WORKDIR /app
COPY --from=composer /app /app
RUN npm ci
RUN npm run build

# Stage 2: Production image
FROM ${BASE_IMAGE}
ENV APPDIR=/var/www/html
WORKDIR ${APPDIR}

# Copy application code and vendor from composer stage
COPY --from=composer /app ${APPDIR}

# Copy built assets from asset-builder stage
COPY --from=asset-builder /app/public/build ${APPDIR}/public/build

# Setup scripts and permissions
COPY docker/dist/scripts/start-container.sh /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container \
    && chown -R www-data:www-data ${APPDIR}/storage ${APPDIR}/bootstrap/cache

# Expose port (Octane default)
EXPOSE 80

ENTRYPOINT ["start-container"]
CMD ["php", "artisan", "octane:swoole", "--host=0.0.0.0", "--port=80", "--workers=2", "--max-requests=500"]
