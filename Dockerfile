FROM dunglas/frankenphp:1-php8.2-alpine

# Install required PHP extensions
RUN install-php-extensions mysqli pdo pdo_mysql mbstring exif zip gd

# Set working directory
WORKDIR /app

# Copy application files
COPY index.php routes.php ./
COPY public/ ./public/
COPY storage/ ./storage/
COPY web/ ./web/

# Copy Caddyfile
COPY Caddyfile /etc/caddy/Caddyfile

# Ensure storage directories exist and are writable
RUN mkdir -p /app/storage/evidence /app/storage/message_attachments \
    && chmod -R 775 /app/storage

EXPOSE 8080
