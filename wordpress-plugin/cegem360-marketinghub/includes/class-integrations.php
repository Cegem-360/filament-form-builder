<?php

/**
 * Hooks into the supported form plugins and forwards their submissions.
 *
 * Form keys: "cf7:{id}", "wpforms:{id}", "gravity:{id}", "elementor:{form name}"
 * and "any" (every form).
 */
if (! defined('ABSPATH')) {
    exit;
}

final class Cegem360_MarketingHub_Integrations
{
    public static function register(): void
    {
        add_action('wpcf7_mail_sent', [self::class, 'contact_form_7']);
        add_action('wpcf7_mail_failed', [self::class, 'contact_form_7']);
        add_action('wpforms_process_complete', [self::class, 'wpforms'], 10, 3);
        add_action('elementor_pro/forms/new_record', [self::class, 'elementor'], 10, 1);
        add_action('gform_after_submission', [self::class, 'gravity_forms'], 10, 2);
    }

    /**
     * @param  object  $contact_form  WPCF7_ContactForm
     */
    public static function contact_form_7($contact_form): void
    {
        if (! class_exists('WPCF7_Submission') || ! method_exists($contact_form, 'id')) {
            return;
        }

        $submission = WPCF7_Submission::get_instance();
        if (! $submission) {
            return;
        }

        $id = (int) $contact_form->id();
        $title = method_exists($contact_form, 'title') ? (string) $contact_form->title() : (string) $id;
        $page_id = (int) $submission->get_meta('container_post_id');

        self::forward(
            'cf7:'.$id,
            'Contact Form 7: '.$title,
            Cegem360_MarketingHub_Payload::from_cf7((array) $submission->get_posted_data()),
            (string) $submission->get_meta('url'),
            $page_id > 0 ? (string) get_the_title($page_id) : ''
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, mixed>  $entry
     * @param  array<string, mixed>  $form_data
     */
    public static function wpforms($fields, $entry, $form_data): void
    {
        $id = (int) ($form_data['id'] ?? 0);
        $title = (string) ($form_data['settings']['form_title'] ?? $id);

        self::forward(
            'wpforms:'.$id,
            'WPForms: '.$title,
            Cegem360_MarketingHub_Payload::from_wpforms((array) $fields),
            (string) wp_get_referer(),
            ''
        );
    }

    /**
     * @param  object  $record  ElementorPro\Modules\Forms\Classes\Form_Record
     */
    public static function elementor($record): void
    {
        if (! method_exists($record, 'get') || ! method_exists($record, 'get_form_settings')) {
            return;
        }

        $name = (string) $record->get_form_settings('form_name');
        $meta = (array) $record->get('meta');

        self::forward(
            'elementor:'.$name,
            'Elementor: '.$name,
            Cegem360_MarketingHub_Payload::from_elementor((array) $record->get('fields')),
            (string) ($meta['page_url']['value'] ?? ''),
            (string) ($meta['page_title']['value'] ?? '')
        );
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<string, mixed>  $form
     */
    public static function gravity_forms($entry, $form): void
    {
        $id = (int) ($form['id'] ?? 0);

        self::forward(
            'gravity:'.$id,
            'Gravity Forms: '.(string) ($form['title'] ?? $id),
            Cegem360_MarketingHub_Payload::from_gravity((array) $entry, (array) $form),
            (string) ($entry['source_url'] ?? ''),
            ''
        );
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private static function forward(string $form_key, string $form_label, array $fields, string $page_url, string $page_title): void
    {
        $urls = Cegem360_MarketingHub_Settings::urls_for($form_key);

        if ($urls === [] || $fields === []) {
            return;
        }

        $payload = Cegem360_MarketingHub_Payload::with_meta($fields, $page_url, $page_title, $form_label);

        foreach ($urls as $url) {
            Cegem360_MarketingHub_Sender::send($url, $payload, $form_label);
        }
    }
}
