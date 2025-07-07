@extends('layouts.app')

@section('title', 'Contact Records')

@section('content')
	 <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>All Contacts</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Contacts
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
                      <button type="submit" class="btn btn-danger" onclick="return confirm('Delete selected contact?')">Delete Selected</button>
                    </div>
                    <div class="col-md-6 text-right">
                      <p>Total Contacts: {{ $contacts->total() }}</p>.
                    </div>
                  </div>

                  <!-- ✅ CHECKBOXES NOW INSIDE FORM -->
                  <table class="table table-bordered table-hover">
                    <thead>
                      <tr>
                        <th><input type="checkbox" id="checkAll" /></th>  
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse($contacts as $contact)
                        <tr>
                          <td><input type="checkbox" name="ids[]" value="{{ $contact->id }}"></td>
                          <td>{{ $contact->name }}</td>
                          <td>{{ $contact->email }}</td>
                          <td>{{ $contact->subject }}</td>
                          <td>{{ $contact->message }}</td>
                          <td>
                            <form method="POST" action="{{ route('delete-contact', $contact->id)}}" style="display: inline-block;">
                              @csrf
                              @method('DELETE')
                              <button class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this contact record?')"><i class="fas fa-trash"></i>  </button>
                            </form>
                          </td>
                        </tr>
                      @empty
                        <tr><td colspan="5">No data found</td></tr>
                      @endforelse
                    </tbody>
                  </table>

                  <div class="mt-3 d-flex justify-content-center">
                    {{ $contacts->links() }}
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