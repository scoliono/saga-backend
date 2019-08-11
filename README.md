# saga-backend

[Install Laravel 5.8 and its requirements](https://laravel.com/docs/5.8/installation#installation) to use this project. Follow the steps under "Server Requirements" and "Configuration."

Make a database running MySQL/MariaDB and grant a user all privileges on it.

Configure a `.env` file according to `.env.example`.

Run the following commands to set up the project:

```sh
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
```

On Linux, you may also need to [set up file permissions](https://stackoverflow.com/questions/30639174/how-to-set-up-file-permissions-for-laravel-5-and-others#37266353) so the web server can write logs and cache views.

If deploying to a web server, change the document root of your server to the `/public` directory. Also, follow the steps [here](https://laravel.com/docs/5.8/installation#web-server-configuration) to set up URL rewriting.

If developing on localhost, simply run `php artisan serve` in Terminal to run the server.
