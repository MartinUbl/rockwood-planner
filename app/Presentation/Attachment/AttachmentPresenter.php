<?php declare(strict_types=1);

namespace App\Presentation\Attachment;

use App\Model\AttachmentStorage;
use App\Model\IssueRepository;
use App\Model\UserRepository;
use App\Presentation\BasePresenter;
use Nette\Application\BadRequestException;
use Nette\Application\Responses\FileResponse;

final class AttachmentPresenter extends BasePresenter
{
	public function __construct(
		UserRepository $users,
		private readonly IssueRepository $issues,
		private readonly AttachmentStorage $attachments,
	) {
		parent::__construct($users);
	}

	public function actionDownload(int $id): void
	{
		$attachment = $this->issues->attachmentById($id);
		if (!$attachment) {
			throw new BadRequestException('Attachment not found.');
		}

		$path = $this->attachments->path((string) $attachment->offsetGet('stored_name'));
		if (!is_file($path)) {
			throw new BadRequestException('Attachment file is missing.');
		}

		$this->sendResponse(new FileResponse(
			$path,
			(string) $attachment->offsetGet('original_name'),
			$attachment->offsetGet('mime_type') ? (string) $attachment->offsetGet('mime_type') : null,
		));
	}
}
