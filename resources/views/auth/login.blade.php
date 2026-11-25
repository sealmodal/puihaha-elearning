<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('verification_sent'))
        <div class="mb-4 p-3.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 text-xs sm:text-sm font-medium">
            {{ session('verification_sent') }}
        </div>
    @endif

    @if (session('status'))
        <div
            class="mb-4 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-medium">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required
                autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required
                autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            @if (Route::has('register'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 mr-3" href="{{ route('register') }}">
                    {{ __('Need an account? Register') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
    <div x-data="{ openResend: false }" class="mt-5 pt-4 border-t border-slate-200 text-xs sm:text-sm">
        <button type="button" @click="openResend = !openResend"
            class="text-blue-600 hover:underline font-medium cursor-pointer">
            Didn't receive or need a new email verification link?
        </button>

        <form x-show="openResend" x-cloak method="POST" action="{{ route('verification.resend.guest') }}"
            class="mt-3 flex flex-col sm:flex-row gap-2">
            @csrf
            <input type="email" name="email" value="{{ old('email') }}" placeholder="Enter your registered email"
                class="flex-1 rounded-lg border-slate-300 text-xs sm:text-sm px-3 py-2" required>
            <button type="submit"
                class="px-4 py-2 rounded-lg bg-[#0a192f] text-white text-xs sm:text-sm font-semibold hover:bg-[#162b4d] cursor-pointer">
                Resend Link
            </button>
        </form>
    </div>
</x-guest-layout>