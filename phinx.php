<?php declare(strict_types=1);

return [
	'paths' => [
		'migrations' => __DIR__ . '/db/migrations',
	],
	'environments' => [
		'default_environment' => 'development',
		'development' => [
			'adapter' => 'mysql',
			'host' => getenv('DB_HOST') ?: 'localhost',
			'name' => getenv('DB_NAME') ?: 'rockwood',
			'user' => getenv('DB_USER') ?: 'root',
			'pass' => getenv('DB_PASSWORD') ?: '',
			'port' => (int) (getenv('DB_PORT') ?: 3306),
			'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
		],
	],
	'version_order' => 'creation',
];
