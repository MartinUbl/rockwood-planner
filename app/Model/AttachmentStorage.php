<?php declare(strict_types=1);

namespace App\Model;

use Nette\Http\FileUpload;
use Nette\Utils\FileSystem;
use Nette\Utils\Random;

final class AttachmentStorage
{
	public function __construct(
		private readonly string $uploadsDir,
		private readonly string $uploadsUrl,
	) {
	}

	public function store(FileUpload $upload): array
	{
		FileSystem::createDir($this->uploadsDir);

		$storedName = Random::generate(24) . '-' . $upload->getSanitizedName();
		$upload->move($this->uploadsDir . '/' . $storedName);

		return [
			'original_name' => $upload->getUntrustedName(),
			'stored_name' => $storedName,
			'mime_type' => $upload->getContentType(),
			'size' => $upload->getSize(),
		];
	}

	public function path(string $storedName): string
	{
		return $this->uploadsDir . '/' . $storedName;
	}

	public function url(string $storedName): string
	{
		return $this->uploadsUrl . '/' . rawurlencode($storedName);
	}

	public function delete(?string $storedName): void
	{
		if (!$storedName) {
			return;
		}

		$path = $this->path($storedName);
		if (is_file($path)) {
			@unlink($path);
		}
	}
}
