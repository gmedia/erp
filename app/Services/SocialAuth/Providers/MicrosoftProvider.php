<?php

namespace App\Services\SocialAuth\Providers;

use App\Services\SocialAuth\Concerns\StatelessOAuthProvider;
use SocialiteProviders\Microsoft\Provider as BaseMicrosoftProvider;

class MicrosoftProvider extends BaseMicrosoftProvider
{
    use StatelessOAuthProvider;
}
