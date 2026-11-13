<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;
use Madbox99\FilamentFormBuilder\Filament\Resources\FormSubmissions\FormSubmissionResource;
use Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\RegistrationFormResource;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Throwable;

/**
 * Admin guide: how to connect an external form (WordPress, Zapier, Make, any
 * HTTP client) to a form through its inbound webhook URL.
 */
final class InboundWebhookHelp extends Page
{
    /**
     * Screenshots shipped with the package (resources/images/inbound/*.png).
     */
    public const array IMAGES = ['01-enable', '02-mapping', '03-submissions'];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownOnSquare;

    protected string $view = 'filament-form-builder::pages.inbound-webhook-help';

    public static function getNavigationLabel(): string
    {
        return __('filament-form-builder::form.inbound_help.navigation_label');
    }

    public function getTitle(): string|Htmlable
    {
        return __('filament-form-builder::form.inbound_help.title');
    }

    public function getWordPressPluginUrl(): string
    {
        return route('form-builder.wordpress-plugin');
    }

    public function getExamplePayloadJson(): string
    {
        return (string) json_encode(
            ['email' => 'anna@example.com', 'first_name' => 'Anna', 'utm_source' => 'wordpress'],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }

    public function getCurlExample(): string
    {
        $url = url((string) config('filament-form-builder.routes.inbound_path', 'forms/{slug}/inbound/{token}'));
        $payload = (string) json_encode(['email' => 'anna@example.com', 'first_name' => 'Anna'], JSON_UNESCAPED_SLASHES);

        return "curl -X POST '{$url}' \\\n  -H 'Content-Type: application/json' \\\n  -d '{$payload}'";
    }

    /**
     * @return array<int, string>
     */
    public function getResponseCodes(): array
    {
        $codes = [];

        foreach ([201, 404, 413, 422, 429] as $code) {
            $codes[$code] = __("filament-form-builder::form.inbound_help.codes.{$code}");
        }

        return $codes;
    }

    /**
     * @return list<array{key: string, name: string, url: string, steps: list<string>}>
     */
    public static function toolGuides(): array
    {
        $tools = [
            'cf7' => ['Contact Form 7', 'https://wordpress.org/plugins/cf7-to-zapier/'],
            'wpforms' => ['WPForms', 'https://wpforms.com/docs/how-to-install-and-use-the-webhooks-addon-with-wpforms/'],
            'gravity' => ['Gravity Forms', 'https://docs.gravityforms.com/category/add-ons-gravity-forms/webhooks-add-on/'],
            'elementor' => ['Elementor Pro', 'https://elementor.com/help/actions-after-submit/'],
            'zapier' => ['Zapier', 'https://help.zapier.com/hc/en-us/articles/8496326446989-Send-webhooks-in-Zaps'],
            'make' => ['Make', 'https://apps.make.com/http'],
        ];

        $guides = [];

        foreach ($tools as $key => [$name, $url]) {
            /** @var list<string> $steps */
            $steps = (array) __("filament-form-builder::form.inbound_help.tools.{$key}");

            $guides[] = ['key' => $key, 'name' => $name, 'url' => $url, 'steps' => array_values($steps)];
        }

        return $guides;
    }

    /**
     * Direct links for the setup steps, so nobody has to search the menu:
     * the forms list, the forms that already accept webhooks, and the
     * submissions list. Empty when the resources are not in the panel.
     *
     * @return array{step1: list<array{label: string, url: string}>, step2: list<array{label: string, url: string}>, step3: list<array{label: string, url: string}>, troubleshooting: list<array{label: string, url: string}>}
     */
    public function getSetupLinks(): array
    {
        $links = ['step1' => [], 'step2' => [], 'step3' => [], 'troubleshooting' => []];

        try {
            $formsUrl = RegistrationFormResource::getUrl('index');
        } catch (Throwable) {
            return $links;
        }

        $label = fn (string $key, array $replace = []): string => __("filament-form-builder::form.inbound_help.{$key}", $replace);

        $links['step1'][] = ['label' => $label('link_forms'), 'url' => $formsUrl];

        try {
            $links['step1'][] = ['label' => $label('link_new_form'), 'url' => RegistrationFormResource::getUrl('create')];
        } catch (Throwable) {
        }

        $inboundForms = RegistrationFormResource::getEloquentQuery()
            ->where('inbound_enabled', true)
            ->orderBy('name')
            ->limit(5)
            ->get();

        foreach ($inboundForms as $form) {
            /** @var RegistrationForm $form */
            $link = ['label' => $label('link_form_webhook', ['name' => $form->name]), 'url' => RegistrationFormResource::getUrl('edit', ['record' => $form])];
            $links['step2'][] = $link;
            $links['step3'][] = $link;
        }

        if ($inboundForms->isEmpty()) {
            $links['step2'][] = ['label' => $label('link_forms'), 'url' => $formsUrl];
            $links['step3'][] = ['label' => $label('link_forms'), 'url' => $formsUrl];
        }

        try {
            $links['troubleshooting'][] = ['label' => $label('link_submissions'), 'url' => FormSubmissionResource::getUrl('index')];
        } catch (Throwable) {
        }

        return $links;
    }

    /**
     * A shipped screenshot as a data URI, so the host app needs no asset
     * publishing step.
     */
    public static function image(string $name): string
    {
        if (! in_array($name, self::IMAGES, true)) {
            throw new InvalidArgumentException("Unknown help image [{$name}].");
        }

        $path = __DIR__."/../../../resources/images/inbound/{$name}.png";

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    }
}
