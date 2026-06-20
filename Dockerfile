FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends freetds-dev libcurl4-openssl-dev \
    && docker-php-ext-install pdo_dblib curl \
    && rm -rf /var/lib/apt/lists/*

RUN { \
        echo "upload_max_filesize=120M"; \
        echo "post_max_size=125M"; \
        echo "max_file_uploads=10"; \
        echo "max_execution_time=180"; \
        echo "max_input_time=180"; \
        echo "memory_limit=256M"; \
    } > /usr/local/etc/php/conf.d/namthang-uploads.ini

WORKDIR /app
COPY . /app

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t ."]
