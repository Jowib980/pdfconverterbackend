@extends('layouts.app')

@section('title', 'Payment Detail')

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
              <li class="breadcrumb-item active">Payment Detail
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
            
            <div class="card">
              <div class="card-header">
                <h2>Payment Detail</h2>
              </div>

              <div class="card-body">
                <ul>
                  <li>Payer Name:- {{ $payment->payer_name }}</li>
                  <li>Payer Email:- {{ $payment->payer_email }}</li>
                  <li>Payer Id:- {{ $payment->payer_id }}</li>
                  <li>Plan Type:- {{ $payment->plan_type }}</li>
                  <li>Plan Amount:- {{ $payment->plan_amount }}</li>
                  <li>Transaction Id:- {{ $payment->transaction_id }}</li>
                  <li>Transaction Status:- {{ $payment->transaction_status }}</li>
                  <li>Payment Gateway:- {{ $payment->gateway }}</li>
                  <li>Payment Date:- {{ $payment->payment_date }}</li>
                </ul>
              </div>
            </div>

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
