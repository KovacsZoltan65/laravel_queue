<nav class="navbar navbar-expand-lg bg-light" data-bs-theme="light">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">
        Laravel Batching and Queues
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarColor03" aria-controls="navbarColor03" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarColor03">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link active" href="{{ url('/') }}">Home
            <span class="visually-hidden">(current)</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ url('/progress') }}">Progress</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ url('/upload') }}">Upload</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ url('/import') }}">Excel Import</a>
        </li>
      </ul>
    </div>
  </div>
</nav>