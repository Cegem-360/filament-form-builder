<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PublicFormController extends Controller
{
    public function show(string $slug): View|Factory
    {
        $form = $this->findActiveForm($slug);

        return view('filament-form-builder::public.registration-form', [
            'form' => $form,
        ]);
    }

    public function embed(string $slug): Response
    {
        $form = $this->findActiveForm($slug);

        $content = view('filament-form-builder::embed.registration-form', [
            'form' => $form,
        ])->render();

        return new Response($content, 200, [
            'X-Frame-Options' => 'ALLOWALL',
            'Content-Security-Policy' => 'frame-ancestors *',
        ]);
    }

    private function findActiveForm(string $slug): RegistrationForm
    {
        $form = RegistrationForm::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $form instanceof RegistrationForm) {
            throw new NotFoundHttpException(__('filament-form-builder::form.errors.not_found'));
        }

        return $form;
    }
}
