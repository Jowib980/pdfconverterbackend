@extends('layouts.app')

@section('title', 'Payment Gateway')

@section('content')
	 <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Gateways
              </li>
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

      <section class="content-header">
        <div class="row mb-2">
          <div class="col-md-6"></div>
          <div class="col-md-6 text-right">
            <a href="/create"><button class="btn btn-primary">Create</button></a>
          </div>
        </div>
      </section>


    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">

              <!-- ✅ Bulk delete form START -->
              <form id="bulkDeleteForm" method="POST" action="{{ route('bulk-delete-gateway') }}">
                @csrf
                @method('DELETE')

                <div class="card">
                  <div class="card-body">
                    <div class="row mb-2">
                      <div class="col-md-6">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Delete selected payment gateway?')">Delete Selected</button>
                      </div>
                      <div class="col-md-6 text-right">
                        <p>Total Files: {{ $gateways->total() }}</p>
                      </div>
                    </div>

                    <table class="table table-bordered table-hover">
                      <thead>
                        <tr>
                          <th><input type="checkbox" id="checkAll" /></th>
                          <th>Gateway Name</th>
                          <th>Status</th>
                          <th>Client Id</th>
                          <th>Client Secret</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach($gateways as $gateway)
                          <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $gateway->id }}"></td>
                            <td>{{ $gateway->name }}</td>
                            <td>
                              @if($gateway->is_enabled)
                                <span class="badge badge-success">Enabled</span>
                              @else
                                <span class="badge badge-danger">Disabled</span>
                              @endif
                            </td>
                            <td>{{ $gateway->client_id }}</td>
                            <td>{{ $gateway->client_secret }}</td>
                            <td>
                              <a href="{{ route('edit-gateway', $gateway->id )}}" class="btn btn-primary"><i class="fas fa-pen"></i></a>

                              <!-- ✅ Single delete form is standalone -->
                              <form method="POST" action="{{ route('delete-gateway', $gateway->id) }}" style="display: inline-block;">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this file?')"><i class="fas fa-trash"></i></button>
                              </form>
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>

                    <div class="mt-3 d-flex justify-content-center">
                      {{ $gateways->links() }}
                    </div>
                  </div>
                </div>
              </form>
              <!-- ✅ Bulk delete form END -->



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