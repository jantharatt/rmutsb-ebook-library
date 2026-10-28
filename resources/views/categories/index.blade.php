@extends('layouts.app')

@section('title', 'หมวดหมู่หนังสือ')

@section('content_header')

    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>
            <h3 class="mb-0">
                📚 Dewey Decimal Classification
            </h3>

            <small class="text-muted">
                ระบบหมวดหมู่หนังสือห้องสมุด
            </small>
        </div>

    </div>
    <a href="{{ route('categories.create') }}" class="btn btn-primary">

        <i class="fas fa-plus"></i>

        เพิ่มหมวดหมู่

    </a>
@stop



@section('content')

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">

        <div>
            <h1 class="text-2xl font-bold text-slate-800">
                📚 Dewey Decimal Classification
            </h1>

            <p class="text-sm text-slate-500">
                ระบบหมวดหมู่หนังสือห้องสมุด
            </p>
        </div>

        <a href="{{ route('categories.create') }}"
            class="rounded-lg bg-ink-800 px-4 py-2 text-white hover:bg-ink-900 transition">

            <i class="fas fa-plus mr-1"></i>

            เพิ่มหมวดหมู่

        </a>

    </div>


    @forelse($categories as $category)

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-5">

            <div class="flex justify-between items-start mb-5">

                <div>

                    <h2 class="text-xl font-bold text-blue-700">

                        {{ $category->code }}
                        -
                        {{ $category->name }}

                    </h2>

                    <p class="text-sm text-slate-500 mt-1">

                        {{ $category->description }}

                    </p>

                </div>

                <span class="rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-700">

                    {{ $category->children->count() }}
                    หมวดย่อย

                </span>

            </div>


            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">

                @forelse($category->children as $child)
                    <div
                        class="rounded-lg border border-slate-200 bg-slate-50 p-4 hover:border-blue-400 hover:bg-blue-50 transition">

                        <div class="text-blue-700 font-bold">

                            {{ $child->code }}

                        </div>

                        <div class="font-semibold text-slate-700">

                            {{ $child->name }}

                        </div>

                        @if ($child->description)
                            <div class="text-sm text-slate-500 mt-1">

                                {{ $child->description }}

                            </div>
                        @endif

                        <div class="flex gap-2 mt-4">

                            <a href="{{ route('categories.edit', $child) }}"
                                        class="inline-flex items-center rounded-md border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-100">

                                        <i class="fas fa-pen mr-1"></i>
                                        แก้ไข

                                    </a>

                            <form action="{{ route('categories.destroy', $child) }}" method="POST">

                                @csrf
                                @method('DELETE')

                                <button onclick="return confirm('ลบหมวดหมู่นี้?')"
                                            class="inline-flex items-center rounded-md border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100">

                                            <i class="fas fa-trash-alt mr-1"></i>
                                            ลบ

                                </button>

                            </form>

                        </div>

                    </div>

                @empty

                    <div class="col-span-full text-center text-slate-400 py-8">

                        ยังไม่มีหมวดย่อย

                    </div>
                @endforelse

            </div>

        </div>

    @empty

        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-6 text-center text-yellow-700">

            ยังไม่มีข้อมูลหมวดหมู่

        </div>

    @endforelse

@stop
