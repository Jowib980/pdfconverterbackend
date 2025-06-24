@extends('layouts.app')

@section('title', 'Converted Files')

@section('content')
	 <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Converted Files</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Files</li>
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
            <div class="card p-2">
               <!-- Filter Form -->
              <form method="GET" action="{{ route('all-files') }}">
                <div class="row">
                  <div class="col-md-3">
                    <input type="text" name="name" class="form-control" placeholder="Search by User Name" value="{{ request('name') }}">
                  </div>
                  <div class="col-md-3">
                    <select name="file_type" class="form-control">
                      <option value="">All File Types</option>
                      <option value="word_files" {{ request('file_type') == 'word_files' ? 'selected' : '' }}>Word</option>
                      <option value="pdf_files" {{ request('file_type') == 'pdf_files' ? 'selected' : '' }}>PDF</option>
                      <option value="ppt_files" {{ request('file_type') == 'ppt_files' ? 'selected' : '' }}>PPT</option>
                      <option value="excel_files" {{ request('file_type') == 'excel_files' ? 'selected' : '' }}>Excel</option>
                      <option value="html_files" {{ request('file_type') == 'html_files' ? 'selected' : '' }}>HTML</option>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                  </div>
                </div>
              </form>

            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div class="card">
              
              <!-- /.card-header -->
              <div class="card-body">
                <form method="POST" action="{{ route('bulk-delete-files') }}">
                  @csrf
                  @method('DELETE')

                    <div class="col-md-4 mb-2">
                      <button type="submit" class="btn btn-danger" onclick="return confirm('Delete selected files?')">Delete Selected</button>
                    </div>
                  </div>

                  <table class="table table-bordered table-hover">
                    <thead>
                      <tr>
                        <th><input type="checkbox" id="checkAll" /></th>
                        <th>User Name</th>
                        <th>File Type</th>
                        <th>Conversion Type</th>
                        <th colspan="2">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse($files as $file)
                        <tr>
                          <td><input type="checkbox" name="ids[]" value="{{ $file->id }}" /></td>
                          <td>{{ $file->user->name }}</td>
                          <td>{{ $file->file_type }}</td>
                          <td>{{ $file->convert_into }}</td>
                          <td>
                            <form method="POST" action="{{ route('delete-file', $file->id) }}" style="display: inline-block;">
                              @csrf
                              @method('DELETE')
                              <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                            <a href="{{ url('api/download-file', $file->downloadToken?->token) }}" class="btn btn-primary btn-sm"><i class="fa fa-download"></i></a>
                          </td>
                        </tr>
                      @empty
                        <tr><td colspan="5">No data found</td></tr>
                      @endforelse
                    </tbody>
                  </table>

                  <div class="mt-3 d-flex justify-content-center">
                    {{ $files->links() }}
                  </div>
                </form>

              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
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
