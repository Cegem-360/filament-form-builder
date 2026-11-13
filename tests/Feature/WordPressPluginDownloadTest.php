<?php

declare(strict_types=1);

it('serves the WordPress plugin as a zip', function (): void {
    $response = $this->get(route('form-builder.wordpress-plugin'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/zip')
        ->assertDownload('cegem360-marketinghub.zip');

    $path = tempnam(sys_get_temp_dir(), 'wp');
    file_put_contents($path, $response->streamedContent());

    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();

    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = $zip->getNameIndex($i);
    }
    $main = (string) $zip->getFromName('cegem360-marketinghub/cegem360-marketinghub.php');
    $zip->close();
    unlink($path);

    expect($names)->toContain(
        'cegem360-marketinghub/cegem360-marketinghub.php',
        'cegem360-marketinghub/includes/class-payload.php',
        'cegem360-marketinghub/includes/class-settings.php',
    )->and($main)->toContain('Plugin Name:       Cégem360 MarketingHub');
});
