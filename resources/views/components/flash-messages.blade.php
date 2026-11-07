{{--
    Shared page-level feedback banner.
    - Success messages are announced politely (role="status").
    - Errors are announced immediately (role="alert").
    - Only general (non-field) validation errors are listed here, e.g. back()->withErrors('...').
      Field errors keep rendering next to their inputs via @error, so nothing is shown twice.
--}}
@php
    $generalErrors = collect($errors->getMessages())
        ->filter(fn ($messages, $key) => is_int($key))
        ->flatten()
        ->all();
@endphp

@if (session('success'))
    <div role="status" class="mb-4 bg-[#E5F5EC] border border-[#1E8E5A] text-[#1E8E5A] px-4 py-3 rounded-lg text-[13px] font-semibold print:hidden">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div role="alert" class="mb-4 bg-[#F7E9E8] border border-[#B5504B] text-[#B5504B] px-4 py-3 rounded-lg text-[13px] font-semibold print:hidden">
        {{ session('error') }}
    </div>
@endif

@if (!empty($generalErrors))
    <div role="alert" class="mb-4 bg-[#F7E9E8] border border-[#B5504B] text-[#B5504B] px-4 py-3 rounded-lg text-[13px] font-semibold print:hidden">
        @if (count($generalErrors) === 1)
            {{ $generalErrors[0] }}
        @else
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($generalErrors as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
