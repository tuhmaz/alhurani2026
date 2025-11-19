<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\JetstreamServiceProvider::class,
    App\Providers\MenuServiceProvider::class,
    // Register custom OneSignal notification channel provider
    App\Providers\OneSignalServiceProvider::class,
    // Database Safety Provider - حماية قاعدة البيانات من العمليات الخطيرة
    App\Providers\DatabaseSafetyServiceProvider::class,
];
