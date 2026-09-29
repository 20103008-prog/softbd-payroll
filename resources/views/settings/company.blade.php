@extends('layouts.app')

@section('title', 'Company Profile')

@section('content')
    <div class="page-toolbar">
        <div class="min-w-0">
            <h1 class="h4 mb-1"><i class="bi bi-building me-2"></i>Company Profile</h1>
            <div class="text-muted hide-xs-desc">This name, contact details, and logo appear on printed payslips.</div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-x-circle-fill"></i>
            <div><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('settings.company.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Company name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-building"></i></span>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $company->name) }}" placeholder="{{ config('app.name') }}">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                <textarea name="address" rows="2" class="form-control @error('address') is-invalid @enderror"
                                          placeholder="Street, city, country">{{ old('address', $company->address) }}</textarea>
                                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                           value="{{ old('phone', $company->phone) }}" placeholder="e.g. +880 1700 000000">
                                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email', $company->email) }}" placeholder="hr@company.com">
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Logo <span class="text-muted">(optional, max 1 MB)</span></label>
                            @if($company->logoUrl())
                                <div class="d-flex align-items-center gap-3 mb-2 p-2 border rounded-3">
                                    <img src="{{ $company->logoUrl() }}" alt="Current logo" style="max-height: 48px; max-width: 120px; object-fit: contain;">
                                    <div class="flex-grow-1 small text-muted">Current logo</div>
                                </div>
                            @endif
                            <input type="file" name="logo" accept="image/*" class="form-control @error('logo') is-invalid @enderror">
                            @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">PNG, JPG, or WEBP. Shown on payslips and the navbar.</div>
                            @if($company->logo_path)
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo">
                                    <label class="form-check-label" for="remove_logo">Remove current logo when saving</label>
                                </div>
                            @endif
                        </div>

                        <div class="mt-4">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Save profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-eye me-1"></i>Preview</div>
                <div class="card-body text-center">
                    @if($company->logoUrl())
                        <img src="{{ $company->logoUrl() }}" alt="Logo" class="img-fluid mb-3" style="max-height: 90px;">
                        <form method="POST" action="{{ route('settings.company.logo.destroy') }}" class="mb-3"
                              onsubmit="return confirm('Remove the company logo?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash me-1"></i>Remove logo
                            </button>
                        </form>
                    @else
                        <div class="text-muted mb-3"><i class="bi bi-image fs-1 d-block opacity-50"></i>No logo uploaded</div>
                    @endif
                    <div class="h5 mb-1">{{ $company->displayName() }}</div>
                    @if($company->address)<div class="small text-muted">{{ $company->address }}</div>@endif
                    <div class="small text-muted">
                        @if($company->phone)<span><i class="bi bi-telephone me-1"></i>{{ $company->phone }}</span>@endif
                        @if($company->email)<span class="ms-2"><i class="bi bi-envelope me-1"></i>{{ $company->email }}</span>@endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
