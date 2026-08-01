@if (session('success'))
    <div class="cc-alert cc-alert-success" role="alert">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="cc-alert cc-alert-danger" role="alert">{{ session('error') }}</div>
@endif
@if (!empty($showDistributedFlash) && request('distributed'))
    <div class="cc-alert cc-alert-success" role="alert">Assets distributed successfully.</div>
@endif
@if ($errors->any())
    <div class="cc-alert cc-alert-danger" role="alert">
        <ul class="cc-alert-list mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
