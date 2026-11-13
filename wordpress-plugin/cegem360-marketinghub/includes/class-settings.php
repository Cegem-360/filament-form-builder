<?php
/**
 * Settings → MarketingHub: which WordPress form goes to which MarketingHub
 * webhook URL, a connection test and the log of the last sends.
 */
if (! defined('ABSPATH')) {
    exit;
}

final class Cegem360_MarketingHub_Settings
{
    public const OPTION_CONNECTIONS = 'cegem360_mh_connections';

    private const PAGE = 'cegem360-marketinghub';

    private const EMPTY_ROWS = 2;

    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_init', [self::class, 'settings']);
        add_action('admin_post_cegem360_mh_test', [self::class, 'handle_test']);
        add_filter('plugin_action_links_'.plugin_basename(dirname(__DIR__).'/cegem360-marketinghub.php'), [self::class, 'action_links']);
    }

    /**
     * @return list<string>
     */
    public static function urls_for(string $form_key): array
    {
        $urls = [];

        foreach (self::connections() as $connection) {
            if (($connection['form'] === $form_key || $connection['form'] === 'any') && Cegem360_MarketingHub_Payload::is_valid_webhook_url($connection['url'])) {
                $urls[] = $connection['url'];
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * @return list<array{form: string, url: string}>
     */
    public static function connections(): array
    {
        $rows = get_option(self::OPTION_CONNECTIONS, []);

        return is_array($rows) ? array_values($rows) : [];
    }

    public static function menu(): void
    {
        add_options_page('MarketingHub', 'MarketingHub', 'manage_options', self::PAGE, [self::class, 'render']);
    }

    public static function settings(): void
    {
        register_setting(self::PAGE, self::OPTION_CONNECTIONS, [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize'],
            'default' => [],
        ]);
    }

    /**
     * @param  array<string, string>  $links
     * @return array<string, string>
     */
    public static function action_links(array $links): array
    {
        array_unshift($links, '<a href="'.esc_url(admin_url('options-general.php?page='.self::PAGE)).'">Beállítások</a>');

        return $links;
    }

    /**
     * @param  mixed  $input
     * @return list<array{form: string, url: string}>
     */
    public static function sanitize($input): array
    {
        $rows = [];

        foreach ((array) $input as $row) {
            $row = (array) $row;
            $form = sanitize_text_field((string) ($row['form'] ?? ''));
            $elementor = sanitize_text_field((string) ($row['elementor'] ?? ''));
            $url = esc_url_raw(trim((string) ($row['url'] ?? '')), ['https']);

            if ($form === 'elementor:' && $elementor !== '') {
                $form = 'elementor:'.$elementor;
            }

            if ($form === '' || $form === 'elementor:' || $url === '') {
                continue;
            }

            if (! Cegem360_MarketingHub_Payload::is_valid_webhook_url($url)) {
                add_settings_error(self::PAGE, 'invalid-url', 'Érvénytelen webhook cím: '.esc_html($url).' – másold ki újra a MarketingHub űrlap „Külső űrlap (webhook)” szakaszából.');

                continue;
            }

            $rows[] = ['form' => $form, 'url' => $url];
        }

        return $rows;
    }

    /**
     * Forms of the installed form plugins, as form key → label.
     *
     * @return array<string, string>
     */
    public static function available_forms(): array
    {
        $forms = ['any' => 'Minden űrlap'];

        if (class_exists('WPCF7_ContactForm')) {
            foreach ((array) WPCF7_ContactForm::find(['posts_per_page' => -1]) as $form) {
                $forms['cf7:'.$form->id()] = 'Contact Form 7: '.$form->title();
            }
        }

        if (function_exists('wpforms') && isset(wpforms()->form)) {
            foreach ((array) wpforms()->form->get('', ['posts_per_page' => -1]) as $form) {
                $forms['wpforms:'.$form->ID] = 'WPForms: '.$form->post_title;
            }
        }

        if (class_exists('GFAPI')) {
            foreach ((array) GFAPI::get_forms() as $form) {
                $forms['gravity:'.$form['id']] = 'Gravity Forms: '.$form['title'];
            }
        }

        if (defined('ELEMENTOR_PRO_VERSION')) {
            $forms['elementor:'] = 'Elementor Pro űrlap (név alapján) →';
        }

        return $forms;
    }

    public static function handle_test(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die('Nincs jogosultságod.');
        }

        check_admin_referer('cegem360_mh_test');

        $url = esc_url_raw(trim((string) wp_unslash($_POST['url'] ?? '')), ['https']);
        $result = Cegem360_MarketingHub_Payload::is_valid_webhook_url($url)
            ? Cegem360_MarketingHub_Sender::test($url)
            : ['ok' => false, 'status' => 0, 'message' => 'Ez nem MarketingHub webhook cím.'];

        set_transient('cegem360_mh_test_result', $result, 60);

        wp_safe_redirect(admin_url('options-general.php?page='.self::PAGE));
        exit;
    }

    public static function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $forms = self::available_forms();
        $rows = self::connections();
        for ($i = 0; $i < self::EMPTY_ROWS; $i++) {
            $rows[] = ['form' => '', 'url' => ''];
        }

        $test = get_transient('cegem360_mh_test_result');
        delete_transient('cegem360_mh_test_result');
        ?>
        <div class="wrap">
            <h1>MarketingHub</h1>
            <p>Az itt beállított WordPress-űrlapok beküldései a MarketingHubba kerülnek, és ott érdeklődőként jelennek meg.
               A webhook címet a MarketingHubban az űrlap szerkesztőjében, a <strong>„Külső űrlap (webhook)”</strong> szakaszban találod.</p>

            <?php if (is_array($test)) { ?>
                <div class="notice notice-<?php echo $test['ok'] ? 'success' : 'error'; ?> is-dismissible"><p><?php echo esc_html($test['message']); ?></p></div>
            <?php } ?>
            <?php settings_errors(self::PAGE); ?>

            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>
                <table class="widefat striped" style="max-width: 1100px">
                    <thead><tr><th style="width: 35%">WordPress-űrlap</th><th>MarketingHub webhook cím</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $index => $row) {
                        $name = self::OPTION_CONNECTIONS.'['.$index.']';
                        $is_elementor = strpos($row['form'], 'elementor:') === 0;
                        $selected = $is_elementor ? 'elementor:' : $row['form'];
                        ?>
                        <tr>
                            <td>
                                <select name="<?php echo esc_attr($name); ?>[form]" style="width: 100%">
                                    <option value="">— Válassz űrlapot —</option>
                                    <?php foreach ($forms as $key => $label) { ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($selected, $key); ?>><?php echo esc_html($label); ?></option>
                                    <?php } ?>
                                    <?php if ($row['form'] !== '' && ! isset($forms[$selected])) { ?>
                                        <option value="<?php echo esc_attr($row['form']); ?>" selected><?php echo esc_html($row['form']); ?> (nem található)</option>
                                    <?php } ?>
                                </select>
                                <?php if (isset($forms['elementor:'])) { ?>
                                    <input type="text" name="<?php echo esc_attr($name); ?>[elementor]" value="<?php echo esc_attr($is_elementor ? substr($row['form'], 10) : ''); ?>"
                                           placeholder="Elementor űrlap neve" style="width: 100%; margin-top: 4px">
                                <?php } ?>
                            </td>
                            <td>
                                <input type="url" name="<?php echo esc_attr($name); ?>[url]" value="<?php echo esc_attr($row['url']); ?>"
                                       placeholder="https://…/forms/…/inbound/…" style="width: 100%; font-family: monospace">
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <p class="description">Üres sor nem mentődik. Mentés után újabb üres sorok jelennek meg.</p>
                <?php submit_button('Mentés'); ?>
            </form>

            <h2>Kapcsolat tesztelése</h2>
            <p>A teszt nem hoz létre érdeklődőt: csak azt ellenőrzi, hogy a cím helyes, és a MarketingHub fogadja.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="cegem360_mh_test">
                <?php wp_nonce_field('cegem360_mh_test'); ?>
                <input type="url" name="url" required placeholder="Webhook cím" style="width: 60%; font-family: monospace">
                <?php submit_button('Teszt', 'secondary', 'submit', false); ?>
            </form>

            <h2>Legutóbbi küldések</h2>
            <?php $entries = Cegem360_MarketingHub_Sender::entries(); ?>
            <?php if ($entries === []) { ?>
                <p>Még nem volt küldés.</p>
            <?php } else { ?>
                <table class="widefat striped" style="max-width: 1100px">
                    <thead><tr><th>Időpont</th><th>Űrlap</th><th>Eredmény</th></tr></thead>
                    <tbody>
                    <?php foreach ($entries as $entry) { ?>
                        <tr>
                            <td><?php echo esc_html($entry['time']); ?></td>
                            <td><?php echo esc_html($entry['form']); ?></td>
                            <td><?php echo $entry['ok'] ? '✅' : '❌'; ?> <?php echo esc_html(($entry['status'] ? $entry['status'].' – ' : '').$entry['message']); ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>
        <?php
    }
}
