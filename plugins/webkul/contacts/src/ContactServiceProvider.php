<?php

namespace Webkul\Contact;

use Webkul\Support\Package;
use Webkul\Support\Console\Commands\InstallCommand;
use Webkul\Support\PackageServiceProvider;

class ContactServiceProvider extends PackageServiceProvider
{
    public static string $name = 'contacts';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasTranslations()
            ->hasInstallCommand(function (InstallCommand $command) {});
    }

    public function packageBooted(): void
    {
        //
    }
}
