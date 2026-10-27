@props(['title' => 'Please check your submission', 'message' => 'Your changes have not been saved. Correct the fields below and try again.'])
@if ($errors->any())
    <div class="dashboard-error form-error-summary" role="alert" tabindex="-1" data-form-errors>
        <h2>{{ $title }}</h2>
        <p>{{ $message }}</p>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
