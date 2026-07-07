Nette Web Project
=================

Welcome to the Nette Web Project! This is a basic skeleton application built using
[Nette](https://nette.org), ideal for kick-starting your new web projects.

Nette is a renowned PHP web development framework, celebrated for its user-friendliness,
robust security, and outstanding performance. It's among the safest choices
for PHP frameworks out there.

If Nette helps you, consider supporting it by [making a donation](https://nette.org/donate).
Thank you for your generosity!


Requirements
------------

This Web Project is compatible with Nette 3.2 and requires PHP 8.2.


Installation
------------

To install the Web Project, Composer is the recommended tool. If you're new to Composer,
follow [these instructions](https://doc.nette.org/composer). Then, run:

	composer create-project nette/web-project path/to/install
	cd path/to/install

Ensure the `temp/` and `log/` directories are writable.


Asset Building with Vite
------------------------

This project supports Vite for asset building, which is recommended but optional. To activate Vite:

1. Uncomment the `type: vite` line in the `common.neon` configuration file under the assets mapping section.
2. Then set up and build the assets:

		npm install
		npm run build


Web Server Setup
----------------

To quickly dive in, use PHP's built-in server:

	php -S localhost:8000 -t www

Then, open `http://localhost:8000` in your browser to view the welcome page.

For Apache or Nginx users, configure a virtual host pointing to your project's `www/` directory.

**Important Note:** Ensure `app/`, `config/`, `log/`, and `temp/` directories are not web-accessible.
Refer to [security warning](https://nette.org/security-warning) for more details.


Docker on Debian
----------------

The included Docker setup runs Apache/PHP in a container and connects to the MySQL server installed on the host.
It uses Linux host networking so MySQL can stay bound to `127.0.0.1`.

Build and start the app:

	docker compose up -d --build

Or use the helper scripts:

	./start.sh
	./stop.sh
	./restart.sh

The helper scripts use `docker compose` when the Compose v2 plugin is installed and fall back to `docker-compose` for older Debian setups.

Run database migrations:

	docker compose exec web vendor/bin/phinx migrate

The container Apache listens on `127.0.0.1:8080`, leaving host Apache free to keep ports 80 and 443.
You can open the app directly at `http://127.0.0.1:8080`, or reverse proxy from host Apache.

Example host Apache setup:

	sudo a2enmod proxy proxy_http headers
	sudo cp docker/apache-vhost.example.conf /etc/apache2/sites-available/rockwood.conf
	sudo a2ensite rockwood
	sudo systemctl reload apache2

Edit `ServerName` in `/etc/apache2/sites-available/rockwood.conf` before enabling it.
For HTTPS, put the proxy rules in your `:443` virtual host and change `X-Forwarded-Proto` to `https`.


Minimal Skeleton
----------------

For demonstrating issues or similar tasks, rather than starting a new project, use
[minimal skeleton](https://github.com/nette/web-project/tree/minimal).
