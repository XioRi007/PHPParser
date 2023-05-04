# PHP Parser

### Installation

1. Clone repository
2. Copy .env.example to .env and change db credentials
3. Run
   ```
   composer install
   ```
4. Run
   ```
   php index.php
   ```

### For Docker
1. Change DB_HOST to host.docker.internal in .env
2. Make sure that you have docker-network. If you have not, run
   ```
   docker network create my-network
   ```
3. Run
   ```
   docker build -t my-php-app .
   docker run --network=my-network --rm -it --name my-running-app my-php-app
   ```
