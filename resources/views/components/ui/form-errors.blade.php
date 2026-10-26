@if ($errors->any())
    <div class="dashboard-error form-error-summary" role="alert" tabindex="-1" data-form-errors>
        <h2>Please check your submission</h2>
        <p>Your changes have not been saved. Correct the fields below and try again.</p>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
