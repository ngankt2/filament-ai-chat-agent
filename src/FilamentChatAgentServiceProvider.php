<?php

namespace Ngankt2\FilamentChatAgent;
use Livewire\Livewire;
use Ngankt2\FilamentChatAgent\Components\ChatAgentComponent;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentChatAgentServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('filament-ai-chat-agent')
            ->hasConfigFile('filament-ai-chat-agent') // <--- Đăng ký file config: config/filament-ai-chat-agent.php
            ->hasTranslations()
            ->hasViews();
    }

    /**
     * Bootstrap any application services.
     */
    public function packageBooted(): void
    {
        Livewire::component('fi-ai-chat-agent', ChatAgentComponent::class);

        // Đăng ký publish thủ công nếu dùng custom paths
        $this->publishes([
            __DIR__ . '/../config/filament-ai-chat-agent.php' => config_path('filament-ai-chat-agent.php'),
        ], 'filament-ai-chat-agent-config');

        $this->publishes([
            __DIR__ . '/../resources/lang' => resource_path('lang/vendor/filament-ai-chat-agent'),
        ], 'filament-ai-chat-agent-translations');
    }

}
