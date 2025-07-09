<aside class="main-sidebar sidebar-dark-primary elevation-4" style="position: fixed;">
    <!-- Brand Logo -->
    <a href="/dashboard" class="brand-link">
      <img src="{{ asset('dist/img/AdminLTELogo.png') }}" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light">PDFTools</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="{{ asset('dist/img/user2-160x160.jpg') }}" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block">{{ Auth::user()->name }}</a>
        </div>
      </div>

      <!-- SidebarSearch Form -->
      <div class="form-inline">
        <div class="input-group" data-widget="sidebar-search">
          <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
          <div class="input-group-append">
            <button class="btn btn-sidebar">
              <i class="fas fa-search fa-fw"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->

          <li class="nav-item">
            <a href="/dashboard" class="nav-link active">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>
                Dashboard
              </p>
            </a>
          </li>
          @role('admin')
          <li class="nav-item">
            <a href="/users" class="nav-link">
              <i class="nav-icon far fa-user"></i>
              <p>
                Users
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="/users" class="nav-link">
                  <i class="fa fa-users nav-icon"></i>
                  <p>All Users</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="/add-user" class="nav-link">
                  <i class="fa fa-plus nav-icon"></i>
                  <p>Add User</p>
                </a>
              </li>
            </ul>
          </li>
          @endrole
          @role('admin')
          <li class="nav-item">
            <a href="/all-files" class="nav-link">
              <i class="nav-icon far fa-file"></i>
              <p>
                Files
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="/all-files" class="nav-link">
                  <i class="fa fa-file nav-icon"></i>
                  <p>Converted Files</p>
                </a>
              </li>
              <<!-- li class="nav-item">
                <a href="/add-user" class="nav-link">
                  <i class="fa fa-plus nav-icon"></i>
                  <p>Add User</p>
                </a>
              </li> -->
            </ul>
          </li>
          @endrole
          @role('user')
          <li class="nav-item">
            <a href="/all-files" class="nav-link">
              <i class="nav-icon far fa-file"></i>
              <p>
                My Converted Files
              </p>
            </a>
          </li>
          @endrole
          @role('admin')
          <li class="nav-item">
            <a href="/all-payments" class="nav-link">
              <i class="fa fa-credit-card nav-icon"></i>
              <p>
                Payments
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="/all-payments" class="nav-link">
                  <i class="fa fa-credit-card nav-icon"></i>
                  <p>All Payments</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="/all-gateways" class="nav-link">
                  <i class="fas fa-money-check-alt"></i>
                  <p>Payment Gateway</p>
                </a>
              </li>
            </ul>
          </li>
           
            <li class="nav-item">
              <a href="/all-contacts" class="nav-link">
                <i class="fa fa-address-book nav-icon"></i>
                <p>Contacts</p>
              </a>
            </li>
          @endrole
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>
