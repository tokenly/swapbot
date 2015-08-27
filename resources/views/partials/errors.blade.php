@if (isset($errors) AND count($errors) > 0)
<div class="panel panel-danger">
    <div class="panel-heading">There were some errors.</div>
    <div class="panel-body">
        <ul class="list-unstyled">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>

@endif
