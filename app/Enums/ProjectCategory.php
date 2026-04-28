<?php

namespace App\Enums;

enum ProjectCategory: string
{
    case SocialMedia = 'social_media';
    case Website = 'website';
    case LandingPage = 'landing_page';
    case Branding = 'branding';
    case PaidTraffic = 'paid_traffic';
    case Maintenance = 'maintenance';
    case Consulting = 'consulting';
}
