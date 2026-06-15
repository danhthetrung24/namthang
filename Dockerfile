FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends freetds-dev libcurl4-openssl-dev \
    && docker-php-ext-install pdo_dblib curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . /app

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t ."]
