<!DOCTYPE html>
<html data-bs-theme="light" lang="{{ app()->getLocale() === 'zh_CN' ? 'zh-CN' : app()->getLocale() }}" dir="ltr">


@include('layouts.guest.head')

<body>
    <main class="main" id="top">
        <div class="container-fluid pt-3">
            <div class="d-flex justify-content-end">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" id="auth-language-menu" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="bi bi-translate me-1" aria-hidden="true"></span>{{ ['en' => 'EN', 'ms' => 'BM', 'zh_CN' => '中文'][app()->getLocale()] ?? 'EN' }}
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="auth-language-menu">
                        @foreach (['en' => 'English', 'ms' => 'Bahasa Melayu', 'zh_CN' => '中文'] as $locale => $language)
                            <form method="POST" action="{{ route('language.update') }}">
                                @csrf
                                <input type="hidden" name="locale" value="{{ $locale }}">
                                <button class="dropdown-item" type="submit" @if (app()->getLocale() === $locale) aria-current="true" @endif>{{ $language }}</button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            @yield('content')

        </div>
        @stack('modal')




    </main>
    <!-- ===============================================--><!--    End of Main Content--><!-- ===============================================-->



    @include('layouts.guest.scripts')

</body>

</html>