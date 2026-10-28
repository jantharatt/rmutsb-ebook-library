<section>

    <header>

        <h2 class="text-lg font-semibold text-slate-800">
            ข้อมูลส่วนตัว
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            แก้ไขข้อมูลส่วนตัวและอีเมลของบัญชีผู้ใช้งาน
        </p>

    </header>

    <form id="send-verification"
          method="POST"
          action="{{ route('verification.send') }}">

        @csrf

    </form>

    <form method="POST"
          action="{{ route('profile.update') }}"
          class="mt-6 space-y-5">

        @csrf
        @method('PATCH')

        <div>

            <x-input-label
                for="name"
                value="ชื่อ - นามสกุล" />

            <x-text-input
                id="name"
                name="name"
                type="text"
                class="mt-1 block w-full"
                :value="old('name', $user->name)"
                required
                autofocus
                autocomplete="name" />

            <x-input-error
                class="mt-2"
                :messages="$errors->get('name')" />

        </div>

        <div>

            <x-input-label
                for="email"
                value="อีเมล" />

            <x-text-input
                id="email"
                name="email"
                type="email"
                class="mt-1 block w-full"
                :value="old('email', $user->email)"
                required
                autocomplete="username" />

            <x-input-error
                class="mt-2"
                :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())

                <div class="mt-3 rounded-lg border border-yellow-200 bg-yellow-50 p-4">

                    <p class="text-sm text-yellow-800">

                        อีเมลของคุณยังไม่ได้รับการยืนยัน

                    </p>

                    <button
                        form="send-verification"
                        class="mt-2 text-sm font-medium text-blue-600 hover:underline">

                        ส่งอีเมลยืนยันอีกครั้ง

                    </button>

                    @if (session('status') === 'verification-link-sent')

                        <p class="mt-2 text-sm font-medium text-green-600">

                            ส่งลิงก์ยืนยันอีเมลเรียบร้อยแล้ว

                        </p>

                    @endif

                </div>

            @endif

        </div>

        <div class="flex items-center gap-4">

            <x-primary-button>

                บันทึกข้อมูล

            </x-primary-button>

            @if (session('status') === 'profile-updated')

                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm font-medium text-green-600">

                    ✓ บันทึกข้อมูลเรียบร้อยแล้ว

                </p>

            @endif

        </div>

    </form>

</section>