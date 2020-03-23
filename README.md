# saga-backend

## Installation

[Install Laravel 6 and its requirements](https://laravel.com/docs/6.x/installation#installation) to use this project. Follow the steps under "Server Requirements" and "Configuration."

Make a database running MySQL/MariaDB and grant a user all privileges on it.

Install Redis 3+ and configure it. When developing locally, start it in Terminal with `redis-server [/path/to/config.conf]`.

Additionally, set up the [Laravel Echo Server](https://github.com/tlaverdure/laravel-echo-server). When developing locally, don't forget to run the server in the background with the command `laravel-echo-server start`.

Configure a `.env` file according to `.env.example`. An API client will need to be generated first to fill out `JWT_ACCOUNT` and `JWT_PASSWORD`.

Run the following commands to set up the project:

```sh
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan passport:client --personal
```

Run these commands to set up npm:

```sh
npm i
npm run prod
```

On Linux, you may also need to [set up file permissions](https://stackoverflow.com/questions/30639174/how-to-set-up-file-permissions-for-laravel-5-and-others#37266353) so the web server can write logs and cache views.

On the production server, follow [this guide](https://laravel.com/docs/6.x/queues#supervisor-configuration) to set up the queue worker. If working on localhost, you can just run `php artisan queue:listen redis --tries=5` yourself in a terminal.

If deploying to a web server, change the document root of your server to the `/public` directory. Also, follow the steps [here](https://laravel.com/docs/6.x/installation#web-server-configuration) to set up URL rewriting.

If developing on localhost, simply run `php artisan serve --host=127.0.0.1 --port=8000` in Terminal to run the server. (You MUST use `127.0.0.1` in order for the proxy on frontend to work!)

## Automated Testing

Run the following command in this directory:

`./vendor/bin/phpunit --bootstrap vendor/autoload.php tests`
