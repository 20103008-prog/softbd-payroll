@csrf

<div class="mb-3">
    <label class="form-label">Device name</label>
    <div class="input-group">
        <span class="input-group-text"><i class="bi bi-tag"></i></span>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $device->name ?? '') }}" placeholder="e.g. Main Gate K40-A" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="form-text">A friendly label to recognise this terminal.</div>
</div>

<div class="mb-3">
    <label class="form-label">Serial number (SN) <span class="text-muted">— required for ADMS / cloud push</span></label>
    <div class="input-group">
        <span class="input-group-text"><i class="bi bi-upc"></i></span>
        <input type="text" name="serial_number" class="form-control @error('serial_number') is-invalid @enderror"
               value="{{ old('serial_number', $device->serial_number ?? '') }}" placeholder="From device Menu → System Info">
        @error('serial_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="form-text">On K40-A: <strong>Menu → System Info</strong> (or Device Info). ADMS matches punches by this SN.</div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">IP Address</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-router"></i></span>
            <input type="text" name="ip_address" class="form-control @error('ip_address') is-invalid @enderror"
                   value="{{ old('ip_address', $device->ip_address ?? '') }}" placeholder="192.168.68.201" required>
            @error('ip_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-text">Office LAN IP (for reference / local pull only).</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Port</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-ethernet"></i></span>
            <input type="number" name="port" class="form-control @error('port') is-invalid @enderror"
                   value="{{ old('port', $device->port ?? 4370) }}" min="1" max="65535" required>
            @error('port')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Standard communication port for attendance terminal (Default: <code>8000</code> or <code>4370</code>).</div>
    </div>
</div>

<div class="mt-3">
    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1"
               @checked(old('is_active', ($device->is_active ?? true)))>
        <label class="form-check-label" for="is_active">Active (included in sync &amp; push)</label>
    </div>
    @error('is_active')<div class="text-danger small">{{ $message }}</div>@enderror
</div>
