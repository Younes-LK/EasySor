@extends('layouts.base')

@section('sidebar')
    @include('layouts.sidebar')
@endsection

@section('content')
    <div class="container mx-auto px-4 py-6">
        <h2 class="text-xl font-bold mb-4 dark:text-white">مدیریت تجهیزات</h2>

        @if (session('success'))
            <div class="mb-4 p-3 bg-green-100 dark:bg-green-700 text-green-700 dark:text-green-100 rounded-md">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 dark:bg-red-700 text-red-700 dark:text-red-100 rounded-md">
                <strong class="font-bold">خطا!</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Add/Edit Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4 dark:text-white border-b pb-2 dark:border-gray-700">
                {{ $editMode ? 'ویرایش تجهیزات' : 'افزودن تجهیزات جدید' }}
            </h3>
            <form method="POST"
                action="{{ $editMode ? route('equipments.update', $equipment->id) : route('equipments.store') }}">
                @csrf
                @if ($editMode)
                    @method('PUT')
                @endif

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">نام
                            تجهیزات</label>
                        <input type="text" id="name" name="name"
                            value="{{ old('name', $equipment->name ?? '') }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- Brand -->
                    <div>
                        <label for="brand"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-200">برند</label>
                        <input type="text" id="brand" name="brand"
                            value="{{ old('brand', $equipment->brand ?? '') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- Price -->
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-200">قیمت
                            (تومان)</label>
                        <input type="number" id="price" name="price"
                            value="{{ old('price', $equipment->price ?? '') }}" required min="0"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- Stock Quantity -->
                    <div>
                        <label for="stock_quantity" class="block text-sm font-medium text-gray-700 dark:text-gray-200">تعداد
                            در انبار</label>
                        <input type="number" id="stock_quantity" name="stock_quantity"
                            value="{{ old('stock_quantity', $equipment->stock_quantity ?? '') }}" required min="0"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- Description -->
                    <div class="md:col-span-2 lg:col-span-4">
                        <label for="description"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-200">توضیحات</label>
                        <textarea id="description" name="description" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $equipment->description ?? '') }}</textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    @if ($editMode)
                        <a href="{{ route('equipments.index') }}"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                            لغو
                        </a>
                    @endif
                    <button type="submit"
                        class="ms-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ $editMode ? 'بروزرسانی' : 'ذخیره' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Search Form -->
        <div class="mb-4">
            <form method="GET" action="{{ route('equipments.index') }}"
                class="flex flex-col sm:flex-row gap-2 items-center">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="جستجو بر اساس نام، برند، توضیحات..."
                    class="w-full sm:flex-grow rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white px-3 py-2 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <button type="submit"
                    class="w-full sm:w-auto px-4 py-2 bg-indigo-500 text-white rounded-md shadow-sm hover:bg-indigo-600">جستجو</button>
                @if (request('search'))
                    <a href="{{ route('equipments.index') }}"
                        class="w-full sm:w-auto text-center mt-2 sm:mt-0 sm:ml-2 text-red-600 hover:underline px-3 py-2 rounded-md border border-red-500 hover:bg-red-50 dark:hover:bg-red-900">
                        پاک کردن
                    </a>
                @endif
            </form>
        </div>


        <!-- Equipment List -->
        <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-xl shadow">
            <table class="w-full text-sm text-right text-gray-500 dark:text-gray-300">
                <thead class="text-xs uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400 dark:text-white">
                    <tr>
                        <th class="p-3">نام تجهیزات</th>
                        <th class="p-3">برند</th>
                        <th class="p-3">توضیحات</th>
                        <th class="p-3">قیمت</th>
                        <th class="p-3">موجودی انبار</th>
                        <th class="p-3">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($equipments as $item)
                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="p-3 font-semibold">{{ $item->name }}</td>
                            <td class="p-3">{{ $item->brand ?? '-' }}</td>
                            <td class="p-3 text-gray-600 dark:text-gray-400">
                                {{ \Illuminate\Support\Str::limit($item->description, 50, '...') }}</td>
                            <td class="p-3">{{ number_format($item->price) }} تومان</td>
                            <td class="p-3">{{ $item->stock_quantity }}</td>
                            <td class="p-3 whitespace-nowrap">
                                <a href="{{ route('equipments.edit', $item->id) }}"
                                    class="text-blue-600 hover:text-blue-800 dark:hover:text-blue-400 px-2">ویرایش</a>
                                <form action="{{ route('equipments.destroy', $item->id) }}" method="POST"
                                    class="inline-block" onsubmit="return confirm('آیا از حذف این تجهیزات مطمئن هستید؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="text-red-600 hover:text-red-800 dark:hover:text-red-400 px-2">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-4 text-center text-gray-500 dark:text-gray-400">
                                تجهیزاتی یافت نشد.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $equipments->withQueryString()->links() }}
        </div>
    </div>
@endsection
