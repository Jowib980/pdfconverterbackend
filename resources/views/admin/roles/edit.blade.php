@extends('layouts.app')

@section('title', 'Roles and Permission Management')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6 mb-4">
                    <h1>Edit Role</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="/roles"><button class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back</button>
                </div>
          </div>

            <div class="card">
                <form action="{{ route('roles.update', $role->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name">Role Name:</label>
                            <input type="text" name="name" class="form-control" value="{{ $role->name }}" required>
                        </div>

                        <div class="mb-3">
                            <label>Permissions:</label><br>
                            @foreach($permissions as $permission)
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                        class="form-check-input"
                                        {{ $role->permissions->contains('name', $permission->name) ? 'checked' : '' }}>
                                    <label class="form-check-label">{{ $permission->name }}</label>
                                </div>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn-primary">Update Role</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>
@endsection
