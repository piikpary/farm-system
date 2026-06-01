<?php

use Livewire\Component;
use App\Models\AiSetting;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $provider = 'openai';
    public $api_key;
    public $model = 'gpt-4o-mini';
    public $is_enabled = false;

    public $has_saved_key = false;

    public function mount()
    {
        $setting = AiSetting::active();

        if ($setting) {
            $this->provider = $setting->provider;
            $this->model = $setting->model;
            $this->is_enabled = $setting->is_enabled;
            $this->has_saved_key = !empty($setting->api_key);
        }
    }

    public function save()
{
    if (!auth()->user()->hasPermission('ai_settings.update')) {
        abort(403, 'Permission denied.');
    }

    $this->validate([
        'provider' => 'required|in:openai,anthropic,gemini,groq',
        'api_key' => 'nullable|string|min:10',
        'model' => 'required|string|max:100',
        'is_enabled' => 'boolean',
    ]);

    $setting = AiSetting::where('status', 'active')->first();

    if (!$setting) {
        $setting = AiSetting::create([
            'provider' => $this->provider,
            'api_key' => $this->api_key,
            'model' => $this->model,
            'is_enabled' => $this->is_enabled,
            'status' => 'active',
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    } else {
        $data = [
            'provider' => $this->provider,
            'model' => $this->model,
            'is_enabled' => $this->is_enabled,
            'updated_by' => Auth::id(),
        ];

        if (!empty($this->api_key)) {
            $data['api_key'] = $this->api_key;
        }

        $setting->update($data);
    }

    $this->api_key = null;
    $this->has_saved_key = !empty($setting->fresh()->api_key);

    session()->flash('success', __('pages.ai_setting_saved_success'));
}

    public function removeKey()
    {
        if (!auth()->user()->hasPermission('ai_settings.update')) {
            abort(403, 'Permission denied.');
        }

        $setting = AiSetting::active();

        if ($setting) {
            $setting->update([
                'api_key' => null,
                'is_enabled' => false,
                'updated_by' => Auth::id(),
            ]);
        }

        $this->api_key = null;
        $this->is_enabled = false;
        $this->has_saved_key = false;

        session()->flash('success', __('pages.ai_key_removed_success'));
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.ai_settings') }}</h1>
            <p class="page-subtitle">{{ __('pages.ai_settings_subtitle') }}</p>
        </div>

        <div class="page-actions">
            <div class="language-switcher">
                <a href="{{ route('language.switch', 'en') }}"
                   class="lang-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">
                    EN
                </a>

                <a href="{{ route('language.switch', 'km') }}"
                   class="lang-btn {{ app()->getLocale() === 'km' ? 'active' : '' }}">
                    ខ្មែរ
                </a>
            </div>

            <a href="{{ route('dashboard') }}" class="btn gray">
                {{ __('pages.back') }}
            </a>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">{{ __('pages.ai_configuration') }}</h2>

        <div class="form-grid">
            <div>
                <label>{{ __('pages.ai_provider') }} *</label>
                <select wire:model.live="provider">
                    <option value="openai">OpenAI</option>
                    <option value="anthropic">Anthropic Claude</option>
                    <option value="gemini">Google Gemini</option>
                    <option value="groq">Groq</option>
                </select>
                @error('provider') <small>{{ $message }}</small> @enderror
            </div>

           <div>
    <label>{{ __('pages.ai_model') }} *</label>

    @if($provider === 'openai')
        <select wire:model.live="model">
            <option value="gpt-4o-mini">gpt-4o-mini</option>
            <option value="gpt-4.1-mini">gpt-4.1-mini</option>
            <option value="gpt-4.1">gpt-4.1</option>
        </select>

    @elseif($provider === 'anthropic')
        <select wire:model.live="model">
            <option value="claude-3-5-haiku-latest">claude-3-5-haiku-latest</option>
            <option value="claude-3-5-sonnet-latest">claude-3-5-sonnet-latest</option>
        </select>

    @elseif($provider === 'gemini')
        <select wire:model.live="model">
            <option value="gemini-2.5-flash">gemini-2.5-flash</option>
            <option value="gemini-2.5-flash-lite">gemini-2.5-flash-lite</option>
        </select>

    @elseif($provider === 'groq')
        <select wire:model.live="model">
            <option value="llama-3.1-8b-instant">llama-3.1-8b-instant</option>
            <option value="llama-3.3-70b-versatile">llama-3.3-70b-versatile</option>
        </select>
    @endif

    @error('model')
        <small>{{ $message }}</small>
    @enderror
</div>
            <div style="grid-column: 1 / -1;">
                <label>{{ __('pages.ai_api_key') }}</label>

                <input type="password"
                       wire:model.defer="api_key"
                       placeholder="{{ $has_saved_key ? __('pages.ai_key_already_saved') : __('pages.enter_ai_api_key') }}">

                @error('api_key') <small>{{ $message }}</small> @enderror

                @if($has_saved_key)
                    <div style="margin-top:8px;color:#166534;font-weight:800;">
                        {{ __('pages.ai_key_saved_message') }}
                    </div>
                @endif
            </div>

            <div style="grid-column: 1 / -1;">
                <label style="display:flex;align-items:center;gap:10px;font-weight:900;">
                    <input type="checkbox"
                           wire:model.live="is_enabled"
                           style="width:18px;height:18px;">
                    {{ __('pages.enable_ai_help') }}
                </label>
            </div>
        </div>

        <div class="btn-row">
            <button wire:click="save" class="btn">
                {{ __('pages.save_ai_settings') }}
            </button>

            @if($has_saved_key)
                <button wire:click="removeKey" class="btn danger">
                    {{ __('pages.remove_api_key') }}
                </button>
            @endif

            <a href="{{ route('dashboard') }}" class="btn gray">
                {{ __('pages.cancel') }}
            </a>
        </div>
    </div>
</div>