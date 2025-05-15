<section>
    <header>
        <h2 class="text-lg font-medium">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm">
            {{ __("Update your account's information.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)"
                required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="phone" :value="__('Phone')" />
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->phone)"
                required autocomplete="tel" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <div>
            <x-input-label for="national_code" :value="__('National Code')" />
            <x-text-input id="national_code" name="national_code" type="text" class="mt-1 block w-full"
                :value="old('national_code', $user->national_code)" autocomplete="off" />
            <x-input-error class="mt-2" :messages="$errors->get('national_code')" />
        </div>

        <div>
            <x-input-label :value="__('Role')" />
            <div class="mt-1 block w-full">
                @php
                    $roleNames = [
                        'admin' => 'مدیریت',
                        'staff' => 'کارمند',
                    ];
                @endphp
                {{ $roleNames[$user->role] ?? ucfirst($user->role) }}
            </div>
        </div>


        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm">
                    {{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
