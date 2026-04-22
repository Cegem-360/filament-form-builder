@php
    /** @var string $containerId */
    /** @var string $scopeSelector */
    /** @var string $customCss */
    /** @var list<\Madbox99\FilamentFormBuilder\Support\FormFieldBlueprint> $blueprints */
    /** @var string $thankYouMessage */
    use Madbox99\FilamentFormBuilder\Support\FormFieldBlueprint;
@endphp

<div id="{{ $containerId }}" class="ffb-scope">
    @if ($customCss !== '')
        <style>{!! $customCss !!}</style>
    @endif

    @if ($submitted)
        <div class="ffb-card ffb-card--success" role="status">
            <div class="ffb-icon-circle" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
            <h2 class="ffb-heading">{{ __('filament-form-builder::form.thank_you') }}</h2>
            <p class="ffb-text">
                {{ $thankYouMessage !== '' ? $thankYouMessage : __('filament-form-builder::form.submission_received') }}
            </p>
        </div>
    @else
        <div class="ffb-card">
            <div class="ffb-header">
                <h1 class="ffb-title">{{ $formName }}</h1>
                @if ($formDescription)
                    <p class="ffb-description">{{ $formDescription }}</p>
                @endif
            </div>

            @error('form')
                <div class="ffb-alert ffb-alert--error" role="alert">
                    {{ $message }}
                </div>
            @enderror

            <form wire:submit="submit" class="ffb-form" novalidate>
                {{-- Honeypot: bots fill every field; humans never see this one. --}}
                <div class="ffb-honeypot" aria-hidden="true">
                    <label for="{{ $containerId }}-website">Website</label>
                    <input
                        id="{{ $containerId }}-website"
                        type="text"
                        name="website"
                        tabindex="-1"
                        autocomplete="off"
                        wire:model="website"
                    />
                </div>

                @foreach ($blueprints as $blueprint)
                    @php
                        $inputId = $containerId . '-' . $blueprint->key;
                    @endphp

                    <div class="ffb-field ffb-field--{{ $blueprint->type }}">
                        @if ($blueprint->type !== FormFieldBlueprint::TYPE_CHECKBOX)
                            <label for="{{ $inputId }}" class="ffb-label">
                                {{ $blueprint->label }}
                                @if ($blueprint->required)
                                    <span class="ffb-required" aria-hidden="true">*</span>
                                @endif
                            </label>
                        @endif

                        @switch($blueprint->type)
                            @case(FormFieldBlueprint::TYPE_TEXTAREA)
                                <textarea
                                    id="{{ $inputId }}"
                                    class="ffb-input ffb-textarea"
                                    rows="4"
                                    wire:model.blur="formData.{{ $blueprint->key }}"
                                    placeholder="{{ $blueprint->placeholder }}"
                                    @if ($blueprint->maxLength) maxlength="{{ $blueprint->maxLength }}" @endif
                                    @if ($blueprint->required) required @endif
                                ></textarea>
                                @break

                            @case(FormFieldBlueprint::TYPE_SELECT)
                                <select
                                    id="{{ $inputId }}"
                                    class="ffb-input ffb-select"
                                    wire:model.blur="formData.{{ $blueprint->key }}"
                                    @if ($blueprint->required) required @endif
                                >
                                    <option value="">{{ __('filament-form-builder::form.select_option') }}</option>
                                    @foreach ($blueprint->options as $option)
                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                                @break

                            @case(FormFieldBlueprint::TYPE_CHECKBOX)
                                <label class="ffb-checkbox-row" for="{{ $inputId }}">
                                    <input
                                        id="{{ $inputId }}"
                                        type="checkbox"
                                        class="ffb-checkbox"
                                        wire:model.blur="formData.{{ $blueprint->key }}"
                                    />
                                    <span class="ffb-checkbox-label">
                                        {{ $blueprint->label }}
                                        @if ($blueprint->required)
                                            <span class="ffb-required" aria-hidden="true">*</span>
                                        @endif
                                    </span>
                                </label>
                                @break

                            @case(FormFieldBlueprint::TYPE_DATE)
                                <input
                                    id="{{ $inputId }}"
                                    type="date"
                                    class="ffb-input"
                                    wire:model.blur="formData.{{ $blueprint->key }}"
                                    @if ($blueprint->required) required @endif
                                />
                                @break

                            @case(FormFieldBlueprint::TYPE_EMAIL)
                                <input
                                    id="{{ $inputId }}"
                                    type="email"
                                    class="ffb-input"
                                    wire:model.blur="formData.{{ $blueprint->key }}"
                                    placeholder="{{ $blueprint->placeholder }}"
                                    autocomplete="email"
                                    inputmode="email"
                                    @if ($blueprint->required) required @endif
                                />
                                @break

                            @case(FormFieldBlueprint::TYPE_PHONE)
                                <input
                                    id="{{ $inputId }}"
                                    type="tel"
                                    class="ffb-input"
                                    wire:model.blur="formData.{{ $blueprint->key }}"
                                    placeholder="{{ $blueprint->placeholder }}"
                                    autocomplete="tel"
                                    inputmode="tel"
                                    @if ($blueprint->required) required @endif
                                />
                                @break

                            @case(FormFieldBlueprint::TYPE_NUMBER)
                                <input
                                    id="{{ $inputId }}"
                                    type="number"
                                    class="ffb-input"
                                    wire:model.blur="formData.{{ $blueprint->key }}"
                                    placeholder="{{ $blueprint->placeholder }}"
                                    @if ($blueprint->min !== null) min="{{ $blueprint->min }}" @endif
                                    @if ($blueprint->max !== null) max="{{ $blueprint->max }}" @endif
                                    @if ($blueprint->required) required @endif
                                />
                                @break

                            @default
                                <input
                                    id="{{ $inputId }}"
                                    type="text"
                                    class="ffb-input"
                                    wire:model.blur="formData.{{ $blueprint->key }}"
                                    placeholder="{{ $blueprint->placeholder }}"
                                    @if ($blueprint->maxLength) maxlength="{{ $blueprint->maxLength }}" @endif
                                    @if ($blueprint->required) required @endif
                                />
                        @endswitch

                        @error("formData.{$blueprint->key}")
                            <p class="ffb-error">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach

                <button
                    type="submit"
                    class="ffb-submit"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="submit">{{ __('filament-form-builder::form.submit') }}</span>
                    <span wire:loading wire:target="submit">{{ __('filament-form-builder::form.submitting') }}</span>
                </button>
            </form>
        </div>
    @endif
</div>
