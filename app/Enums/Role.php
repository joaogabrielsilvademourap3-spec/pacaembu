<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Designer = 'designer';
    case SocialMediaManager = 'social_media_manager';
    case Copywriter = 'copywriter';
    case Developer = 'developer';
    case Client = 'client';
}
