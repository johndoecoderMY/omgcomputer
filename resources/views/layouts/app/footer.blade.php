<footer class="footer">
    <div class="row g-0 justify-content-between fs-10 mt-4 mb-3">
        <div class="col-12 col-sm-auto text-center">
            <p class="mb-0 text-600">&copy; {{ date('Y') }} OMG Empire (OMG Computer). All rights reserved.</p>
            @if ($companyProfile->ssm_registration_number)
                <p class="small text-600 mb-0">SSM registration no.: {{ $companyProfile->ssm_registration_number }}</p>
            @endif
        </div>
    </div>
</footer>