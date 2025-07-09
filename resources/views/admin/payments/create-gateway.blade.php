@extends('layouts.app')

@section('title', 'Create Payment Gateway')

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
              <li class="breadcrumb-item active">Create Payment Gateway</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

     <section class="content-header">
      <div class="container-fluid">
        <div class=" mb-2">
          @if(session('success'))
            <div class="alert alert-success">
              {{ session('success') }}
            </div>
          @elseif(session('error'))
            <div class="alert alert-warning">
              {{ session('error') }}
            </div>
          @endif
        </div>
      </div>
    </section>

    @if ($errors->any())
  <div class="alert alert-danger">
    <ul>
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif


    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <!-- left column -->
          <div class="col-md-12">
            <!-- jquery validation -->
            <div class="card card-primary">
              
              <!-- /.card-header -->
              <!-- form start -->
              <form id="quickForm" action="{{ route('create-gateway') }}" method="POST">
              	@csrf
                <div class="card-body">
                	<div class="form-group">
	                   <label for="exampleInputPassword1">Name</label>
	                   <input type="name" name="name" class="form-control" id="exampleInputPassword1" placeholder="Name" required>
                  </div>

                  <div class="form-group">
                    <label>Status</label><br>
                    <div class="form-check form-check-inline">
                      <input 
                        class="form-check-input" 
                        type="radio" 
                        name="is_enabled" 
                        id="enabled" 
                        value="1" 
                        checked
                      >
                      <label class="form-check-label" for="enabled">Enable</label>
                    </div>
                    <div class="form-check form-check-inline">
                      <input 
                        class="form-check-input" 
                        type="radio" 
                        name="is_enabled" 
                        id="disabled" 
                        value="0" 
                      >
                      <label class="form-check-label" for="disabled">Disable</label>
                    </div>
                  </div>


                  	<div class="form-group">
	                    <label for="exampleInputPassword1">Client Id</label>
	                    <input type="text" name="client_id" class="form-control" id="exampleInputPassword1" placeholder="Client Id" >
	                </div>

                  <div class="form-group">
                      <label for="exampleInputPassword1">Client Secret</label>
                      <input type="text" name="client_secret" class="form-control" id="exampleInputPassword1" placeholder="Client Secret" >
                  </div>
                  
                </div>
                <!-- /.card-body -->
                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Create</button>
                </div>
              </form>
            </div>
            <!-- /.card -->
            </div>
          <!--/.col (left) -->
          <!-- right column -->
          <div class="col-md-6">

          </div>
          <!--/.col (right) -->
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>

 @endsection
