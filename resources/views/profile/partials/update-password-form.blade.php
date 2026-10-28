<section>

    <header>

        <h2 class="text-lg font-semibold text-slate-800">
            เปลี่ยนรหัสผ่าน
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            เพื่อความปลอดภัยของบัญชีผู้ใช้งาน กรุณาตั้งรหัสผ่านที่คาดเดาได้ยาก
        </p>

    </header>

    <form method="POST"
          action="{{ route('password.update') }}"
          class="mt-6 space-y-5">

        @csrf
        @method('PUT')

        <div>

            <x-input-label
                for="update_password_current_password"
                value="รหัสผ่านปัจจุบัน" />

            <x-text-input
                id="update_password_current_password"
                name="current_password"
                type="password"
                class="mt-1 block w-full"
                autocomplete="current-password" />

            <x-input-error
                :messages="$errors->updatePassword->get('current_password')"
                class="mt-2" />

        </div>

        <div>

            <x-input-label
                for="update_password_password"
                value="รหัสผ่านใหม่" />

            <x-text-input
                id="update_password_password"
                name="password"
                type="password"
                class="mt-1 block w-full"
                autocomplete="new-password" />

            <x-input-error
                :messages="$errors->updatePassword->get('password')"
                class="mt-2" />

        </div>

        <div>

            <x-input-label
                for="update_password_password_confirmation"
                value="ยืนยันรหัสผ่านใหม่" />

            <x-text-input
                id="update_password_password_confirmation"
                name="password_confirmation"
                type="password"
                class="mt-1 block w-full"
                autocomplete="new-password" />

            <x-input-error
                :messages="$errors->updatePassword->get('password_confirmation')"
                class="mt-2" />

        </div>

        <div class="flex items-center gap-4">

            <x-primary-button>
                บันทึกรหัสผ่าน
            </x-primary-button>

            @if (session('status') === 'password-updated')

                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm font-medium text-green-600">

                    ✓ เปลี่ยนรหัสผ่านเรียบร้อยแล้ว

                </p>

            @endif

        </div>

    </form>

</section>