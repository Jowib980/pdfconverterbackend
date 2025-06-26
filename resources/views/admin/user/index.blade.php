@extends('layouts.app')

@section('title', 'User Management')

@section('content')
	 <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>All Users</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Users</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <section class="content-header">
      <div class="container-fluid">
        <div class=" mb-2">
          @if(session('message'))
            <div class="alert alert-success">
              {{ session('message') }}
            </div>
          @elseif(session('error'))
            <div class="alert alert-warning">
              {{ session('error') }}
            </div>
          @endif
        </div>
      </div>
    </section>

    <div class="container-fluid">
        <div class="mb-2 text-right">
          <div class="col-12">
            <a href="/add-user"><button class="btn btn-primary">Create new User</button></a>
          </div>
        </div>
      </div>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <!-- ✅ START form -->
            <form method="POST" action="{{ route('bulk-delete-users') }}">
              @csrf
              @method('DELETE')

              <div class="card">
                <div class="card-body">
                  <div class="row mb-2">
                    <div class="col-md-6">
                      <button type="submit" class="btn btn-danger" onclick="return confirm('Delete selected Users?')">Delete Selected</button>
                    </div>
                    <div class="col-md-6 text-right">
                      <p>Total Users: {{ $users->total() }}</p>
                    </div>
                  </div>

                  <!-- ✅ CHECKBOXES NOW INSIDE FORM -->
                  <table class="table table-bordered table-hover">
                    <thead>
                      <tr>
                        <th><input type="checkbox" id="checkAll" /></th>
                        <th>Name</th>
                        <th>Email</th>
                        <th colspan="2">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse($users as $user)
                        <tr>
                          <td><input type="checkbox" name="ids[]" value="{{ $user->id }}"></td>
                          <td>{{ $user->name }}</td>
                          <td>{{ $user->email }}</td>
                          <td>
                            <a href="{{ route('edit-user', $user->id )}}" class="btn btn-success"><i class="fas fa-pen"></i> </a>
                            <a href="{{ route('view-user', $user->id )}}" class="btn btn-primary"><i class="fas fa-eye"></i> </a>
                            <form method="POST" action="{{ route('delete-user', $user->id)}}" style="display: inline-block;">
                              @csrf
                              @method('DELETE')
                              <button class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?')"><i class="fas fa-trash"></i>  </button>
                            </form>
                          </td>
                        </tr>
                      @empty
                        <tr><td colspan="5">No data found</td></tr>
                      @endforelse
                    </tbody>
                  </table>

                  <div class="mt-3 d-flex justify-content-center">
                    {{ $users->links() }}
                  </div>
                </div>
              </div>
            </form>
           
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>
@endsection


<script>
  document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
      checkAll.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="ids[]"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
      });
    }
  });
</script>