@extends('layouts.base')

@section('sidebar')
    @include('layouts.sidebar')
@endsection

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold dark:text-white">مدیریت کاربران</h2>
            <a href="{{ route('users.create') }}"
                class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                ایجاد کاربر جدید
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 p-3 bg-green-100 dark:bg-green-700 text-green-700 dark:text-green-100 rounded-md">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->has('general'))
            <div class="mb-4 p-3 bg-red-100 dark:bg-red-700 text-red-700 dark:text-red-100 rounded-md">
                {{ $errors->first('general') }}
            </div>
        @endif


        {{-- Search Form --}}
        <div class="mb-4">
            <form method="GET" action="{{ route('users.index') }}" class="flex flex-col sm:flex-row gap-2 items-center">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="جستجو بر اساس نام، تلفن، کد ملی..."
                    class="w-full sm:flex-grow rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white px-3 py-2 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <button type="submit"
                    class="w-full sm:w-auto px-4 py-2 bg-indigo-500 text-white rounded-md shadow-sm hover:bg-indigo-600">جستجو</button>
                @if (request('search'))
                    <a href="{{ route('users.index') }}"
                        class="w-full sm:w-auto text-center mt-2 sm:mt-0 sm:ml-2 text-red-600 hover:underline px-3 py-2 rounded-md border border-red-500 hover:bg-red-50 dark:hover:bg-red-900">
                        پاک کردن
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-xl shadow">
            <table class="w-full text-sm text-right text-gray-500 dark:text-gray-300">
                <thead class="text-xs uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400 dark:text-white">
                    <tr>
                        <th class="p-3">نام</th>
                        <th class="p-3">شماره تلفن</th>
                        <th class="p-3">کد ملی</th>
                        <th class="p-3">نقش</th>
                        <th class="p-3">وضعیت</th>
                        <th class="p-3">تاریخ عضویت</th>
                        <th class="p-3">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="p-3">{{ $user->name }}</td>
                            <td class="p-3">{{ $user->phone }}</td>
                            <td class="p-3">{{ $user->national_code }}</td>
                            <td class="p-3">
                                @if ($user->role === 'admin')
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-200 text-indigo-800 dark:bg-indigo-700 dark:text-indigo-100">مدیر</span>
                                @elseif ($user->role === 'staff')
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-200 text-blue-800 dark:bg-blue-700 dark:text-blue-100">کارمند</span>
                                @else
                                    {{ $user->role }}
                                @endif
                            </td>
                            <td class="p-3">
                                <span
                                    class="px-2 py-1 text-xs font-semibold rounded-full {{ $user->is_active ? 'bg-green-200 text-green-800 dark:bg-green-700 dark:text-green-100' : 'bg-red-200 text-red-800 dark:bg-red-700 dark:text-red-100' }}">
                                    {{ $user->is_active ? 'فعال' : 'غیرفعال' }}
                                </span>
                            </td>
                            <td class="p-3">
                                {{ $user->created_at ? \Morilog\Jalali\Jalalian::fromCarbon($user->created_at)->format('Y/m/d') : 'N/A' }}
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <a href="{{ route('users.edit', $user->id) }}"
                                    class="text-blue-600 hover:text-blue-800 dark:hover:text-blue-400 px-2 py-1">ویرایش</a>
                                @if (Auth::id() !== $user->id)
                                    {{-- Prevent self-deletion button --}}
                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                        class="inline-block"
                                        onsubmit="return confirm('آیا از حذف این کاربر مطمئن هستید؟ این عمل قابل بازگشت نیست.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="text-red-600 hover:text-red-800 dark:hover:text-red-400 px-2 py-1">حذف</button>
                                    </form>
                                @endif
                                {{-- Add restore button if using soft deletes --}}
                                {{-- @if ($user->trashed())
                                <form action="{{ route('users.restore', $user->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-800 dark:hover:text-green-400 px-2 py-1">بازیابی</button>
                                </form>
                                @endif --}}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-4 text-center text-gray-500 dark:text-gray-400">
                                کاربری یافت نشد.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            @if (isset($users) && $users instanceof \Illuminate\Pagination\LengthAwarePaginator && $users->count() > 0)
                {{ $users->withQueryString()->links() }}
            @endif
        </div>
    </div>
@endsection
