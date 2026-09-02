<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Schema;
class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        Cache::rememberForever('translations', function () {
            $translations = collect();
            $language_option =["nl","fr","it","pt","es","en"];

            if(Cache::remember('schema_has_settings_table', 300, function () {
                return Schema::hasTable('settings');
            })) {
                if(\Session::get('setup_data') == ''){
                    $setup_data = sitesetupSession('get');
                    if ($setup_data && isset($setup_data->language_option)) {
                        $language_option = $setup_data->language_option;
                    }
                }
            }
            foreach ($language_option as $locale) { // suported locales
                // Check if language directory exists before processing
                if (is_dir(resource_path("lang/{$locale}"))) {
                    $translations[$locale] = [
                        'php' => $this->phpTranslations($locale),
                        'json' => $this->jsonTranslations($locale),
                    ];
                }
            }

            return $translations;
        });
    }

    private function phpTranslations($locale)
    {
        $path = resource_path("lang/$locale");

        return collect(File::allFiles($path))->flatMap(function ($file) use ($locale) {
            $key = ($translation = $file->getBasename('.php'));

            return [$key => trans($translation, [], $locale)];
        });
    }

    private function jsonTranslations($locale)
    {
        $path = resource_path("lang/$locale.json");

        if (is_string($path) && is_readable($path)) {
            return json_decode(file_get_contents($path), true);
        }

        return [];
    }
}
