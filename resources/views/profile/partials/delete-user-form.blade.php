<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            ลบบัญชีผู้ใช้งาน
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            เมื่อลบบัญชีผู้ใช้งานแล้ว ข้อมูลและทรัพยากรทั้งหมดที่เกี่ยวข้องกับบัญชีนี้จะถูกลบอย่างถาวร 
            กรุณาดาวน์โหลดข้อมูลหรือไฟล์ที่ต้องการเก็บไว้ก่อนดำเนินการลบบัญชี
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >
        ลบบัญชีผู้ใช้งาน
    </x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>

        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                คุณแน่ใจหรือไม่ว่าต้องการลบบัญชีผู้ใช้งาน?
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                เมื่อลบบัญชีแล้ว ข้อมูลทั้งหมดของคุณจะถูกลบอย่างถาวร 
                กรุณากรอกรหัสผ่านเพื่อยืนยันการลบบัญชี
            </p>

            <div class="mt-6">

                <x-input-label 
                    for="password" 
                    value="รหัสผ่าน" 
                    class="sr-only" 
                />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="กรอกรหัสผ่าน"
                />

                <x-input-error 
                    :messages="$errors->userDeletion->get('password')" 
                    class="mt-2" 
                />

            </div>

            <div class="mt-6 flex justify-end">

                <x-secondary-button x-on:click="$dispatch('close')">
                    ยกเลิก
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    ลบบัญชี
                </x-danger-button>

            </div>

        </form>

    </x-modal>

</section>