<div class="card text-center p-2"> 
    <div class="card-body">
        <div class="avatar avatar-4xl mx-auto mb-3">
            @if ($user->profile_photo_url)
                <img class="rounded-circle profile-avatar-photo" src="{{ $user->profile_photo_url }}" alt="{{ $user->name }} profile photo">
            @else
                <div class="avatar-name rounded-circle bg-primary-subtle text-primary profile-avatar">
                    <span>{{ str($user->name)->substr(0, 1)->upper() }}</span>
                </div>
            @endif
        </div>
        <h2 class="h4 mb-1">{{ $user->name }}</h2>
        @unless ($user->hasRole('customer'))
            <p class="profile-role mb-2">{{ $user->teams->pluck('name')->join(', ') ?: 'Team member' }}</p>
            <p class="profile-location mb-0">
                <span class="fas fa-building me-1" aria-hidden="true"></span>
                {{ $user->teams->pluck('department.name')->filter()->unique()->join(', ') ?: 'No department assigned' }}
            </p>
        @endunless
    </div>
    @if ($isSelf)
        <div class="list-group list-group-flush text-start">
            <button class="list-group-item list-group-item-action" type="button" data-profile-tab="edit">
                <span class="fas fa-user-pen me-2" aria-hidden="true"></span>Edit profile
            </button>
            <button class="list-group-item list-group-item-action" type="button" data-profile-tab="activity">
                <span class="fas fa-clock-rotate-left me-2" aria-hidden="true"></span>Account activity
            </button>
            <button class="list-group-item list-group-item-action" type="button" data-profile-tab="security">
                <span class="fas fa-shield-halved me-2" aria-hidden="true"></span>Security
            </button>
        </div>
    @elseif (!$user->hasRole('customer'))
        <div class="card-footer bg-body d-flex justify-content-end">
            <a class="btn btn-outline-primary" href="{{ route('teams.index') }}">
                <span class="fas fa-users me-2" aria-hidden="true"></span>Team directory
            </a>
        </div>
    @endif
</div>
