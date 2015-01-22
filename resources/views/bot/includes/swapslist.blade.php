<ul class="list">
@foreach ($swaps as $swap)
<li>
    Vend 1 {{ $swap['out'] }} for each {{ $swap['rate'] }} {{ $swap['in'] }} received
</li>

@endforeach
</ul>