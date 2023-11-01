# laravel-common-starter


## process to clone the project

- clone the project from git url or cmd
- install composer
    ```bash
   composer install
   ```
- copy .env.example to .env
    ```bash 
    cp .env.example .env
   ```
- generate app key
   ```bash 
   php artisan key:generate
   ```

- configure your env file

- perform migration 
   ```bash
  php artisan migrate
  ```

- install passport 
    ```bash
    php artisan passport:install
    ```

## Now all set enjoy coding :)