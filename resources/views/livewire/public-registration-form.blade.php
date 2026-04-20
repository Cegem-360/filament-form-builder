<div class="w-full max-w-xl mx-auto">
    @if ($submitted)
        <div class="rounded-2xl bg-white p-8 shadow-sm border border-gray-200 text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ __('filament-form-builder::form.thank_you') }}</h2>
            <p class="text-gray-600">
                {{ $registrationForm->thank_you_message ?? __('filament-form-builder::form.submission_received') }}
            </p>
        </div>
    @else
        <div class="rounded-2xl bg-white p-8 shadow-sm border border-gray-200">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">{{ $registrationForm->name }}</h1>
                @if ($registrationForm->description)
                    <p class="mt-2 text-gray-600">{{ $registrationForm->description }}</p>
                @endif
            </div>

            @error('form')
                <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700 border border-red-200">
                    {{ $message }}
                </div>
            @enderror

            <form wire:submit="submit" class="space-y-5">
                @foreach ($registrationForm->fields ?? [] as $field)
                    @php
                        $fieldName = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::snake($field['name'] ?? ''));
                        $fieldType = $field['type'] ?? 'text';
                        $isRequired = !empty($field['required']);
                        $placeholder = $field['placeholder'] ?? '';
                    @endphp

                    <div>
                        @if ($fieldType !== 'checkbox')
                            <label for="field-{{ $fieldName }}" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __($field['name']) }}
                                @if ($isRequired)
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>
                        @endif

                        @switch($fieldType)
                            @case('textarea')
                                <textarea
                                    id="field-{{ $fieldName }}"
                                    wire:model="formData.{{ $fieldName }}"
                                    rows="4"
                                    placeholder="{{ $placeholder }}"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                    @if ($isRequired) required @endif
                                ></textarea>
                                @break

                            @case('select')
                                <select
                                    id="field-{{ $fieldName }}"
                                    wire:model="formData.{{ $fieldName }}"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                    @if ($isRequired) required @endif
                                >
                                    <option value="">{{ __('filament-form-builder::form.select_option') }}</option>
                                </select>
                                @break

                            @case('checkbox')
                                <label class="inline-flex items-center gap-2">
                                    <input
                                        id="field-{{ $fieldName }}"
                                        type="checkbox"
                                        wire:model="formData.{{ $fieldName }}"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                    />
                                    <span class="text-sm text-gray-700">
                                        {{ __($field['name']) }}
                                        @if ($isRequired)
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </span>
                                </label>
                                @break

                            @case('date')
                                <input
                                    id="field-{{ $fieldName }}"
                                    type="date"
                                    wire:model="formData.{{ $fieldName }}"
                                    placeholder="{{ $placeholder }}"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                    @if ($isRequired) required @endif
                                />
                                @break

                            @case('email')
                                <input
                                    id="field-{{ $fieldName }}"
                                    type="email"
                                    wire:model="formData.{{ $fieldName }}"
                                    placeholder="{{ $placeholder }}"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                    @if ($isRequired) required @endif
                                />
                                @break

                            @case('phone')
                                <input
                                    id="field-{{ $fieldName }}"
                                    type="tel"
                                    wire:model="formData.{{ $fieldName }}"
                                    placeholder="{{ $placeholder }}"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                    @if ($isRequired) required @endif
                                />
                                @break

                            @case('number')
                                <input
                                    id="field-{{ $fieldName }}"
                                    type="number"
                                    wire:model="formData.{{ $fieldName }}"
                                    placeholder="{{ $placeholder }}"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                    @if ($isRequired) required @endif
                                />
                                @break

                            @default
                                <input
                                    id="field-{{ $fieldName }}"
                                    type="text"
                                    wire:model="formData.{{ $fieldName }}"
                                    placeholder="{{ $placeholder }}"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                    @if ($isRequired) required @endif
                                />
                        @endswitch

                        @error("formData.{$fieldName}")
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach

                <div class="pt-2">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <span wire:loading.remove>{{ __('filament-form-builder::form.submit') }}</span>
                        <span wire:loading>{{ __('filament-form-builder::form.submitting') }}</span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
