<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\ValueObjects;

final readonly class SubmissionActions
{
    public const string KEY_CREATE_SUBMISSION = 'create_submission';

    public const string KEY_CREATE_LEAD_IF_HAS_EMAIL = 'create_lead_if_has_email';

    public const string KEY_NOTIFY_EMAILS = 'notify_emails';

    /**
     * @param  list<string>  $notifyEmails
     */
    public function __construct(
        public bool $createSubmission = true,
        public bool $createLeadIfHasEmail = true,
        public array $notifyEmails = [],
    ) {}

    /**
     * @param  array<string, mixed>|null  $data
     */
    public static function fromArray(?array $data): self
    {
        $data ??= [];

        return new self(
            createSubmission: (bool) ($data[self::KEY_CREATE_SUBMISSION] ?? true),
            createLeadIfHasEmail: (bool) ($data[self::KEY_CREATE_LEAD_IF_HAS_EMAIL] ?? true),
            notifyEmails: array_values(array_filter((array) ($data[self::KEY_NOTIFY_EMAILS] ?? []), is_string(...))),
        );
    }

    /**
     * @return array{create_submission: bool, create_lead_if_has_email: bool, notify_emails: list<string>}
     */
    public function toArray(): array
    {
        return [
            self::KEY_CREATE_SUBMISSION => $this->createSubmission,
            self::KEY_CREATE_LEAD_IF_HAS_EMAIL => $this->createLeadIfHasEmail,
            self::KEY_NOTIFY_EMAILS => $this->notifyEmails,
        ];
    }
}
