<?php

namespace App\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case InternalReview = 'internal_review';
    case SentToClient = 'sent_to_client';
    case Approved = 'approved';
    case ChangesRequested = 'changes_requested';
    case Published = 'published';
}
