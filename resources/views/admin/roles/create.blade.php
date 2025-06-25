@extends('layouts.app')

@section('title', 'Roles and Permission Management')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6 mb-4">
                    <h1>Create Role</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="/roles"><button class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back</button>
                </div>
          </div>

            <div class="card">
                <form action="{{ route('roles.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">Role Name:</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Permissions:</label><br>
                            @foreach($permissions as $permission)
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="form-check-input">
                                    <label class="form-check-label">{{ $permission->name }}</label>
                                </div>
                            @endforeach
                        </div>

                        <button type="submit" class="btn btn-success">Create Role</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>
@endsection
