<style>
    .profile-page .profile-avatar {
        width: 6rem;
        height: 6rem;
        font-size: 2rem;
    }
    .profile-page .profile-avatar-photo {
        width: 6rem;
        height: 6rem;
        object-fit: cover;
    }
    .profile-page .profile-role {
        color: var(--falcon-primary);
        font-weight: 600;
    }
    .profile-page .profile-location {
        color: var(--falcon-secondary-color);
    }
    .profile-page .profile-section + .profile-section {
        margin-top: 1rem;
    }
    .profile-page .profile-section-header {
        border-bottom: 1px solid var(--falcon-border-color);
        margin-bottom: 1rem;
        padding-bottom: .75rem;
    }
    .profile-page .profile-info-item {
        min-width: 0;
    }
    .profile-page .profile-info-label {
        color: var(--falcon-secondary-color);
        display: block;
        font-size: .8rem;
        margin-bottom: .25rem;
    }
    .profile-page .profile-info-value {
        display: block;
        overflow-wrap: anywhere;
    }
    .profile-page .profile-tabs {
        border-bottom: 1px solid var(--falcon-border-color);
        display: flex;
        flex-wrap: wrap;
        gap: .25rem;
    }
    .profile-page .profile-tab {
        background: transparent;
        border: 0;
        border-bottom: 2px solid transparent;
        color: var(--falcon-secondary-color);
        padding: .8rem 1rem;
    }
    .profile-page .profile-tab.active {
        border-bottom-color: var(--falcon-primary);
        color: var(--falcon-primary);
        font-weight: 600;
    }
    .profile-page .profile-tab-pane[hidden] {
        display: none !important;
    }
</style>
