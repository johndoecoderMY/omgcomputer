<ul class="nav nav-tabs mb-3">
    @foreach (['sales' => 'Sales', 'inventory' => 'Inventory', 'teams' => 'Team performance', 'marketing' => 'Marketing'] as $key => $label)
        <li class="nav-item"><a class="nav-link {{ $active === $key ? 'active' : '' }}" href="{{ route('reports.' . $key) }}">{{ $label }}</a></li>
    @endforeach
</ul>