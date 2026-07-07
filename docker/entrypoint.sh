#!/bin/sh
set -eu

: "${DB_HOST:=127.0.0.1}"
: "${DB_PORT:=3306}"
: "${DB_NAME:=rockwood}"
: "${DB_USER:=rockwood}"
: "${DB_PASSWORD:=}"
: "${DB_CHARSET:=utf8mb4}"

php -r '
$dsn = sprintf(
	"mysql:host=%s;port=%s;dbname=%s;charset=%s",
	getenv("DB_HOST"),
	getenv("DB_PORT"),
	getenv("DB_NAME"),
	getenv("DB_CHARSET")
);

$config = "database:\n"
	. "\tdsn: " . var_export($dsn, true) . "\n"
	. "\tuser: " . var_export(getenv("DB_USER"), true) . "\n"
	. "\tpassword: " . var_export(getenv("DB_PASSWORD"), true) . "\n"
	. "\toptions:\n"
	. "\t\tPDO::ATTR_ERRMODE: PDO::ERRMODE_EXCEPTION\n"
	. "\t\tPDO::ATTR_DEFAULT_FETCH_MODE: PDO::FETCH_ASSOC\n"
	. "\t\tPDO::ATTR_PERSISTENT: true\n";

file_put_contents(getcwd() . "/config/local.neon", $config);
'

mkdir -p temp/sessions log www/uploads/issues
chown -R www-data:www-data temp log www/uploads

exec "$@"
